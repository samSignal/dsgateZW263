<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'student_id', 'first_name', 'last_name', 'email',
        'phone', 'relationship', 'address', 'city', 'country',
        'occupation', 'is_primary_contact',
    ];

    protected $casts = ['is_primary_contact' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
