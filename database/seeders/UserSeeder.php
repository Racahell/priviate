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

        // 1. Superadmin (satu@g)
        $superadmin = User::firstOrCreate(
            ['email' => 'satu@g'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superadmin->assignRole('superadmin');

        // 2. Admin (dua@g)
        $admin = User::firstOrCreate(
            ['email' => 'dua@g'],
            [
                'name' => 'Admin Cabang',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // 3. Owner (tiga@g)
        $owner = User::firstOrCreate(
            ['email' => 'tiga@g'],
            [
                'name' => 'Business Owner',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $owner->assignRole('owner');

        // 4. Tentor (empat@g)
        $tentor = User::firstOrCreate(
            ['email' => 'empat@g'],
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

        // 5. Siswa (lima@g)
        $siswa = User::firstOrCreate(
            ['email' => 'lima@g'],
            [
                'name' => 'Siswa Rajin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa->assignRole('siswa');
        
        // Create Wallet/Profile for Siswa if needed (optional)
    }
}
