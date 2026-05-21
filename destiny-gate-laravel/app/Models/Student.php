<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'admission_number', 'first_name', 'last_name', 'email',
        'date_of_birth', 'gender', 'class_id', 'admission_date', 'status',
        'blood_type', 'allergies', 'medical_conditions',
    ];

    protected $casts = [
        'date_of_birth'  => 'date',
        'admission_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function fees()
    {
        return $this->hasMany(StudentFee::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function academicProgress()
    {
        return $this->hasMany(AcademicProgress::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function behaviourRecords()
    {
        return $this->hasMany(BehaviourRecord::class);
    }

    public function purchases()
    {
        return $this->hasMany(StudentPurchase::class);
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function teacherComments()
    {
        return $this->hasMany(TeacherComment::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
