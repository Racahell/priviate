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
            ['email' => 'a@g'],
            [
                'name' => 'a',
                'password' => 'a',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        // 2. Admin
        $admin = User::updateOrCreate(
            ['email' => 'b@g'],
            [
                'name' => 'b',
                'password' => 'b',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        // 3. Owner
        $owner = User::updateOrCreate(
            ['email' => 'c@g'],
            [
                'name' => 'c',
                'password' => 'c',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $owner->syncRoles(['owner']);

        // 4. Tentor
        $tentor = User::updateOrCreate(
            ['email' => 'e@g'],
            [
                'name' => 'e',
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
            ['email' => 'f@g'],
            [
                'name' => 'f',
                'password' => 'f',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $siswa->syncRoles(['siswa']);

        // 6. Orang Tua
        $orangTua = User::updateOrCreate(
            ['email' => 'g@g'],
            [
                'name' => 'g',
                'password' => 'g',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $orangTua->syncRoles(['orang_tua']);
    }
}
