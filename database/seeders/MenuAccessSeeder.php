<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\MenuPermission;
use Illuminate\Database\Seeder;

class MenuAccessSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['code' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'dashboard', 'sort_order' => 1],
            ['code' => 'profile', 'label' => 'Profil', 'route_name' => 'profile.edit', 'sort_order' => 2],
            ['code' => 'student_booking', 'label' => 'Booking', 'route_name' => 'student.booking', 'sort_order' => 3],
            ['code' => 'student_invoices', 'label' => 'Invoices', 'route_name' => 'student.invoices', 'sort_order' => 4],
            ['code' => 'parent_dashboard', 'label' => 'Dashboard Orang Tua', 'route_name' => 'parent.dashboard', 'sort_order' => 5],
            ['code' => 'parent_children', 'label' => 'Hubungkan Anak', 'route_name' => 'parent.children', 'sort_order' => 6],
            ['code' => 'tutor_schedule', 'label' => 'Jadwal Mengajar', 'route_name' => 'tutor.schedule', 'sort_order' => 7],
            ['code' => 'tutor_wallet', 'label' => 'Dompet & Honor', 'route_name' => 'tutor.wallet', 'sort_order' => 8],
            ['code' => 'admin_disputes', 'label' => 'Disputes Admin', 'route_name' => 'admin.disputes', 'sort_order' => 9],
            ['code' => 'admin_monitor', 'label' => 'Monitor Admin', 'route_name' => 'admin.monitor', 'sort_order' => 10],
            ['code' => 'admin_packages', 'label' => 'Paket', 'route_name' => 'admin.modules.packages', 'sort_order' => 11],
            ['code' => 'admin_subjects', 'label' => 'Mapel', 'route_name' => 'admin.modules.subjects', 'sort_order' => 12],
            ['code' => 'admin_items', 'label' => 'Item', 'route_name' => 'admin.modules.items', 'sort_order' => 13],
            ['code' => 'admin_users', 'label' => 'User', 'route_name' => 'admin.modules.users', 'sort_order' => 14],
            ['code' => 'admin_activity_logs', 'label' => 'Activity Log', 'route_name' => 'admin.activity.logs', 'sort_order' => 15],
            ['code' => 'owner_reports', 'label' => 'Laporan Owner', 'route_name' => 'owner.reports', 'sort_order' => 16],
            ['code' => 'owner_financials', 'label' => 'Financial Owner', 'route_name' => 'owner.financials', 'sort_order' => 17],
            ['code' => 'superadmin_packages', 'label' => 'Paket', 'route_name' => 'superadmin.modules.packages', 'sort_order' => 18],
            ['code' => 'superadmin_subjects', 'label' => 'Mapel', 'route_name' => 'superadmin.modules.subjects', 'sort_order' => 19],
            ['code' => 'superadmin_items', 'label' => 'Item', 'route_name' => 'superadmin.modules.items', 'sort_order' => 20],
            ['code' => 'superadmin_users', 'label' => 'User', 'route_name' => 'superadmin.modules.users', 'sort_order' => 21],
            ['code' => 'settings', 'label' => 'Setting Web', 'route_name' => 'superadmin.settings', 'sort_order' => 22],
            ['code' => 'menu_access', 'label' => 'Hak Akses Menu', 'route_name' => 'superadmin.menu.access', 'sort_order' => 23],
            ['code' => 'backup_center', 'label' => 'Backup Restore', 'route_name' => 'superadmin.backup.center', 'sort_order' => 24],
            ['code' => 'import_center', 'label' => 'Import Data', 'route_name' => 'superadmin.import.center', 'sort_order' => 25],
        ];

        $activeCodes = collect($menus)->pluck('code')->all();
        MenuItem::whereNotIn('code', $activeCodes)->update(['is_active' => false]);

        foreach ($menus as $menuData) {
            $menu = MenuItem::updateOrCreate(['code' => $menuData['code']], $menuData);

            $this->seedPermissionsForMenu($menu->id, $menuData['route_name']);
        }
    }

    private function seedPermissionsForMenu(int $menuId, string $routeName): void
    {
        $roles = ['superadmin', 'owner', 'admin', 'tentor', 'siswa', 'orang_tua'];

        foreach ($roles as $role) {
            $allow = $this->canViewRoute($role, $routeName);
            MenuPermission::updateOrCreate(
                ['menu_item_id' => $menuId, 'role_name' => $role],
                [
                    'can_view' => $allow,
                    'can_create' => $allow && in_array($role, ['superadmin', 'admin'], true),
                    'can_update' => $allow && in_array($role, ['superadmin', 'admin'], true),
                    'can_delete' => $allow && $role === 'superadmin',
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
                'admin.disputes',
                'admin.monitor',
                'admin.modules.packages',
                'admin.modules.subjects',
                'admin.modules.items',
                'admin.modules.users',
                'admin.activity.logs',
            ], true),
            'tentor' => in_array($routeName, ['dashboard', 'profile.edit', 'tutor.schedule', 'tutor.wallet'], true),
            'siswa' => in_array($routeName, ['dashboard', 'profile.edit', 'student.booking', 'student.invoices'], true),
            'orang_tua' => in_array($routeName, ['profile.edit', 'parent.dashboard', 'parent.children'], true),
            default => false,
        };
    }
}
