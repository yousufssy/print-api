<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'sys_users';

    protected $fillable = [
        'username', 'password', 'full_name', 'role', 'active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'active' => 'boolean',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
