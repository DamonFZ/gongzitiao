<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const months = ref([])
const salary = ref(null)
const loading = ref(true)
const errorMsg = ref('')

const currentMonth = computed(() => route.params.month || '')

function formatMoney(val) {
  const num = parseFloat(val)
  if (isNaN(num)) return '0.00'
  return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

async function loadMonths() {
  try {
    const res = await axios.get('/api/salaries')
    months.value = res.data.months || []
    if (months.value.length > 0 && !currentMonth.value) {
      router.replace(`/salary/${months.value[0]}`)
    }
  } catch (err) {
    if (err.response?.status === 401) {
      router.push('/bind')
    } else {
      errorMsg.value = '加载月份列表失败'
    }
  }
}

async function loadSalary(month) {
  loading.value = true
  errorMsg.value = ''
  salary.value = null

  try {
    const res = await axios.get(`/api/salaries/${month}`)
    salary.value = res.data.salary
  } catch (err) {
    if (err.response?.status === 404) {
      errorMsg.value = '未找到该月份的工资记录'
    } else if (err.response?.status === 401) {
      router.push('/bind')
    } else {
      errorMsg.value = '加载工资详情失败'
    }
  } finally {
    loading.value = false
  }
}

function onMonthChange(month) {
  router.push(`/salary/${month}`)
  loadSalary(month)
}

onMounted(() => {
  const token = localStorage.getItem('token')
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }
  loadMonths().then(() => {
    if (currentMonth.value) {
      loadSalary(currentMonth.value)
    }
  })
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 pb-8">
    <!-- 顶部导航 -->
    <div class="bg-blue-600 text-white px-4 py-4 shadow-md">
      <div class="max-w-md mx-auto flex items-center justify-between">
        <button
          @click="router.push('/bind')"
          class="text-sm opacity-80 hover:opacity-100"
        >
          ← 返回
        </button>
        <h1 class="text-lg font-semibold">工资条查询</h1>
        <div class="w-10"></div>
      </div>
    </div>

    <div class="max-w-md mx-auto px-4 mt-4">
      <!-- 月份选择器 -->
      <div class="bg-white rounded-xl shadow p-4 mb-4">
        <label class="block text-sm font-medium text-gray-600 mb-2">
          选择月份
        </label>
        <select
          :value="currentMonth"
          @change="onMonthChange($event.target.value)"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option v-for="m in months" :key="m" :value="m">
            {{ m }}
          </option>
        </select>
      </div>

      <!-- 加载状态 -->
      <div v-if="loading" class="text-center py-12 text-gray-400">
        <p>加载中...</p>
      </div>

      <!-- 错误提示 -->
      <div v-else-if="errorMsg" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <p class="text-red-500 text-sm">{{ errorMsg }}</p>
      </div>

      <!-- 工资凭条卡片 -->
      <div v-else-if="salary" class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- 卡片头部 -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-5 text-white text-center">
          <p class="text-sm opacity-80 mb-1">{{ salary.month }} 工资条</p>
          <p class="text-xs opacity-60">{{ salary.department || '未知部门' }} · {{ salary.position || '未知岗位' }}</p>
        </div>

        <!-- 应发项目 -->
        <div class="px-6 py-4">
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
        <div class="px-6 py-4 bg-red-50/50">
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
        <div class="px-6 py-5 bg-gradient-to-r from-green-50 to-emerald-50 border-t-2 border-green-200">
          <div class="flex justify-between items-center">
            <span class="text-base font-bold text-gray-800">实收工资</span>
            <span class="text-2xl font-black text-green-600">
              ¥{{ formatMoney(salary.net_salary) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
