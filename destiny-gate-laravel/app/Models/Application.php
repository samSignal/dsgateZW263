<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'guardian_name', 'guardian_email', 'guardian_phone',
        'intended_class', 'academic_year', 'status', 'rejection_reason',
        'processed_by', 'processed_at', 'enrolled_student_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'processed_at'  => 'datetime',
    ];

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function enrolledStudent()
    {
        return $this->belongsTo(Student::class, 'enrolled_student_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
