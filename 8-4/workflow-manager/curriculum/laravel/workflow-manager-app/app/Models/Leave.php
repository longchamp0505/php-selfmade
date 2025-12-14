<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'day_of_week', 'leave_type', 'note',
        'is_submitted', 'is_approved_by_admins', 'admins_approved_at',
        'is_rejection', 'rejection_comment'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
