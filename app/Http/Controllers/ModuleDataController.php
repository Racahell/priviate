<?php

namespace App\Http\Controllers;

use App\Jobs\SendRawEmailJob;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PackageQuota;
use App\Models\Dispute;
use App\Models\DisputeAction;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TentorProfile;
use App\Models\TentorSkill;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
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
        $perPage = $this->resolvePerPage($request, 15);

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

        $rows = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
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
            if ($module === 'users') {
                $detailQuery->with([
                    'roles',
                    'tentorProfile.skills',
                ]);
            }
            if ($module === 'disputes') {
                $detailQuery->with('actions');
            }
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
            'subjectOptions' => $module === 'users'
                ? Subject::query()->orderBy('name')->get(['id', 'name', 'level'])
                : collect(),
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

        return $this->redirectToModuleIndex($request, $module)
            ->with('success', $cfg['title'] . ' berhasil dihapus.');
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
            return back()->with('success', $cfg['title'] . ' berhasil delete (bulk).');
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
            'disputes' => [
                'model' => Dispute::class,
                'title' => 'Kritik',
                'columns' => ['id', 'tutoring_session_id', 'source_role', 'reason', 'status', 'priority'],
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
            'sessions' => [
                'model' => ScheduleSlot::class,
                'title' => 'Sesi',
                'columns' => ['id', 'start_at', 'end_at'],
            ],
        ];

        abort_unless(array_key_exists($module, $configs), 404);
        return $configs[$module];
    }

    private function formConfig(string $module, bool $isSuperadmin): array
    {
        $roleOptions = $isSuperadmin
            ? ['superadmin', 'owner', 'admin', 'tentor', 'siswa', 'orang_tua']
            : ['owner', 'admin', 'tentor', 'siswa', 'orang_tua'];

        return match ($module) {
            'packages' => [
                'fields' => ['name', 'description', 'is_active', 'trial_enabled', 'trial_limit', 'price', 'quota'],
            ],
            'subjects' => [
                'fields' => ['name', 'level', 'description', 'is_active'],
            ],
            'disputes' => [
                'fields' => ['tutoring_session_id', 'reason', 'description', 'status', 'priority'],
            ],
            'items' => [
                'fields' => ['sku', 'name', 'description', 'price', 'stock', 'is_active'],
            ],
            'users' => [
                'fields' => ['name', 'email', 'phone', 'is_active', 'role', 'password', 'password_confirmation'],
                'role_options' => $roleOptions,
            ],
            'sessions' => [
                'fields' => ['start_at', 'end_at'],
            ],
            default => ['fields' => []],
        };
    }

    private function save(Request $request, string $module, ?int $id)
    {
        return match ($module) {
            'packages' => $this->savePackage($request, $id),
            'subjects' => $this->saveSubject($request, $id),
            'disputes' => $this->saveDispute($request, $id),
            'items' => $this->saveItem($request, $id),
            'users' => $this->saveUser($request, $id),
            'sessions' => $this->saveSession($request, $id),
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

        return $this->redirectToModuleIndex($request, 'packages')
            ->with('success', 'Paket berhasil disimpan.');
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

        return $this->redirectToModuleIndex($request, 'subjects')
            ->with('success', 'Mapel berhasil disimpan.');
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

        return $this->redirectToModuleIndex($request, 'items')
            ->with('success', 'Item berhasil disimpan.');
    }

    private function saveDispute(Request $request, ?int $id)
    {
        if (!$id) {
            return back()->withErrors(['reason' => 'Kritik tidak bisa dibuat manual dari halaman ini.']);
        }

        $dispute = Dispute::query()->withTrashed()->with('actions')->findOrFail($id);
        $hasReply = $dispute->actions->contains(fn ($a) => !empty(trim((string) $a->notes)));
        $isReplyStage = $request->input('reply_stage') === 'send_reply' && !$hasReply;

        if ($isReplyStage) {
            $validated = $request->validate([
                'reply_notes' => 'required|string',
                'status' => ['required', Rule::in(['IN_REVIEW_L1', 'IN_REVIEW_ADMIN', 'RESOLVED'])],
            ]);

            $dispute->fill([
                'status' => $validated['status'],
                'resolved_at' => $validated['status'] === 'RESOLVED' ? now() : null,
                'resolved_by' => $validated['status'] === 'RESOLVED' ? auth()->id() : null,
            ])->save();

            DisputeAction::create([
                'dispute_id' => $dispute->id,
                'actor_id' => auth()->id(),
                'action' => 'REPLIED',
                'notes' => $validated['reply_notes'],
            ]);

            $this->sendDisputeReplyEmail($dispute, (string) $validated['reply_notes'], (string) $validated['status']);

            return $this->redirectToModuleIndex($request, 'disputes')
                ->with('success', 'Jawaban kritik berhasil dikirim.');
        }

        $validated = $request->validate([
            'tutoring_session_id' => 'nullable|integer|exists:tutoring_sessions,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['IN_REVIEW_L1', 'IN_REVIEW_ADMIN', 'RESOLVED'])],
            'priority' => ['nullable', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
            'reply_notes' => 'nullable|string',
        ]);

        $dispute->fill([
            'tutoring_session_id' => $validated['tutoring_session_id'] ?? null,
            'created_by' => $dispute->created_by ?: auth()->id(),
            'source_role' => $dispute->source_role ?: (string) auth()->user()?->getRoleNames()->first(),
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'] ?? 'MEDIUM',
            'resolved_at' => $validated['status'] === 'RESOLVED' ? now() : null,
            'resolved_by' => $validated['status'] === 'RESOLVED' ? auth()->id() : null,
        ])->save();

        if (!empty(trim((string) ($validated['reply_notes'] ?? '')))) {
            DisputeAction::create([
                'dispute_id' => $dispute->id,
                'actor_id' => auth()->id(),
                'action' => 'UPDATED',
                'notes' => $validated['reply_notes'],
            ]);
            $this->sendDisputeReplyEmail($dispute, (string) $validated['reply_notes'], (string) $validated['status']);
        }

        return $this->redirectToModuleIndex($request, 'disputes')
            ->with('success', 'Kritik berhasil disimpan.');
    }

    private function sendDisputeReplyEmail(Dispute $dispute, string $notes, string $status): void
    {
        if (trim($notes) === '') {
            return;
        }

        $creator = User::query()->find($dispute->created_by);
        if (!$creator || empty($creator->email)) {
            return;
        }

        try {
            Mail::to($creator->email)->send(new \App\Mail\DisputeReplyMail(
                $dispute,
                $notes,
                $status,
                auth()->user()?->name,
                $creator->name
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send dispute reply email from module flow.', [
                'dispute_id' => $dispute->id,
                'recipient' => $creator->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function saveUser(Request $request, ?int $id)
    {
        $isSuperadmin = $request->user()?->hasRole('superadmin');
        $allowedRoles = $isSuperadmin
            ? ['superadmin', 'owner', 'admin', 'tentor', 'siswa', 'orang_tua']
            : ['owner', 'admin', 'tentor', 'siswa', 'orang_tua'];

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255'],
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => $id ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
            'teaching_subject_ids' => 'nullable|array',
            'teaching_subject_ids.*' => 'integer|exists:subjects,id',
            'education' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'domicile' => 'nullable|string|max:255',
            'teaching_mode' => 'nullable|in:online,offline,hybrid',
            'offline_coverage' => 'nullable|string|max:255',
            'tentor_bio' => 'nullable|string',
            'verification_status' => ['nullable', Rule::in(['PENDING_REVIEW', 'APPROVED', 'VERIFIED', 'REJECTED', 'NEEDS_REVISION'])],
            'verification_notes' => 'nullable|string',
            'intro_video_url' => 'nullable|url|max:255',
            'cv_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'diploma_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_card_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'profile_photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ];
        if ($id) {
            $rules['email'][] = Rule::unique('users', 'email')->ignore($id);
        } else {
            $rules['email'][] = Rule::unique('users', 'email');
        }
        $validated = $request->validate($rules);

        if (($validated['role'] ?? '') === 'tentor') {
            $request->validate([
                'teaching_subject_ids' => 'required|array|min:1',
                'education' => 'required|string|max:255',
                'domicile' => 'required|string|max:255',
            ]);
        }

        $user = $id ? User::findOrFail($id) : new User();
        $existingTentorProfile = TentorProfile::query()->where('user_id', $user->id)->first();
        $previousVerificationStatus = $this->normalizeTentorVerificationStatus((string) ($existingTentorProfile?->verification_status ?? ''));
        $verificationStatus = $this->normalizeTentorVerificationStatus((string) ($validated['verification_status'] ?? 'PENDING_REVIEW'));
        $isTentor = ($validated['role'] ?? '') === 'tentor';
        $isActive = (bool) ($validated['is_active'] ?? false);
        if ($isTentor) {
            $isActive = $this->isTentorApprovedStatus($verificationStatus);
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $isActive,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        $user->syncRoles([$validated['role']]);
        $this->syncTentorMasterData($user, $validated, $request);
        if ($isTentor && $previousVerificationStatus !== $verificationStatus) {
            $this->sendTentorVerificationEmail($user, $verificationStatus, (string) ($validated['verification_notes'] ?? ''));
        }

        return $this->redirectToModuleIndex($request, 'users')
            ->with('success', 'User berhasil disimpan.');
    }

    private function syncTentorMasterData(User $user, array $validated, Request $request): void
    {
        $role = (string) ($validated['role'] ?? '');
        $profile = TentorProfile::query()->where('user_id', $user->id)->first();

        if ($role !== 'tentor') {
            if (!$profile) {
                return;
            }
            $profile->update(['is_verified' => false]);
            TentorSkill::query()->where('tentor_profile_id', $profile->id)->delete();
            return;
        }

        if (!$profile) {
            $profile = TentorProfile::query()->create([
                'user_id' => $user->id,
                'verification_status' => 'PENDING_REVIEW',
                'is_verified' => false,
            ]);
        }

        $verificationStatus = $this->normalizeTentorVerificationStatus((string) ($validated['verification_status'] ?? ($profile->verification_status ?: 'PENDING_REVIEW')));
        $profile->fill([
            'bio' => $validated['tentor_bio'] ?? $profile->bio,
            'education' => $validated['education'] ?? $profile->education,
            'experience_years' => $validated['experience_years'] ?? $profile->experience_years,
            'domicile' => $validated['domicile'] ?? $profile->domicile,
            'teaching_mode' => $validated['teaching_mode'] ?? $profile->teaching_mode ?? 'online',
            'offline_coverage' => $validated['offline_coverage'] ?? $profile->offline_coverage,
            'verification_status' => $verificationStatus,
            'verification_notes' => $validated['verification_notes'] ?? $profile->verification_notes,
            'intro_video_url' => $validated['intro_video_url'] ?? $profile->intro_video_url,
            'is_verified' => $this->isTentorApprovedStatus($verificationStatus),
        ]);

        $cvPath = $this->storeTentorDocument($request->file('cv_file'), $user->id);
        $diplomaPath = $this->storeTentorDocument($request->file('diploma_file'), $user->id);
        $certificatePath = $this->storeTentorDocument($request->file('certificate_file'), $user->id);
        $idCardPath = $this->storeTentorDocument($request->file('id_card_file'), $user->id);
        $photoPath = $this->storeTentorDocument($request->file('profile_photo_file'), $user->id);
        if ($cvPath) {
            $profile->cv_path = $cvPath;
        }
        if ($diplomaPath) {
            $profile->diploma_path = $diplomaPath;
        }
        if ($certificatePath) {
            $profile->certificate_path = $certificatePath;
        }
        if ($idCardPath) {
            $profile->id_card_path = $idCardPath;
        }
        if ($photoPath) {
            $profile->profile_photo_path = $photoPath;
        }
        $profile->save();

        $subjectIds = collect($validated['teaching_subject_ids'] ?? [])
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        TentorSkill::query()
            ->where('tentor_profile_id', $profile->id)
            ->whereNotIn('subject_id', $subjectIds ?: [0])
            ->delete();

        foreach ($subjectIds as $subjectId) {
            TentorSkill::query()->updateOrCreate(
                [
                    'tentor_profile_id' => $profile->id,
                    'subject_id' => $subjectId,
                ],
                [
                    'hourly_rate' => 0,
                    'is_verified' => $this->isTentorApprovedStatus($verificationStatus),
                ]
            );
        }
    }

    private function storeTentorDocument(?UploadedFile $file, int $userId): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        return $file->store("tentor-docs/{$userId}", 'public');
    }

    private function normalizeTentorVerificationStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        if ($normalized === 'VERIFIED') {
            return 'APPROVED';
        }
        if (in_array($normalized, ['PENDING_REVIEW', 'APPROVED', 'NEEDS_REVISION', 'REJECTED'], true)) {
            return $normalized;
        }

        return 'PENDING_REVIEW';
    }

    private function isTentorApprovedStatus(string $status): bool
    {
        return $this->normalizeTentorVerificationStatus($status) === 'APPROVED';
    }

    private function sendTentorVerificationEmail(User $user, string $status, string $notes = ''): void
    {
        if (empty($user->email)) {
            return;
        }

        $state = $this->normalizeTentorVerificationStatus($status);
        $label = match ($state) {
            'APPROVED' => 'DISETUJUI',
            'NEEDS_REVISION' => 'PERLU REVISI',
            'REJECTED' => 'DITOLAK',
            default => 'PENDING REVIEW',
        };
        $message = "Halo {$user->name},\n\nStatus verifikasi akun tentor Anda: {$label}.";
        if (trim($notes) !== '') {
            $message .= "\n\nCatatan admin:\n{$notes}";
        }
        if ($state === 'APPROVED') {
            $message .= "\n\nAkun Anda sudah aktif dan bisa login.";
        }
        $message .= "\n\nTerima kasih.";

        SendRawEmailJob::dispatchSync($user->email, 'Update Status Verifikasi Tentor', $message);
    }

    private function saveSession(Request $request, ?int $id)
    {
        $validated = $request->validate([
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'status' => ['nullable', Rule::in(['OPEN', 'CLOSED'])],
        ]);

        $status = $validated['status'] ?? 'OPEN';
        if (!$id) {
            $baseDate = now()->startOfDay();
            [$startAt, $endAt] = $this->buildSessionDateTime($baseDate, $validated['start_at'], $validated['end_at']);

            if ($this->hasSessionTimeConflict($startAt, $endAt)) {
                return $this->redirectToModuleIndex($request, 'sessions')
                    ->withErrors(['start_at' => 'Jam sesi bentrok dengan sesi lain. Gunakan jam yang berbeda.'])
                    ->withInput();
            }

            ScheduleSlot::query()->create([
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);

            return $this->redirectToModuleIndex($request, 'sessions')
                ->with('success', 'Sesi berhasil ditambahkan.');
        }

        $slot = ScheduleSlot::query()->withTrashed()->findOrFail($id);
        if ($slot->tutoringSessions()->exists()) {
            return $this->redirectToModuleIndex($request, 'sessions')
                ->withErrors(['status' => 'Master slot yang sudah dipakai booking tidak boleh diubah.']);
        }
        $slotDate = ($slot->start_at ? Carbon::parse($slot->start_at) : now())->startOfDay();
        [$startAt, $endAt] = $this->buildSessionDateTime($slotDate, $validated['start_at'], $validated['end_at']);

        if ($this->hasSessionTimeConflict($startAt, $endAt, $slot->id)) {
            return $this->redirectToModuleIndex($request, 'sessions')
                ->withErrors(['start_at' => 'Jam sesi bentrok dengan sesi lain. Gunakan jam yang berbeda.'])
                ->withInput();
        }

        $slot->fill([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $validated['status'] ?? ($slot->status ?: 'OPEN'),
            'created_by' => $slot->created_by ?: auth()->id(),
        ])->save();

        return $this->redirectToModuleIndex($request, 'sessions')
            ->with('success', 'Sesi berhasil diperbarui.');
    }

    private function redirectToModuleIndex(Request $request, string $module)
    {
        $prefix = $request->routeIs('superadmin.*') ? 'superadmin' : 'admin';
        return redirect()->route($prefix . '.modules.' . $module, ['tab' => 'active']);
    }

    private function buildSessionDateTime(Carbon $date, string $startTime, string $endTime): array
    {
        $startAt = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $endAt = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);
        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    private function hasSessionTimeConflict(Carbon $startAt, Carbon $endAt, ?int $ignoreId = null): bool
    {
        return ScheduleSlot::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($startAt, $endAt) {
                $query
                    ->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->exists();
    }

    private function applySearch($query, string $module, string $q): void
    {
        $like = '%' . $q . '%';
        $query->where(function ($inner) use ($module, $like) {
            match ($module) {
                'packages' => $inner->where('name', 'like', $like)->orWhere('description', 'like', $like),
                'subjects' => $inner->where('name', 'like', $like)->orWhere('level', 'like', $like)->orWhere('description', 'like', $like),
                'disputes' => $inner->where('reason', 'like', $like)->orWhere('description', 'like', $like)->orWhere('status', 'like', $like)->orWhere('source_role', 'like', $like),
                'items' => $inner->where('sku', 'like', $like)->orWhere('name', 'like', $like)->orWhere('description', 'like', $like),
                'users' => $inner->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like),
                'sessions' => $inner->where('status', 'like', $like)->orWhere('id', 'like', $like),
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
            'sessions' => $this->canForceDeleteSession($row),
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

    private function canForceDeleteSession(Model $row): array
    {
        $used = TutoringSession::where('schedule_slot_id', $row->id)->exists();
        return $used
            ? [false, 'Sesi sudah dipakai di tutoring session, hard delete ditolak.']
            : [true, 'OK'];
    }

    private function resolvePerPage(Request $request, int $default = 15): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->query('per_page', $default);
        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
