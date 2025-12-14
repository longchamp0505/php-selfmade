<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Client extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'password',
        'company_name',
        'department_name',
        'contact',
        'approver1_name',
        'approver1_email',
        'approver2_name',
        'approver2_email',
        'approver3_name',
        'approver3_email',
    ];

    protected $hidden = ['password'];

    public function users()
    {
        return $this->hasMany(User::class, 'client_id', 'id');
    }
}
