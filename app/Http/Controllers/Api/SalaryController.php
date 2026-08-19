<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    /**
     * 工资月份列表
     */
    public function index(Request $request)
    {
        $employee = Auth::guard('employees')->user();

        $salaries = Salary::where('employee_id', $employee->id)
            ->select('month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->get();

        return view('h5.list', compact('salaries'));
    }

    /**
     * 指定月份工资明细
     */
    public function show(Request $request, $month)
    {
        $employee = Auth::guard('employees')->user();

        $salary = Salary::where('employee_id', $employee->id)
            ->where('month', $month)
            ->first();

        if (!$salary) {
            abort(404, '未找到该月份的工资记录');
        }

        return view('h5.detail', compact('salary'));
    }
}
