<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id', 'term_id', 'form_id', 'category_id',
        'application_number', 'first_name', 'middle_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'id_number',
        'student_address',
        'previous_school', 'former_grade', 'reason_for_joining',
        'doc_student_id_path', 'doc_results_path', 'doc_parent_id_path', 'doc_transfer_letter_path',
        'guardian_name', 'guardian_email', 'guardian_phone',
        'guardian2_name', 'guardian2_email', 'guardian2_phone',
        'guardian3_name', 'guardian3_email', 'guardian3_phone',
        'intended_class', 'academic_year', 'status', 'rejection_reason',
        'offer_letter_token', 'offer_letter_version', 'offer_letter_expires_at',
        'offer_accepted_at',
        'is_draft', 'last_saved_step', 'resume_token',
        'processed_by', 'processed_at', 'enrolled_student_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'processed_at'  => 'datetime',
        'is_draft'      => 'boolean',
        'offer_letter_expires_at' => 'datetime',
        'offer_accepted_at' => 'datetime',
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
