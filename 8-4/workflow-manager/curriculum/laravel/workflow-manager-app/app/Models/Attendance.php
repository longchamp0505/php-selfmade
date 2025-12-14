<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'day_of_week', 'category', 'is_other_company_work',
        'clock_in', 'start_time', 'clock_out', 'end_time', 'break_time',
        'remarks', 'is_submitted', 'is_approved_by_clients', 'clients_approved_at',
        'is_approved_by_admins', 'admins_approved_at', 'rejection', 'rejection_comment'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
