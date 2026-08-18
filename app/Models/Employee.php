<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'id_card',
        'openid',
    ];

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}
