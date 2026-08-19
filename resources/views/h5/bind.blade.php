@extends('h5.layout')

@section('title', $employee ? '已绑定 - 工资条查询' : '员工绑定 - 工资条查询')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <!-- 顶部 -->
    <div class="bg-blue-600 text-white px-4 py-6 text-center">
        <h1 class="text-xl font-bold">工资条查询</h1>
        <p class="text-sm opacity-80 mt-1">
            {{ $employee ? '当前微信已绑定员工' : '首次使用请先绑定个人信息' }}
        </p>
    </div>

    <div class="px-4 -mt-4">
        @if ($employee)
            {{-- 已绑定状态展示 --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-gray-800">当前微信已绑定员工</p>
                </div>

                <div class="space-y-3 bg-gray-50 rounded-xl p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">姓名</span>
                        <span class="text-sm font-medium text-gray-800">{{ $employee->name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">手机号</span>
                        <span class="text-sm font-medium text-gray-800">{{ $employee->phone }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">身份证号</span>
                        <span class="text-sm font-medium text-gray-800">{{ substr($employee->id_card, 0, 4) }}****{{ substr($employee->id_card, -4) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">部门</span>
                        <span class="text-sm font-medium text-gray-800">
                            {{ $employee->salaries()->latest()->first()?->department ?: '暂无记录' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    <a
                        href="{{ route('h5.salaries') }}"
                        class="block w-full py-3.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 active:bg-blue-800 transition text-center text-base"
                    >
                        查看工资条
                    </a>

                    <form method="POST" action="{{ route('h5.unbind') }}">
                        @csrf
                        <button
                            type="submit"
                            onclick="return confirm('确定要解除绑定吗？解绑后需要重新绑定才能查看工资条。')"
                            class="w-full py-3.5 bg-white text-red-500 font-semibold rounded-lg border-2 border-red-200 hover:bg-red-50 active:bg-red-100 transition text-base"
                        >
                            解除绑定
                        </button>
                    </form>
                </div>
            </div>
        @else
            {{-- 未绑定：显示绑定表单 --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                @if ($errors->has('msg'))
                    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-red-600 text-sm text-center">{{ $errors->first('msg') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('h5.bind') }}" class="space-y-4">
                    @csrf

                    <!-- 姓名 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            姓名 <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="请输入您的姓名"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        />
                    </div>

                    <!-- 手机号 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            手机号 <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="请输入11位手机号"
                            maxlength="11"
                            pattern="^1[3-9]\d{9}$"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        />
                    </div>

                    <!-- 身份证号 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            身份证号 <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="id_card"
                            value="{{ old('id_card') }}"
                            placeholder="请输入18位身份证号"
                            maxlength="18"
                            pattern="(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        />
                    </div>

                    <!-- 提交按钮 -->
                    <button
                        type="submit"
                        class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 active:bg-blue-800 transition text-base"
                    >
                        立即绑定
                    </button>
                </form>
            </div>

            <!-- 底部说明 -->
            <div class="px-6 mt-6 text-center">
                <p class="text-xs text-gray-400 leading-relaxed">
                    请填写与工资表中一致的个人信息<br>
                    信息仅用于身份验证，不会泄露
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
