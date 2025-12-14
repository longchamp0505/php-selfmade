<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false; // PKがVARCHARのため
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'password',
        'name',
        'name_kana',
        'hire_date',
        'retire_date',
        'employment_type',
        'contract_type',
        'workplace',
        'scheduled_days',
        'client_id',
        'department', // ← 追加
    ];


    protected $hidden = ['password'];

    // リレーション
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function paidLeave()
    {
        return $this->hasOne(PaidLeave::class);
    }
}
