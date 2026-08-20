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

    <!-- 状态标识 -->
    <div class="px-4 mt-3">
        @if ($salary->status === 0)
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                未读
            </div>
        @elseif ($salary->status === 1)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                已读 · 待签名确认
            </div>
        @elseif ($salary->status === 2)
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                已签名确认
            </div>
        @endif
    </div>

    <!-- 工资凭条卡片 -->
    <div class="px-4 mt-3">
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

    <!-- 签名区域 -->
    <div class="px-4 mt-4">
        @if ($salary->status === 2)
            <!-- 已签名：显示签名图片 -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-xs text-gray-400 mb-3 text-center">员工签名确认</p>
                <div class="flex justify-center items-center bg-gray-50 rounded-lg p-4 border border-dashed border-gray-200">
                    <img src="{{ asset('storage/' . $salary->signature_path) }}" alt="员工签名" class="max-h-32" />
                </div>
                <div class="mt-3 text-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        已签名确认
                    </span>
                </div>
            </div>
        @else
            <!-- 未签名：显示签名按钮 -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-xs text-gray-400 mb-2 text-center">请仔细核对工资信息，确认无误后签名</p>
                <button id="open-signature-btn" class="w-full bg-blue-600 text-white font-medium py-3 rounded-xl hover:bg-blue-700 active:bg-blue-800 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    签名并确认
                </button>
            </div>
        @endif
    </div>

    <!-- 签名弹窗 -->
    <div id="signature-modal" class="fixed inset-0 z-50 bg-white hidden flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-800">手写签名</h3>
            <button id="close-signature-btn" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 p-4">
            <div class="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-white h-full">
                <canvas id="signature-pad" class="w-full h-full"></canvas>
            </div>
        </div>
        <div class="px-4 py-4 border-t border-gray-200 flex gap-3">
            <button id="clear-signature-btn" class="flex-1 py-3 rounded-xl border border-gray-300 text-gray-600 font-medium hover:bg-gray-50 active:bg-gray-100 transition">
                清除重写
            </button>
            <button id="submit-signature-btn" class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 active:bg-blue-800 transition">
                提交确认
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/signature_pad.umd.min.js') }}"></script>
<script>
(function() {
    const salaryId = {{ $salary->id }};
    const signUrl = "{{ route('h5.salary.sign', ['id' => $salary->id]) }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const modal = document.getElementById('signature-modal');
    const openBtn = document.getElementById('open-signature-btn');
    const closeBtn = document.getElementById('close-signature-btn');
    const clearBtn = document.getElementById('clear-signature-btn');
    const submitBtn = document.getElementById('submit-signature-btn');
    const canvas = document.getElementById('signature-pad');

    if (!canvas) return;

    let signaturePad = null;

    function initSignaturePad() {
        // 适配高分屏
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();

        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;

        const ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);

        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 2.5,
            dotSize: 2,
        });
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // 延迟初始化，确保 DOM 渲染完成
        setTimeout(function() {
            initSignaturePad();
        }, 100);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (signaturePad) {
            signaturePad.clear();
        }
    }

    function submitSignature() {
        if (!signaturePad || signaturePad.isEmpty()) {
            alert('请先签名再提交');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = '提交中...';

        const signatureData = signaturePad.toDataURL('image/png');

        fetch(signUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ signature: signatureData }),
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('签名确认成功！');
                window.location.reload();
            } else {
                alert(data.message || '签名提交失败，请重试');
                submitBtn.disabled = false;
                submitBtn.textContent = '提交确认';
            }
        })
        .catch(function(error) {
            alert('网络错误，请重试');
            submitBtn.disabled = false;
            submitBtn.textContent = '提交确认';
        });
    }

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (signaturePad) {
                signaturePad.clear();
            }
        });
    }
    if (submitBtn) {
        submitBtn.addEventListener('click', submitSignature);
    }
})();
</script>
@endpush
