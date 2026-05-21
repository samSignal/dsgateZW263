<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'purchase_date', 'item_type', 'description',
        'quantity', 'unit_price', 'total_price', 'recorded_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
