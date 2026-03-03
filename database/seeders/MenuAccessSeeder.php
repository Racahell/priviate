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
            ['code' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'home', 'sort_order' => 1],
            ['code' => 'student_dashboard', 'label' => 'Dashboard Siswa', 'route_name' => 'student.dashboard', 'sort_order' => 2],
            ['code' => 'tutor_dashboard', 'label' => 'Dashboard Guru', 'route_name' => 'tutor.dashboard', 'sort_order' => 3],
            ['code' => 'admin_dashboard', 'label' => 'Dashboard Admin', 'route_name' => 'admin.dashboard', 'sort_order' => 4],
            ['code' => 'manager_dashboard', 'label' => 'Dashboard Manager', 'route_name' => 'manager.dashboard', 'sort_order' => 5],
            ['code' => 'parent_dashboard', 'label' => 'Dashboard Orang Tua', 'route_name' => 'parent.dashboard', 'sort_order' => 6],
            ['code' => 'owner_dashboard', 'label' => 'Dashboard Owner', 'route_name' => 'owner.dashboard', 'sort_order' => 7],
            ['code' => 'owner_reports', 'label' => 'Laporan Owner', 'route_name' => 'owner.reports', 'sort_order' => 8],
            ['code' => 'superadmin_dashboard', 'label' => 'Dashboard Superadmin', 'route_name' => 'superadmin.dashboard', 'sort_order' => 9],
            ['code' => 'settings', 'label' => 'Setting Web', 'route_name' => 'superadmin.settings', 'sort_order' => 10],
            ['code' => 'menu_access', 'label' => 'Hak Akses Menu', 'route_name' => 'superadmin.menu.access', 'sort_order' => 11],
            ['code' => 'restore_center', 'label' => 'Restore Data', 'route_name' => 'superadmin.restore.center', 'sort_order' => 12],
            ['code' => 'backup_center', 'label' => 'Backup Restore', 'route_name' => 'superadmin.backup.center', 'sort_order' => 13],
            ['code' => 'import_center', 'label' => 'Import Data', 'route_name' => 'superadmin.import.center', 'sort_order' => 14],
            ['code' => 'admin_backup_center', 'label' => 'Backup Admin', 'route_name' => 'admin.backup.center', 'sort_order' => 15],
            ['code' => 'admin_import_center', 'label' => 'Import Admin', 'route_name' => 'admin.import.center', 'sort_order' => 16],
        ];

        foreach ($menus as $menuData) {
            $menu = MenuItem::updateOrCreate(['code' => $menuData['code']], $menuData);

            $this->seedPermissionsForMenu($menu->id, $menuData['route_name']);
        }
    }

    private function seedPermissionsForMenu(int $menuId, string $routeName): void
    {
        $roles = ['superadmin', 'owner', 'admin', 'manager', 'tentor', 'siswa', 'orang_tua'];

        foreach ($roles as $role) {
            $allow = $this->canViewRoute($role, $routeName);
            MenuPermission::updateOrCreate(
                ['menu_item_id' => $menuId, 'role_name' => $role],
                [
                    'can_view' => $allow,
                    'can_create' => $allow && in_array($role, ['superadmin', 'admin', 'manager'], true),
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
            'owner' => in_array($routeName, ['home', 'owner.dashboard', 'owner.reports'], true),
            'admin' => in_array($routeName, ['home', 'admin.dashboard', 'admin.backup.center', 'admin.import.center'], true),
            'manager' => in_array($routeName, ['home', 'manager.dashboard'], true),
            'tentor' => in_array($routeName, ['home', 'tutor.dashboard'], true),
            'siswa' => in_array($routeName, ['home', 'student.dashboard'], true),
            'orang_tua' => in_array($routeName, ['home', 'parent.dashboard'], true),
            default => false,
        };
    }
}
