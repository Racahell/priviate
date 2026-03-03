<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password'); // Default password for all

        // 1. Superadmin
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superadmin->assignRole('superadmin');

        // 2. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Cabang',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // 3. Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Business Owner',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $owner->assignRole('owner');

        // 4. Manager Operasional
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager Operasional',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $manager->assignRole('manager');

        // 5. Tentor
        $tentor = User::firstOrCreate(
            ['email' => 'tentor@example.com'],
            [
                'name' => 'Tentor Profesional',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $tentor->assignRole('tentor');
        
        // Create Wallet for Tentor
        if (!$tentor->wallet) {
            Wallet::create([
                'user_id' => $tentor->id,
                'balance' => 1500000,
                'held_balance' => 200000,
                'is_active' => true,
            ]);
        }

        // 6. Siswa
        $siswa = User::firstOrCreate(
            ['email' => 'siswa@example.com'],
            [
                'name' => 'Siswa Rajin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa->assignRole('siswa');

        // 7. Orang Tua
        $orangTua = User::firstOrCreate(
            ['email' => 'orangtua@example.com'],
            [
                'name' => 'Orang Tua',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $orangTua->assignRole('orang_tua');
    }
}
