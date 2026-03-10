<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Superadmin
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'superadmin',
                'password' => 'a',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        // 2. Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'password' => 'b',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        // 3. Owner
        $owner = User::updateOrCreate(
            ['email' => 'budi.santoso@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'password' => 'c',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $owner->syncRoles(['owner']);

        // 4. Tentor
        $tentor = User::updateOrCreate(
            ['email' => 'rina.maharani@gmail.com'],
            [
                'name' => 'Rina Maharani',
                'password' => 'e',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $tentor->syncRoles(['tentor']);
        
        // Create Wallet for Tentor
        if (!$tentor->wallet) {
            Wallet::create([
                'user_id' => $tentor->id,
                'balance' => 1500000,
                'held_balance' => 200000,
                'is_active' => true,
            ]);
        }

        // 5. Siswa
        $siswa = User::updateOrCreate(
            ['email' => 'alya.putri@gmail.com'],
            [
                'name' => 'Alya Putri',
                'password' => 'f',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa->syncRoles(['siswa']);

        // 6. Orang Tua
        $orangTua = User::updateOrCreate(
            ['email' => 'dewi.lestari@gmail.com'],
            [
                'name' => 'Dewi Lestari',
                'password' => 'g',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $orangTua->syncRoles(['orang_tua']);
    }
}
