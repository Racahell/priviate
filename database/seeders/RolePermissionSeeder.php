<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset Cached Roles/Permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Permissions (Based on User Journey Matrix)
        $permissions = [
            'view_landing_page',
            'book_tentor',
            'start_session', // GPS Guarded
            'manage_wallet',
            'resolve_dispute',
            'view_financial_reports',
            'configure_whitelabel',
            'manage_rbac',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Define Roles and Assign Permissions

        // Role: Siswa (The Customer)
        $roleSiswa = Role::firstOrCreate(['name' => 'siswa']);
        $roleSiswa->givePermissionTo(['view_landing_page', 'book_tentor']);

        // Role: Tentor (The Provider)
        $roleTentor = Role::firstOrCreate(['name' => 'tentor']);
        $roleTentor->givePermissionTo(['view_landing_page', 'start_session', 'manage_wallet']);

        // Role: Admin (Operational)
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(['view_landing_page', 'resolve_dispute']);

        // Role: Owner (Director/Stakeholder)
        $roleOwner = Role::firstOrCreate(['name' => 'owner']);
        $roleOwner->givePermissionTo(['view_landing_page', 'view_financial_reports']);

        // Role: Superadmin (God Mode)
        $roleSuperadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $roleSuperadmin->givePermissionTo(Permission::all()); // All permissions
    }
}
