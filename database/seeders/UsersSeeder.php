<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sys_users')->insertOrIgnore([
            [
                'username'   => 'admin',
                'password'   => Hash::make('admin123'),
                'full_name'  => 'مدير النظام',
                'role'       => 'admin',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username'   => 'user1',
                'password'   => Hash::make('user123'),
                'full_name'  => 'مستخدم عادي',
                'role'       => 'user',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
