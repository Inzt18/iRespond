<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('username', 'admin')
            ->orWhere('mobile_number', '09000000000')
            ->orWhere('email', 'admin@irespond.local')
            ->first();

        $payload = [
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Admin',
            'name' => 'System Admin',
            'email' => 'admin@irespond.local',
            'username' => 'admin',
            'password' => Hash::make('Admin123!'),
            'mobile_number' => '09000000000',
            'role' => 'admin',
            'is_active' => true,
        ];

        if ($admin) {
            $admin->fill($payload);
            $admin->save();

            return;
        }

        User::create($payload);
    }
}
