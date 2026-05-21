<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'document_type', 'file_name', 'file_url', 'file_key', 'uploaded_at',
    ];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
