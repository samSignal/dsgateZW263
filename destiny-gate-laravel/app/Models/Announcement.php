<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'content', 'audience', 'target_class_id', 'created_by', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetClass()
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }
}
