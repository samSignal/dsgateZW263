<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolNotification extends Model
{
    use HasFactory;

    protected $table = 'school_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'related_student_id', 'related_record_id', 'is_read', 'email_sent',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'email_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
