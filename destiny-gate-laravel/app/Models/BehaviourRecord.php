<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'class_id', 'issue_date', 'issue_type', 'severity',
        'description', 'action', 'parent_meeting_scheduled', 'parent_meeting_date',
        'parent_meeting_notes', 'headmaster_review', 'reviewed_at', 'recorded_by',
    ];

    protected $casts = [
        'issue_date'               => 'date',
        'parent_meeting_date'      => 'date',
        'reviewed_at'              => 'datetime',
        'parent_meeting_scheduled' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
