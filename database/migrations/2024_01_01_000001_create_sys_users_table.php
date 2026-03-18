<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 80)->unique();
            $table->string('password');
            $table->string('full_name', 150);
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Seed default users
        DB::table('sys_users')->insert([
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

        Schema::create('sys_login_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username', 80)->nullable();
            $table->string('ip', 45)->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_login_log');
        Schema::dropIfExists('sys_users');
    }
};
