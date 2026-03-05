<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BackupJob;
use App\Models\ImportJob;
use App\Models\MenuItem;
use App\Models\MenuPermission;
use App\Models\User;
use App\Models\WebSetting;
use App\Services\AuditService;
use App\Services\BackupRestoreService;
use App\Services\DiscordAlertService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'address' => 'nullable|string',
            'manager_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:32',
            'footer_content' => 'nullable|string',
            'logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $setting = WebSetting::firstOrCreate([]);
        $old = $setting->toArray();
        $extra = is_array($setting->extra) ? $setting->extra : [];
        $extra['footer_content'] = $data['footer_content'] ?? ($extra['footer_content'] ?? null);
        unset($data['footer_content']);

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('branding', 'public');
            $data['logo_url'] = 'storage/' . $path;
        }

        $setting->update([
            ...$data,
            'extra' => $extra,
        ]);

        $this->auditService->log('UPDATE', $setting, $old, $setting->toArray());

        return back()->with('success', 'Setting web berhasil diperbarui.');
    }

    public function menuAccess()
    {
        $this->ensureDefaultMenus();
        $roles = ['owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];
        $menuCount = MenuItem::where('is_active', true)->count();

        return view('superadmin.menu-access.index', compact('roles', 'menuCount'));
    }

    public function menuAccessRole(string $role)
    {
        $roles = ['owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];
        abort_unless(in_array($role, $roles, true), 404);

        $menuItems = MenuItem::where('is_active', true)->orderBy('sort_order')->get();
        $permissions = MenuPermission::where('role_name', $role)->get()->keyBy('menu_item_id');

        return view('superadmin.menu-access.role', compact('role', 'menuItems', 'permissions'));
    }

    public function updateMenuAccessRole(Request $request, string $role)
    {
        $roles = ['owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];
        abort_unless(in_array($role, $roles, true), 404);

        $data = $request->validate([
            'permissions' => 'array',
        ]);

        DB::transaction(function () use ($data, $role) {
            foreach ($data['permissions'] ?? [] as $menuId => $perm) {
                MenuPermission::updateOrCreate(
                    [
                        'menu_item_id' => (int) $menuId,
                        'role_name' => $role,
                    ],
                    [
                        'can_view' => !empty($perm['can_view']),
                        'can_create' => !empty($perm['can_create']),
                        'can_update' => !empty($perm['can_update']),
                        'can_delete' => !empty($perm['can_delete']),
                    ]
                );
            }
        });

        $this->auditService->log('RBAC_UPDATED', null, [], ['menu_permissions' => true, 'role' => $role]);
        $this->discordAlertService->send('RBAC Menu Access Updated', [
            'actor_id' => auth()->id(),
            'time' => now()->toDateTimeString(),
            'role' => $role,
        ], 'warning');

        return back()->with('success', 'Hak akses menu role berhasil diperbarui.');
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
            ['code' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'dashboard', 'sort_order' => 1],
            ['code' => 'profile', 'label' => 'Profil', 'route_name' => 'profile.edit', 'sort_order' => 2],
            ['code' => 'owner_reports', 'label' => 'Laporan Owner', 'route_name' => 'owner.reports', 'sort_order' => 3],
            ['code' => 'owner_financials', 'label' => 'Financial Owner', 'route_name' => 'owner.financials', 'sort_order' => 4],
            ['code' => 'admin_import_center', 'label' => 'Import Admin', 'route_name' => 'admin.import.center', 'sort_order' => 5],
            ['code' => 'admin_disputes', 'label' => 'Kritik', 'route_name' => 'admin.disputes', 'sort_order' => 6],
            ['code' => 'admin_monitor', 'label' => 'Monitor Admin', 'route_name' => 'admin.monitor', 'sort_order' => 7],
            ['code' => 'admin_sessions', 'label' => 'Sesi', 'route_name' => 'admin.sessions', 'sort_order' => 8],
            ['code' => 'manager_disputes', 'label' => 'Kritik', 'route_name' => 'manager.disputes', 'sort_order' => 9],
            ['code' => 'manager_monitor', 'label' => 'Monitor', 'route_name' => 'manager.monitor', 'sort_order' => 10],
            ['code' => 'admin_packages', 'label' => 'Paket', 'route_name' => 'admin.modules.packages', 'sort_order' => 11],
            ['code' => 'admin_subjects', 'label' => 'Mapel', 'route_name' => 'admin.modules.subjects', 'sort_order' => 12],
            ['code' => 'admin_users', 'label' => 'User', 'route_name' => 'admin.modules.users', 'sort_order' => 13],
            ['code' => 'student_booking', 'label' => 'Booking', 'route_name' => 'student.booking', 'sort_order' => 14],
            ['code' => 'student_invoices', 'label' => 'Invoices', 'route_name' => 'student.invoices', 'sort_order' => 15],
            ['code' => 'parent_dashboard', 'label' => 'Dashboard Orang Tua', 'route_name' => 'parent.dashboard', 'sort_order' => 16],
            ['code' => 'parent_children', 'label' => 'Hubungkan Anak', 'route_name' => 'parent.children', 'sort_order' => 17],
            ['code' => 'tutor_schedule', 'label' => 'Jadwal Mengajar', 'route_name' => 'tutor.schedule', 'sort_order' => 18],
            ['code' => 'tutor_wallet', 'label' => 'Dompet & Honor', 'route_name' => 'tutor.wallet', 'sort_order' => 19],
            ['code' => 'superadmin_packages', 'label' => 'Paket', 'route_name' => 'superadmin.modules.packages', 'sort_order' => 20],
            ['code' => 'superadmin_subjects', 'label' => 'Mapel', 'route_name' => 'superadmin.modules.subjects', 'sort_order' => 21],
            ['code' => 'superadmin_users', 'label' => 'User', 'route_name' => 'superadmin.modules.users', 'sort_order' => 22],
            ['code' => 'settings', 'label' => 'Setting Web', 'route_name' => 'superadmin.settings', 'sort_order' => 23],
            ['code' => 'menu_access', 'label' => 'Hak Akses Menu', 'route_name' => 'superadmin.menu.access', 'sort_order' => 24],
            ['code' => 'backup_center', 'label' => 'Backup Restore', 'route_name' => 'superadmin.backup.center', 'sort_order' => 25],
            ['code' => 'import_center', 'label' => 'Import Data', 'route_name' => 'superadmin.import.center', 'sort_order' => 26],
        ];

        $activeCodes = collect($menus)->pluck('code')->all();
        MenuItem::whereNotIn('code', $activeCodes)->update(['is_active' => false]);

        foreach ($menus as $menu) {
            $menuItem = MenuItem::updateOrCreate(['code' => $menu['code']], $menu);
            $this->syncDefaultPermission($menuItem->id, $menu['route_name']);
        }
    }

    private function syncDefaultPermission(int $menuId, string $routeName): void
    {
        $roles = ['superadmin', 'owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];
        foreach ($roles as $role) {
            $canView = $this->canViewRoute($role, $routeName);
            MenuPermission::updateOrCreate(
                [
                    'menu_item_id' => $menuId,
                    'role_name' => $role,
                ],
                [
                    'can_view' => $canView,
                    'can_create' => $canView && in_array($role, ['superadmin', 'admin', 'manager'], true),
                    'can_update' => $canView && in_array($role, ['superadmin', 'admin'], true),
                    'can_delete' => $role === 'superadmin',
                ]
            );
        }
    }

    private function canViewRoute(string $role, string $routeName): bool
    {
        return match ($role) {
            'superadmin' => true,
            'owner' => in_array($routeName, ['dashboard', 'profile.edit', 'owner.reports', 'owner.financials'], true),
            'admin' => in_array($routeName, [
                'dashboard',
                'profile.edit',
                'admin.import.center',
                'admin.disputes',
                'admin.monitor',
                'admin.sessions',
                'admin.modules.packages',
                'admin.modules.subjects',
                'admin.modules.users',
            ], true),
            'manager' => in_array($routeName, ['dashboard', 'profile.edit', 'manager.disputes', 'manager.monitor'], true),
            'tentor' => in_array($routeName, ['dashboard', 'profile.edit', 'tutor.schedule', 'tutor.wallet'], true),
            'siswa' => in_array($routeName, ['dashboard', 'profile.edit', 'student.booking', 'student.invoices'], true),
            'orang_tua' => in_array($routeName, ['profile.edit', 'parent.dashboard', 'parent.children'], true),
            default => false,
        };
    }
}
