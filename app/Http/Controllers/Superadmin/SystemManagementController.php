<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BackupJob;
use App\Models\ImportJob;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use App\Models\Subject;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\WebSetting;
use App\Services\AuditService;
use App\Services\BackupRestoreService;
use App\Services\DiscordAlertService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SystemManagementController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly BackupRestoreService $backupRestoreService,
        private readonly ImportService $importService,
        private readonly DiscordAlertService $discordAlertService
    ) {
    }

    public function settings()
    {
        $setting = WebSetting::first();
        return view('superadmin.settings.index', compact('setting'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:255',
            'logo_url' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'manager_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:32',
        ]);

        $setting = WebSetting::firstOrCreate([]);
        $old = $setting->toArray();
        $setting->update($data);

        $this->auditService->log('UPDATE', $setting, $old, $setting->toArray());

        return back()->with('success', 'Setting web berhasil diperbarui.');
    }

    public function menuAccess()
    {
        $this->ensureDefaultMenus();
        $menuItems = MenuItem::orderBy('sort_order')->get();
        $roles = ['superadmin', 'owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];
        $permissions = MenuPermission::all()->groupBy(fn ($p) => $p->menu_item_id . ':' . $p->role_name);

        return view('superadmin.menu-access.index', compact('menuItems', 'roles', 'permissions'));
    }

    public function updateMenuAccess(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'array',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['permissions'] ?? [] as $menuId => $roleData) {
                foreach ($roleData as $roleName => $perm) {
                    MenuPermission::updateOrCreate(
                        [
                            'menu_item_id' => (int) $menuId,
                            'role_name' => $roleName,
                        ],
                        [
                            'can_view' => !empty($perm['can_view']),
                            'can_create' => !empty($perm['can_create']),
                            'can_update' => !empty($perm['can_update']),
                            'can_delete' => !empty($perm['can_delete']),
                        ]
                    );
                }
            }
        });

        $this->auditService->log('RBAC_UPDATED', null, [], ['menu_permissions' => true]);
        $this->discordAlertService->send('RBAC Menu Access Updated', [
            'actor_id' => auth()->id(),
            'time' => now()->toDateTimeString(),
        ], 'warning');

        return back()->with('success', 'Hak akses menu berhasil diperbarui.');
    }

    public function restoreCenter()
    {
        $deletedUsers = User::onlyTrashed()->latest()->take(20)->get();
        $deletedInvoices = Invoice::onlyTrashed()->latest()->take(20)->get();
        $deletedSubjects = Subject::onlyTrashed()->latest()->take(20)->get();
        $deletedSessions = TutoringSession::onlyTrashed()->latest()->take(20)->get();
        $deletedItems = Item::onlyTrashed()->latest()->take(20)->get();

        return view('superadmin.restore.index', compact(
            'deletedUsers',
            'deletedInvoices',
            'deletedSubjects',
            'deletedSessions',
            'deletedItems'
        ));
    }

    public function restoreData(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:user,invoice,subject,session,item',
            'id' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        $map = [
            'user' => User::class,
            'invoice' => Invoice::class,
            'subject' => Subject::class,
            'session' => TutoringSession::class,
            'item' => Item::class,
        ];

        $modelClass = $map[$validated['type']];
        $model = $modelClass::onlyTrashed()->findOrFail($validated['id']);
        $model->restore();

        $this->auditService->log('RESTORE_DATA', $model, [], [
            'reason' => $validated['reason'] ?? null,
        ]);

        $this->discordAlertService->send('Restore Data Executed', [
            'type' => $validated['type'],
            'record_id' => $validated['id'],
            'actor_id' => auth()->id(),
            'reason' => $validated['reason'] ?? '-',
        ], 'warning');

        return back()->with('success', 'Data berhasil direstore.');
    }

    public function requestHardDeleteOtp()
    {
        $user = auth()->user();
        $otp = (string) random_int(100000, 999999);
        cache()->put("hard_delete_otp_{$user->id}", $otp, now()->addMinutes(5));

        try {
            Mail::raw("OTP hard delete Anda: {$otp}. Berlaku 5 menit.", function ($message) use ($user) {
                $message->to($user->email)->subject('OTP Hard Delete');
            });
        } catch (\Throwable) {
            // keep non-blocking
        }

        return back()->with('success', 'OTP hard delete dikirim ke email Superadmin.');
    }

    public function hardDelete(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:user,invoice,subject,session,item',
            'id' => 'required|integer',
            'reason' => 'required|string|max:500',
            'otp_code' => 'required|string|size:6',
        ]);

        $cacheKey = 'hard_delete_otp_' . auth()->id();
        if (cache()->get($cacheKey) !== $validated['otp_code']) {
            return back()->withErrors(['otp_code' => 'OTP hard delete tidak valid.']);
        }
        cache()->forget($cacheKey);

        $map = [
            'user' => User::class,
            'invoice' => Invoice::class,
            'subject' => Subject::class,
            'session' => TutoringSession::class,
            'item' => Item::class,
        ];

        $modelClass = $map[$validated['type']];
        $model = $modelClass::withTrashed()->findOrFail($validated['id']);
        $snapshot = $model->toArray();
        $model->forceDelete();

        $this->auditService->log('HARD_DELETE', null, $snapshot, [
            'type' => $validated['type'],
            'id' => $validated['id'],
            'reason' => $validated['reason'],
        ]);

        $this->discordAlertService->send('Critical Hard Delete', [
            'type' => $validated['type'],
            'id' => $validated['id'],
            'actor_id' => auth()->id(),
            'reason' => $validated['reason'],
        ], 'critical');

        return back()->with('success', 'Hard delete berhasil dijalankan.');
    }

    public function backupCenter()
    {
        $backups = BackupJob::latest()->paginate(15);
        $routePrefix = request()->routeIs('admin.*') ? 'admin' : 'superadmin';
        return view('superadmin.backup.index', compact('backups', 'routePrefix'));
    }

    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:db,files,config',
            'mode' => 'required|in:update,full',
            'note' => 'nullable|string',
        ]);

        $backup = $this->backupRestoreService->createBackup(
            $validated['type'],
            $validated['mode'],
            auth()->id(),
            $validated['note'] ?? null
        );

        $this->auditService->log('BACKUP_CREATED', $backup);
        $this->discordAlertService->send('Backup Created', [
            'backup_id' => $backup->id,
            'type' => $backup->type,
            'mode' => $backup->mode,
        ], 'warning');

        return back()->with('success', 'Backup berhasil dibuat.');
    }

    public function previewPartialRestore(int $backupId)
    {
        $backup = BackupJob::findOrFail($backupId);
        $diff = $this->backupRestoreService->previewPartialRestore($backup);

        $this->auditService->log('RESTORE_PARTIAL_PREVIEW', $backup, [], ['diff' => $diff]);

        return back()->with('diffPreview', $diff)->with('success', 'Preview restore berhasil dibuat.');
    }

    public function applyPartialRestore(Request $request, int $backupId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $backup = BackupJob::findOrFail($backupId);
        $restoreJob = $this->backupRestoreService->applyPartialRestore($backup, auth()->id(), $request->reason);

        $this->auditService->log('RESTORE_PARTIAL_APPLY', $restoreJob);
        $this->discordAlertService->send('Partial Restore Applied', [
            'backup_id' => $backup->id,
            'restore_job_id' => $restoreJob->id,
            'actor_id' => auth()->id(),
        ], 'critical');

        return back()->with('success', 'Partial restore berhasil dijalankan.');
    }

    public function disasterRestore(Request $request, int $backupId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'confirm_phrase' => 'required|in:DISASTER_RESTORE_APPROVED',
        ]);

        $backup = BackupJob::findOrFail($backupId);
        $restoreJob = $this->backupRestoreService->applyDisasterRestore($backup, auth()->id(), $request->reason);

        $this->auditService->log('DISASTER_RESTORE', $restoreJob);
        $this->discordAlertService->send('Disaster Restore Applied', [
            'backup_id' => $backup->id,
            'restore_job_id' => $restoreJob->id,
            'actor_id' => auth()->id(),
        ], 'critical');

        return back()->with('success', 'Disaster restore berhasil dijalankan.');
    }

    public function importCenter()
    {
        $jobs = ImportJob::latest()->paginate(15);
        $routePrefix = request()->routeIs('admin.*') ? 'admin' : 'superadmin';
        return view('superadmin.import.index', compact('jobs', 'routePrefix'));
    }

    public function importUsers(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $job = $this->importService->importUsers($request->file('file'), auth()->id());
        $this->auditService->log('IMPORT_USERS', $job);
        return back()->with('success', 'Import user selesai.');
    }

    public function importItems(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $job = $this->importService->importItems($request->file('file'), auth()->id());
        $this->auditService->log('IMPORT_ITEMS', $job);
        return back()->with('success', 'Import barang selesai.');
    }

    private function ensureDefaultMenus(): void
    {
        $menus = [
            ['code' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'home', 'sort_order' => 1],
            ['code' => 'student_dashboard', 'label' => 'Dashboard Siswa', 'route_name' => 'student.dashboard', 'sort_order' => 2],
            ['code' => 'tutor_dashboard', 'label' => 'Dashboard Guru', 'route_name' => 'tutor.dashboard', 'sort_order' => 3],
            ['code' => 'admin_dashboard', 'label' => 'Dashboard Admin', 'route_name' => 'admin.dashboard', 'sort_order' => 4],
            ['code' => 'manager_dashboard', 'label' => 'Dashboard Manager', 'route_name' => 'manager.dashboard', 'sort_order' => 5],
            ['code' => 'parent_dashboard', 'label' => 'Dashboard Orang Tua', 'route_name' => 'parent.dashboard', 'sort_order' => 6],
            ['code' => 'owner_dashboard', 'label' => 'Dashboard Owner', 'route_name' => 'owner.dashboard', 'sort_order' => 7],
            ['code' => 'superadmin_dashboard', 'label' => 'Dashboard Superadmin', 'route_name' => 'superadmin.dashboard', 'sort_order' => 8],
            ['code' => 'settings', 'label' => 'Setting Web', 'route_name' => 'superadmin.settings', 'sort_order' => 9],
            ['code' => 'menu_access', 'label' => 'Hak Akses Menu', 'route_name' => 'superadmin.menu.access', 'sort_order' => 10],
            ['code' => 'restore_center', 'label' => 'Restore Data', 'route_name' => 'superadmin.restore.center', 'sort_order' => 11],
            ['code' => 'backup_center', 'label' => 'Backup Restore', 'route_name' => 'superadmin.backup.center', 'sort_order' => 12],
            ['code' => 'import_center', 'label' => 'Import Data', 'route_name' => 'superadmin.import.center', 'sort_order' => 13],
        ];

        foreach ($menus as $menu) {
            MenuItem::updateOrCreate(['code' => $menu['code']], $menu);
        }
    }
}
