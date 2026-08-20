@extends('h5.layout')

@section('title', '我的工资条')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- 顶部 -->
    <div class="bg-blue-600 text-white px-4 py-5 text-center">
        <h1 class="text-xl font-bold">我的工资条</h1>
        <p class="text-sm opacity-80 mt-1">请选择月份查看详情</p>
    </div>

    <!-- 设置入口 -->
    <div class="px-4 mt-3">
        <a href="{{ route('h5.bind') }}" class="flex items-center justify-between bg-white rounded-lg shadow-sm px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 transition">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                账号与绑定设置
            </span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>

    <!-- 月份列表 -->
    <div class="px-4 mt-3 pb-8">
        @if ($salaries->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <p>暂无工资记录</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($salaries as $item)
                    <a
                        href="{{ route('h5.salary.detail', ['month' => $item->month]) }}"
                        class="block bg-white rounded-xl shadow-sm p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $item->month }}</p>
                                    <p class="text-xs text-gray-400">点击查看工资明细</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($item->status === 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        未读
                                    </span>
                                @elseif ($item->status === 1)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                        未签名
                                    </span>
                                @elseif ($item->status === 2)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        已确认
                                    </span>
                                @endif
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
