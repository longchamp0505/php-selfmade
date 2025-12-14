<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'day_of_week', 'category', 'amount', 'payee', 'purpose',
        'receipt_image', 'invoice_number', 'is_submitted', 'is_approved_by_admins',
        'admins_approved_at', 'is_rejection', 'rejection_comment'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
