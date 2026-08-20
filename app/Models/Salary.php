<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'department',
        'position',
        'base_salary',
        'position_allowance',
        'overtime_pay',
        'leave_days',
        'deducted_leave_pay',
        'payable_salary',
        'social_security',
        'income_tax',
        'net_salary',
        'status',
        'signature_path',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
