<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'subject_group_id', 'pass_mark', 'is_compulsory', 'description'];

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function academicProgress()
    {
        return $this->hasMany(AcademicProgress::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
}
