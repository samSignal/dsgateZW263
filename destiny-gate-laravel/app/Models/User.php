<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'login_method',
        'role', 'is_active', 'last_signed_in',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_signed_in'    => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isHeadmaster(): bool   { return $this->role === 'headmaster'; }
    public function isTeacher(): bool      { return $this->role === 'teacher'; }
    public function isBursar(): bool       { return $this->role === 'bursar'; }
    public function isParent(): bool       { return $this->role === 'parent'; }
    public function isStudent(): bool      { return $this->role === 'student'; }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'headmaster', 'teacher', 'bursar']);
    }
}
