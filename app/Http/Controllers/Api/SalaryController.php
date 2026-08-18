<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * 获取当前员工的所有发薪月份列表
     */
    public function index(Request $request)
    {
        $employee = $request->user();

        $months = Salary::where('employee_id', $employee->id)
            ->select('month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month');

        return response()->json([
            'months' => $months,
        ]);
    }

    /**
     * 获取指定月份的薪资明细
     */
    public function show(Request $request, $month)
    {
        $employee = $request->user();

        $salary = Salary::where('employee_id', $employee->id)
            ->where('month', $month)
            ->first();

        if (!$salary) {
            return response()->json(['message' => '未找到该月份的工资记录'], 404);
        }

        return response()->json([
            'salary' => $salary,
        ]);
    }
}
