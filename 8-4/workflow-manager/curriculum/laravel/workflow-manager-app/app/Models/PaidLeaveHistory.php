<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidLeaveHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'paid_leave_id',
        'user_id',
        'remaining_days',
        'granted_date',
        'current_year_taken',
        'last_year_granted',
        'last_year_carried',
        'last_year_taken',
        'two_years_ago_granted',
        'two_years_ago_carried',
        'next_expiration_date',
        'expiration_days',
    ];

    public function paidLeave()
    {
        return $this->belongsTo(PaidLeave::class);
    }
}
