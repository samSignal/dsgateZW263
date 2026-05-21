<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'class_id', 'subject_id', 'academic_year', 'term',
        'assessment_type', 'marks', 'total_marks', 'percentage', 'grade',
        'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'marks'       => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage'  => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
