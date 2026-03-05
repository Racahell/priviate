<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PackageQuota;
use App\Models\Subject;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ModuleDataController extends Controller
{
    public function index(Request $request, string $module)
    {
        $cfg = $this->moduleConfig($module);
        $tab = $request->query('tab', 'active');
        $isSuperadmin = $request->user()?->hasRole('superadmin');
        $mode = (string) $request->query('mode', '');
        $detailId = $request->query('detail');
        $q = trim((string) $request->query('q', ''));
        $activeFilter = (string) $request->query('active', '');

        $query = ($cfg['model'])::query();
        if ($module === 'users') {
            $query->with('roles');
        }

        if ($tab === 'deleted' && $isSuperadmin) {
            $query->onlyTrashed();
        } else {
            $tab = 'active';
        }

        if ($q !== '') {
            $this->applySearch($query, $module, $q);
        }

        if ($tab === 'active' && in_array($activeFilter, ['1', '0'], true) && in_array($module, ['packages', 'subjects', 'items', 'users'], true)) {
            $query->where('is_active', (int) $activeFilter);
        }

        $rows = $query->orderByDesc('id')->paginate(15);
        $rows->appends([
            'tab' => $tab,
            'q' => $q,
            'active' => $activeFilter,
            'mode' => $mode,
            'detail' => $detailId,
        ]);
        $detail = null;
        if (!empty($detailId)) {
            $detailQuery = ($cfg['model'])::query();
            if ($tab === 'deleted' && $isSuperadmin) {
                $detailQuery->onlyTrashed();
            }
            $detail = $detailQuery->find($detailId);
        }

        return view('modules.index', [
            'module' => $module,
            'title' => $cfg['title'],
            'columns' => $cfg['columns'],
            'rows' => $rows,
            'tab' => $tab,
            'isSuperadmin' => $isSuperadmin,
            'mode' => $mode,
            'detail' => $detail,
            'formConfig' => $this->formConfig($module, $request->user()?->hasRole('superadmin')),
            'q' => $q,
            'activeFilter' => $activeFilter,
        ]);
    }

    public function store(Request $request, string $module)
    {
        return $this->save($request, $module, null);
    }

    public function update(Request $request, string $module, int $id)
    {
        return $this->save($request, $module, $id);
    }

    public function softDelete(Request $request, string $module, int $id)
    {
        $cfg = $this->moduleConfig($module);
        /** @var Model $row */
        $row = ($cfg['model'])::query()->findOrFail($id);
        $row->delete();

        return back()->with('success', $cfg['title'] . ' berhasil dihapus (soft delete).');
    }

    public function restore(Request $request, string $module, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $cfg = $this->moduleConfig($module);
        /** @var Model $row */
        $row = ($cfg['model'])::query()->onlyTrashed()->findOrFail($id);
        $row->restore();

        return back()->with('success', $cfg['title'] . ' berhasil direstore.');
    }

    public function forceDelete(Request $request, string $module, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $cfg = $this->moduleConfig($module);
        /** @var Model $row */
        $row = ($cfg['model'])::query()->withTrashed()->findOrFail($id);

        [$canDelete, $message] = $this->canForceDelete($module, $row);
        if (!$canDelete) {
            return back()->withErrors(['reason' => $message]);
        }

        $row->forceDelete();

        return back()->with('success', $cfg['title'] . ' berhasil dihapus permanen.');
    }

    public function bulk(Request $request, string $module)
    {
        $cfg = $this->moduleConfig($module);
        $action = (string) $request->input('action');
        $ids = collect($request->input('ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values();

        if ($ids->isEmpty()) {
            return back()->withErrors(['action' => 'Pilih minimal satu data.']);
        }

        if ($action === 'soft_delete') {
            ($cfg['model'])::query()->whereIn('id', $ids)->get()->each->delete();
            return back()->with('success', $cfg['title'] . ' berhasil soft delete (bulk).');
        }

        abort_unless($request->user()?->hasRole('superadmin'), 403);

        if ($action === 'restore') {
            ($cfg['model'])::query()->onlyTrashed()->whereIn('id', $ids)->get()->each->restore();
            return back()->with('success', $cfg['title'] . ' berhasil restore (bulk).');
        }

        if ($action === 'force_delete') {
            $reason = trim((string) $request->input('reason', ''));
            if ($reason === '') {
                return back()->withErrors(['reason' => 'Alasan hard delete bulk wajib diisi.']);
            }

            $failed = [];
            $okIds = [];
            $rows = ($cfg['model'])::query()->withTrashed()->whereIn('id', $ids)->get();
            foreach ($rows as $row) {
                [$canDelete, $message] = $this->canForceDelete($module, $row);
                if (!$canDelete) {
                    $failed[] = "#{$row->id}: {$message}";
                    continue;
                }
                $okIds[] = $row->id;
            }

            if (!empty($okIds)) {
                ($cfg['model'])::query()->withTrashed()->whereIn('id', $okIds)->get()->each->forceDelete();
            }

            if (!empty($failed)) {
                return back()->withErrors(['reason' => 'Sebagian gagal hard delete: ' . implode(' | ', $failed)])
                    ->with('status', 'Sebagian data berhasil hard delete.');
            }

            return back()->with('success', $cfg['title'] . ' berhasil hard delete (bulk).');
        }

        return back()->withErrors(['action' => 'Aksi bulk tidak valid.']);
    }

    private function moduleConfig(string $module): array
    {
        $configs = [
            'packages' => [
                'model' => Package::class,
                'title' => 'Paket',
                'columns' => ['id', 'name', 'description', 'is_active'],
            ],
            'subjects' => [
                'model' => Subject::class,
                'title' => 'Mapel',
                'columns' => ['id', 'name', 'level', 'is_active'],
            ],
            'items' => [
                'model' => Item::class,
                'title' => 'Item',
                'columns' => ['id', 'sku', 'name', 'price', 'stock', 'is_active'],
            ],
            'users' => [
                'model' => User::class,
                'title' => 'User',
                'columns' => ['id', 'name', 'email', 'phone', 'is_active', 'role'],
            ],
        ];

        abort_unless(array_key_exists($module, $configs), 404);
        return $configs[$module];
    }

    private function formConfig(string $module, bool $isSuperadmin): array
    {
        $roleOptions = $isSuperadmin
            ? ['superadmin', 'owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua']
            : ['owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];

        return match ($module) {
            'packages' => [
                'fields' => ['name', 'description', 'is_active', 'trial_enabled', 'trial_limit', 'price', 'quota'],
            ],
            'subjects' => [
                'fields' => ['name', 'level', 'description', 'is_active'],
            ],
            'items' => [
                'fields' => ['sku', 'name', 'description', 'price', 'stock', 'is_active'],
            ],
            'users' => [
                'fields' => ['name', 'email', 'phone', 'is_active', 'role', 'password', 'password_confirmation'],
                'role_options' => $roleOptions,
            ],
            default => ['fields' => []],
        };
    }

    private function save(Request $request, string $module, ?int $id)
    {
        return match ($module) {
            'packages' => $this->savePackage($request, $id),
            'subjects' => $this->saveSubject($request, $id),
            'items' => $this->saveItem($request, $id),
            'users' => $this->saveUser($request, $id),
            default => abort(404),
        };
    }

    private function savePackage(Request $request, ?int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'trial_enabled' => 'nullable|boolean',
            'trial_limit' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0',
            'quota' => 'nullable|integer|min:0',
        ]);

        $package = $id ? Package::findOrFail($id) : new Package();
        if ($id && array_key_exists('is_active', $validated) && !(bool) ($validated['is_active'] ?? false)) {
            $usedQuota = (int) PackageQuota::where('package_id', $package->id)->sum('used_quota');
            if ($usedQuota > 0) {
                return back()->withErrors(['is_active' => 'Paket tidak bisa dinonaktifkan karena sudah dipakai (used quota > 0).']);
            }
        }
        $package->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'trial_enabled' => (bool) ($validated['trial_enabled'] ?? false),
            'trial_limit' => (int) ($validated['trial_limit'] ?? 0),
        ])->save();

        PackagePrice::updateOrCreate(
            ['package_id' => $package->id, 'is_active' => true],
            ['price' => $validated['price']]
        );

        if (array_key_exists('quota', $validated)) {
            PackageQuota::updateOrCreate(
                ['package_id' => $package->id, 'is_active' => true],
                ['quota' => (int) ($validated['quota'] ?? 0)]
            );
        }

        return back()->with('success', 'Paket berhasil disimpan.');
    }

    private function saveSubject(Request $request, ?int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $subject = $id ? Subject::findOrFail($id) : new Subject();
        $subject->fill([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return back()->with('success', 'Mapel berhasil disimpan.');
    }

    private function saveItem(Request $request, ?int $id)
    {
        $rules = [
            'sku' => ['required', 'string', 'max:100'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
        if ($id) {
            $rules['sku'][] = Rule::unique('items', 'sku')->ignore($id);
        } else {
            $rules['sku'][] = Rule::unique('items', 'sku');
        }
        $validated = $request->validate($rules);

        $item = $id ? Item::findOrFail($id) : new Item();
        $item->fill([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return back()->with('success', 'Item berhasil disimpan.');
    }

    private function saveUser(Request $request, ?int $id)
    {
        $isSuperadmin = $request->user()?->hasRole('superadmin');
        $allowedRoles = $isSuperadmin
            ? ['superadmin', 'owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua']
            : ['owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255'],
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => $id ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
        ];
        if ($id) {
            $rules['email'][] = Rule::unique('users', 'email')->ignore($id);
        } else {
            $rules['email'][] = Rule::unique('users', 'email');
        }
        $validated = $request->validate($rules);

        $user = $id ? User::findOrFail($id) : new User();
        $user->fill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'User berhasil disimpan.');
    }

    private function applySearch($query, string $module, string $q): void
    {
        $like = '%' . $q . '%';
        $query->where(function ($inner) use ($module, $like) {
            match ($module) {
                'packages' => $inner->where('name', 'like', $like)->orWhere('description', 'like', $like),
                'subjects' => $inner->where('name', 'like', $like)->orWhere('level', 'like', $like)->orWhere('description', 'like', $like),
                'items' => $inner->where('sku', 'like', $like)->orWhere('name', 'like', $like)->orWhere('description', 'like', $like),
                'users' => $inner->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like),
                default => null,
            };
        });
    }

    private function canForceDelete(string $module, Model $row): array
    {
        return match ($module) {
            'users' => $this->canForceDeleteUser($row),
            'subjects' => $this->canForceDeleteSubject($row),
            'packages' => $this->canForceDeletePackage($row),
            default => [true, 'OK'],
        };
    }

    private function canForceDeleteUser(Model $row): array
    {
        $used = Invoice::where('user_id', $row->id)->exists()
            || TutoringSession::where('student_id', $row->id)->exists()
            || TutoringSession::where('tentor_id', $row->id)->exists();

        return $used
            ? [false, 'User sudah dipakai di transaksi/sesi, hard delete ditolak.']
            : [true, 'OK'];
    }

    private function canForceDeleteSubject(Model $row): array
    {
        $used = TutoringSession::where('subject_id', $row->id)->exists();
        return $used
            ? [false, 'Mapel sudah dipakai di sesi, hard delete ditolak.']
            : [true, 'OK'];
    }

    private function canForceDeletePackage(Model $row): array
    {
        $used = PackagePrice::where('package_id', $row->id)->exists()
            || PackageQuota::where('package_id', $row->id)->exists();

        return $used
            ? [false, 'Paket sudah punya relasi harga/kuota, hard delete ditolak.']
            : [true, 'OK'];
    }
}
