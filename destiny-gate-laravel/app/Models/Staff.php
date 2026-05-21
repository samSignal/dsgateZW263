<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'staff_id', 'first_name', 'last_name', 'email',
        'phone', 'department', 'position', 'roles', 'qualifications',
        'employment_date', 'is_active',
    ];

    protected $casts = [
        'roles'           => 'array',
        'employment_date' => 'date',
        'is_active'       => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function teacherComments()
    {
        return $this->hasMany(TeacherComment::class, 'teacher_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'teacher_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
