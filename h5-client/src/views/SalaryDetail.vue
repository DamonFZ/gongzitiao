<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import request from '../utils/request'

const route = useRoute()
const router = useRouter()
const salary = ref(null)
const loading = ref(true)

function formatMoney(val) {
  const num = parseFloat(val)
  if (isNaN(num)) return '0.00'
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

onMounted(async () => {
  const month = route.query.month
  if (!month) {
    showToast('请选择月份')
    router.push('/list')
    return
  }

  try {
    const res = await request.get(`/api/h5/salary/${month}`)
    salary.value = res.data.salary
  } catch (err) {
    if (err.response?.status === 404) {
      showToast('未找到该月份的工资记录')
    } else if (err.response?.status === 401) {
      router.push('/bind')
    } else {
      showToast('加载失败')
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 pb-8">
    <!-- 顶部导航 -->
    <div class="bg-blue-600 text-white px-4 py-4">
      <div class="flex items-center justify-between">
        <button @click="router.push('/list')" class="text-sm opacity-80">
          ← 返回
        </button>
        <h1 class="text-lg font-semibold">工资明细</h1>
        <div class="w-10"></div>
      </div>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="text-center py-20 text-gray-400">
      <p>加载中...</p>
    </div>

    <!-- 工资凭条卡片 -->
    <div v-else-if="salary" class="px-4 mt-4">
      <!-- 卡片头部 -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-t-2xl px-6 py-5 text-white text-center">
        <p class="text-sm opacity-80 mb-1">{{ salary.month }} 工资条</p>
        <p class="text-xs opacity-60">{{ salary.department || '未知部门' }} · {{ salary.position || '未知岗位' }}</p>
      </div>

      <!-- 应发项目 -->
      <div class="bg-white px-6 py-4">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
          应发项目
        </h3>
        <div class="space-y-2.5">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">基本工资</span>
            <span class="text-sm font-medium text-gray-800">¥{{ formatMoney(salary.base_salary) }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">岗位津贴</span>
            <span class="text-sm font-medium text-gray-800">¥{{ formatMoney(salary.position_allowance) }}</span>
          </div>
          <div v-if="salary.overtime_pay" class="flex justify-between items-center">
            <span class="text-sm text-gray-600">加班费</span>
            <span class="text-sm font-medium text-gray-800">¥{{ formatMoney(salary.overtime_pay) }}</span>
          </div>
          <div class="border-t border-dashed border-gray-200 pt-2.5 flex justify-between items-center">
            <span class="text-sm font-semibold text-gray-700">应发工资</span>
            <span class="text-sm font-semibold text-gray-800">¥{{ formatMoney(salary.payable_salary) }}</span>
          </div>
        </div>
      </div>

      <!-- 扣款项目 -->
      <div class="bg-white px-6 py-4 border-t border-gray-100">
        <h3 class="text-xs font-semibold text-red-400 uppercase tracking-wider mb-3">
          扣款项目
        </h3>
        <div class="space-y-2.5">
          <div v-if="salary.leave_days" class="flex justify-between items-center">
            <span class="text-sm text-gray-600">请假天数 ({{ salary.leave_days }}天)</span>
            <span class="text-sm font-medium text-red-500">-¥{{ formatMoney(salary.deducted_leave_pay || 0) }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">社保</span>
            <span class="text-sm font-medium text-red-500">-¥{{ formatMoney(salary.social_security) }}</span>
          </div>
          <div v-if="salary.income_tax" class="flex justify-between items-center">
            <span class="text-sm text-gray-600">个人所得税</span>
            <span class="text-sm font-medium text-red-500">-¥{{ formatMoney(salary.income_tax) }}</span>
          </div>
        </div>
      </div>

      <!-- 实收工资 -->
      <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-b-2xl px-6 py-5 border-t-2 border-green-200">
        <div class="flex justify-between items-center">
          <span class="text-base font-bold text-gray-800">实收工资</span>
          <span class="text-2xl font-black text-green-600">
            ¥{{ formatMoney(salary.net_salary) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
