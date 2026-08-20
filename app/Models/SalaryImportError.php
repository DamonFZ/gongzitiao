<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'name',
        'department',
        'row_data',
        'error_reason',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];
}
