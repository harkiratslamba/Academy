<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'name', 'email', 'password', 'role', 'teacher_id', 'last_login',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'   => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    public function teacher()
    {
        return $this->hasOne(\Modules\Teacher\Models\Teacher::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'coordinator']);
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }
}
