@extends('h5.layout')

@section('title', $salary->month . ' 工资明细')

@php
function formatMoney($val) {
    $num = floatval($val);
    return number_format($num, 2);
}
@endphp

@section('content')
<div class="min-h-screen bg-gray-100 pb-8">
    <!-- 顶部导航 -->
    <div class="bg-blue-600 text-white px-4 py-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('h5.salaries') }}" class="text-sm opacity-80">
                ← 返回
            </a>
            <h1 class="text-lg font-semibold">工资明细</h1>
            <div class="w-10"></div>
        </div>
    </div>

    <!-- 工资凭条卡片 -->
    <div class="px-4 mt-4">
        <!-- 卡片头部 -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-t-2xl px-6 py-5 text-white text-center">
            <p class="text-sm opacity-80 mb-1">{{ $salary->month }} 工资条</p>
            <p class="text-xs opacity-60">{{ $salary->department ?: '未知部门' }} · {{ $salary->position ?: '未知岗位' }}</p>
        </div>

        <!-- 应发项目 -->
        <div class="bg-white px-6 py-4">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                应发项目
            </h3>
            <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">基本工资</span>
                    <span class="text-sm font-medium text-gray-800">¥{{ formatMoney($salary->base_salary) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">岗位津贴</span>
                    <span class="text-sm font-medium text-gray-800">¥{{ formatMoney($salary->position_allowance) }}</span>
                </div>
                @if ($salary->overtime_pay)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">加班费</span>
                    <span class="text-sm font-medium text-gray-800">¥{{ formatMoney($salary->overtime_pay) }}</span>
                </div>
                @endif
                <div class="border-t border-dashed border-gray-200 pt-2.5 flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700">应发工资</span>
                    <span class="text-sm font-semibold text-gray-800">¥{{ formatMoney($salary->payable_salary) }}</span>
                </div>
            </div>
        </div>

        <!-- 扣款项目 -->
        <div class="bg-white px-6 py-4 border-t border-gray-100">
            <h3 class="text-xs font-semibold text-red-400 uppercase tracking-wider mb-3">
                扣款项目
            </h3>
            <div class="space-y-2.5">
                @if ($salary->leave_days)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">请假天数 ({{ $salary->leave_days }}天)</span>
                    <span class="text-sm font-medium text-red-500">-¥{{ formatMoney($salary->deducted_leave_pay ?: 0) }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">社保</span>
                    <span class="text-sm font-medium text-red-500">-¥{{ formatMoney($salary->social_security) }}</span>
                </div>
                @if ($salary->income_tax)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">个人所得税</span>
                    <span class="text-sm font-medium text-red-500">-¥{{ formatMoney($salary->income_tax) }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- 实收工资 -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-b-2xl px-6 py-5 border-t-2 border-green-200">
            <div class="flex justify-between items-center">
                <span class="text-base font-bold text-gray-800">实收工资</span>
                <span class="text-2xl font-black text-green-600">
                    ¥{{ formatMoney($salary->net_salary) }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
