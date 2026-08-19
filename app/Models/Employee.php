<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'id_card',
        'openid',
    ];

    protected $hidden = [
        'remember_token',
    ];

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}
