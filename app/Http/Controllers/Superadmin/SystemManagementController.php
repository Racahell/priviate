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
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:32',
            'footer_brand' => 'nullable|string|max:120',
            'footer_copyright_text' => 'nullable|string|max:255',
            'footer_version' => 'nullable|string|max:80',
            'footer_navigation' => 'nullable|string',
            'footer_legal' => 'nullable|string',
            'footer_contact_email' => 'nullable|string|max:120',
            'footer_contact_phone' => 'nullable|string|max:64',
            'footer_contact_address' => 'nullable|string|max:200',
            'footer_social' => 'nullable|string',
            'logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $setting = WebSetting::firstOrCreate([]);
        $old = $setting->toArray();
        $extra = is_array($setting->extra) ? $setting->extra : [];
        $extra['footer_config'] = [
            'brand' => $data['footer_brand'] ?? 'Laravel',
            'copyright_text' => $data['footer_copyright_text'] ?? '© 2026 Laravel. All rights reserved.',
            'version' => $data['footer_version'] ?? 'Version 2.3.1',
            'navigation' => $data['footer_navigation'] ?? "Tentang Kami\nKontak\nBlog\nFAQ\nHelp Center",
            'legal' => $data['footer_legal'] ?? "Privacy Policy\nTerms of Service\nCookie Policy",
            'contact_email' => $data['footer_contact_email'] ?? 'support@privtuition.app',
            'contact_phone' => $data['footer_contact_phone'] ?? '+62 21 5550 2026',
            'contact_address' => $data['footer_contact_address'] ?? 'Jakarta, Indonesia',
            'social' => $data['footer_social'] ?? "Instagram\nFacebook\nLinkedIn\nTwitter/X",
        ];
        unset(
            $data['footer_brand'],
            $data['footer_copyright_text'],
            $data['footer_version'],
            $data['footer_navigation'],
            $data['footer_legal'],
            $data['footer_contact_email'],
            $data['footer_contact_phone'],
            $data['footer_contact_address'],
            $data['footer_social']
        );

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
        $roles = ['owner', 'admin', 'tentor', 'siswa', 'orang_tua'];
        $menuCount = MenuItem::where('is_active', true)->count();

        return view('superadmin.menu-access.index', compact('roles', 'menuCount'));
    }

    public function menuAccessRole(string $role)
    {
        $roles = ['owner', 'admin', 'tentor', 'siswa', 'orang_tua'];
        abort_unless(in_array($role, $roles, true), 404);

        $menuItems = MenuItem::where('is_active', true)->orderBy('sort_order')->get();
        $permissions = MenuPermission::where('role_name', $role)->get()->keyBy('menu_item_id');
        $menuGroups = $menuItems
            ->groupBy(fn ($menu) => mb_strtolower(trim((string) $menu->label)))
            ->map(function ($group) use ($permissions) {
                $ids = $group->pluck('id')->values()->all();
                $perms = collect($ids)->map(fn ($id) => $permissions[$id] ?? null)->filter();

                return [
                    'label' => (string) $group->first()->label,
                    'route_names' => $group->pluck('route_name')->filter()->values()->all(),
                    'menu_ids' => $ids,
                    'can_view' => $perms->contains(fn ($p) => (bool) $p->can_view),
                    'can_create' => $perms->contains(fn ($p) => (bool) $p->can_create),
                    'can_update' => $perms->contains(fn ($p) => (bool) $p->can_update),
                    'can_delete' => $perms->contains(fn ($p) => (bool) $p->can_delete),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('superadmin.menu-access.role', compact('role', 'menuGroups'));
    }

    public function updateMenuAccessRole(Request $request, string $role)
    {
        $roles = ['owner', 'admin', 'tentor', 'siswa', 'orang_tua'];
        abort_unless(in_array($role, $roles, true), 404);

        $data = $request->validate([
            'permissions' => 'array',
            'permissions_group' => 'array',
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

            foreach ($data['permissions_group'] ?? [] as $groupPerm) {
                $menuIds = collect($groupPerm['menu_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
                foreach ($menuIds as $menuId) {
                    MenuPermission::updateOrCreate(
                        [
                            'menu_item_id' => $menuId,
                            'role_name' => $role,
                        ],
                        [
                            'can_view' => !empty($groupPerm['can_view']),
                            'can_create' => !empty($groupPerm['can_create']),
                            'can_update' => !empty($groupPerm['can_update']),
                            'can_delete' => !empty($groupPerm['can_delete']),
                        ]
                    );
                }
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

    public function backupCenter(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 15);
        $backups = BackupJob::latest()->paginate($perPage)->withQueryString();
        $routePrefix = request()->routeIs('admin.*') ? 'admin' : 'superadmin';
        return view('superadmin.backup.index', compact('backups', 'routePrefix'));
    }

    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:db',
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
        return back()->with('status', 'Preview partial restore dinonaktifkan untuk backup SQL.');
    }

    public function applyPartialRestore(Request $request, int $backupId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $backup = BackupJob::findOrFail($backupId);
        $restoreJob = $this->backupRestoreService->restoreFromBackupJob($backup, auth()->id(), $request->reason, false);

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
        $restoreJob = $this->backupRestoreService->restoreFromBackupJob($backup, auth()->id(), $request->reason, true);

        $this->auditService->log('DISASTER_RESTORE', $restoreJob);
        $this->discordAlertService->send('Disaster Restore Applied', [
            'backup_id' => $backup->id,
            'restore_job_id' => $restoreJob->id,
            'actor_id' => auth()->id(),
        ], 'critical');

        return back()->with('success', 'Disaster restore berhasil dijalankan.');
    }

    public function downloadBackup(int $backupId)
    {
        $backup = BackupJob::findOrFail($backupId);
        $this->auditService->log('BACKUP_DOWNLOADED', $backup);

        return $this->backupRestoreService->downloadResponse($backup);
    }

    public function uploadRestoreSql(Request $request)
    {
        $validated = $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt',
            'reason' => 'required|string|max:500',
            'wipe_first' => 'nullable|boolean',
        ]);

        $restoreJob = $this->backupRestoreService->restoreFromUpload(
            $request->file('sql_file'),
            auth()->id(),
            $validated['reason'],
            (bool) ($validated['wipe_first'] ?? true)
        );

        $this->auditService->log('SQL_UPLOAD_RESTORE', $restoreJob);

        return back()->with('success', 'File SQL berhasil direstore.');
    }

    public function wipeDatabase(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'confirm_phrase' => 'required|in:DELETE_DATABASE_DATA',
        ]);

        $this->backupRestoreService->wipeDatabaseData(auth()->id());
        $this->auditService->log('DATABASE_WIPED', null, [], [
            'reason' => $validated['reason'],
            'actor_id' => auth()->id(),
        ]);

        return back()->with('success', 'Data database berhasil dikosongkan.');
    }

    public function importCenter(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 15);
        $jobs = ImportJob::latest()->paginate($perPage)->withQueryString();
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
            ['code' => 'owner_reports', 'label' => 'Laporan Keuangan', 'route_name' => 'owner.reports', 'sort_order' => 3],
            ['code' => 'admin_reports', 'label' => 'Laporan Keuangan', 'route_name' => 'admin.reports', 'sort_order' => 4],
            ['code' => 'admin_disputes', 'label' => 'Kritik', 'route_name' => 'admin.disputes', 'sort_order' => 5],
            ['code' => 'admin_monitor', 'label' => 'Monitor Admin', 'route_name' => 'admin.monitor', 'sort_order' => 6],
            ['code' => 'admin_sessions', 'label' => 'Sesi', 'route_name' => 'admin.sessions', 'sort_order' => 7],
            ['code' => 'admin_module_sessions', 'label' => 'Sesi', 'route_name' => 'admin.modules.sessions', 'sort_order' => 8],
            ['code' => 'admin_invoices', 'label' => 'Invoices', 'route_name' => 'admin.invoices', 'sort_order' => 9],
            ['code' => 'admin_packages', 'label' => 'Paket', 'route_name' => 'admin.modules.packages', 'sort_order' => 10],
            ['code' => 'admin_subjects', 'label' => 'Mapel', 'route_name' => 'admin.modules.subjects', 'sort_order' => 11],
            ['code' => 'admin_users', 'label' => 'User', 'route_name' => 'admin.modules.users', 'sort_order' => 12],
            ['code' => 'student_packages', 'label' => 'Paket', 'route_name' => 'student.packages', 'sort_order' => 13],
            ['code' => 'student_invoices', 'label' => 'Invoices', 'route_name' => 'student.invoices', 'sort_order' => 14],
            ['code' => 'student_booking', 'label' => 'Booking', 'route_name' => 'student.booking', 'sort_order' => 15],
            ['code' => 'parent_dashboard', 'label' => 'Dashboard', 'route_name' => 'parent.dashboard', 'sort_order' => 16],
            ['code' => 'parent_children', 'label' => 'Hubungkan Anak', 'route_name' => 'parent.children', 'sort_order' => 17],
            ['code' => 'parent_reschedule', 'label' => 'Reschedule', 'route_name' => 'parent.reschedule', 'sort_order' => 18],
            ['code' => 'parent_disputes', 'label' => 'Kritik', 'route_name' => 'parent.disputes', 'sort_order' => 19],
            ['code' => 'tutor_schedule', 'label' => 'Jadwal Mengajar', 'route_name' => 'tutor.schedule', 'sort_order' => 20],
            ['code' => 'tutor_wallet', 'label' => 'Dompet & Honor', 'route_name' => 'tutor.wallet', 'sort_order' => 21],
            ['code' => 'superadmin_packages', 'label' => 'Paket', 'route_name' => 'superadmin.modules.packages', 'sort_order' => 22],
            ['code' => 'superadmin_subjects', 'label' => 'Mapel', 'route_name' => 'superadmin.modules.subjects', 'sort_order' => 23],
            ['code' => 'superadmin_sessions', 'label' => 'Sesi', 'route_name' => 'superadmin.modules.sessions', 'sort_order' => 24],
            ['code' => 'superadmin_users', 'label' => 'User', 'route_name' => 'superadmin.modules.users', 'sort_order' => 25],
            ['code' => 'admin_settings', 'label' => 'Setting Web', 'route_name' => 'admin.settings', 'sort_order' => 26],
            ['code' => 'admin_activity_logs', 'label' => 'Activity Log', 'route_name' => 'admin.activity.logs', 'sort_order' => 27],
            ['code' => 'settings', 'label' => 'Setting Web', 'route_name' => 'superadmin.settings', 'sort_order' => 28],
            ['code' => 'menu_access', 'label' => 'Hak Akses Menu', 'route_name' => 'superadmin.menu.access', 'sort_order' => 29],
            ['code' => 'backup_center', 'label' => 'Backup Restore', 'route_name' => 'superadmin.backup.center', 'sort_order' => 30],
            ['code' => 'import_center', 'label' => 'Import Data', 'route_name' => 'superadmin.import.center', 'sort_order' => 31],
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
        $roles = ['superadmin', 'owner', 'admin', 'tentor', 'siswa', 'orang_tua'];
        foreach ($roles as $role) {
            $canView = $this->canViewRoute($role, $routeName);
            MenuPermission::updateOrCreate(
                [
                    'menu_item_id' => $menuId,
                    'role_name' => $role,
                ],
                [
                    'can_view' => $canView,
                    'can_create' => $canView && in_array($role, ['superadmin', 'admin'], true),
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
            'owner' => in_array($routeName, ['dashboard', 'profile.edit', 'owner.reports'], true),
            'admin' => in_array($routeName, [
                'dashboard',
                'profile.edit',
                'admin.disputes',
                'admin.monitor',
                'admin.reports',
                'admin.sessions',
                'admin.modules.sessions',
                'admin.invoices',
                'admin.settings',
                'admin.activity.logs',
                'admin.modules.packages',
                'admin.modules.subjects',
                'admin.modules.users',
            ], true),
            'tentor' => in_array($routeName, ['dashboard', 'profile.edit', 'tutor.schedule', 'tutor.wallet'], true),
            'siswa' => in_array($routeName, ['dashboard', 'profile.edit', 'student.packages', 'student.invoices', 'student.booking'], true),
            'orang_tua' => in_array($routeName, ['profile.edit', 'parent.dashboard', 'parent.children', 'parent.reschedule', 'parent.disputes'], true),
            default => false,
        };
    }

    private function resolvePerPage(Request $request, int $default = 15): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->query('per_page', $default);
        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
