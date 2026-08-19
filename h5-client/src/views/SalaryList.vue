<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import request from '../utils/request'

const router = useRouter()
const months = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await request.get('/api/h5/salaries')
    months.value = res.data.months || []
    if (months.value.length === 0) {
      showToast('暂无工资记录')
    }
  } catch (err) {
    if (err.response?.status === 401) {
      showToast('请先绑定')
      router.push('/bind')
    } else {
      showToast('加载失败，请重试')
    }
  } finally {
    loading.value = false
  }
})

function goToDetail(month) {
  router.push({ path: '/detail', query: { month } })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 顶部 -->
    <div class="bg-blue-600 text-white px-4 py-5 text-center">
      <h1 class="text-xl font-bold">我的工资条</h1>
      <p class="text-sm opacity-80 mt-1">请选择月份查看详情</p>
    </div>

    <!-- 月份列表 -->
    <div class="px-4 mt-4 pb-8">
      <div v-if="loading" class="text-center py-16 text-gray-400">
        <p>加载中...</p>
      </div>

      <div v-else-if="months.length === 0" class="text-center py-16 text-gray-400">
        <p>暂无工资记录</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="month in months"
          :key="month"
          @click="goToDetail(month)"
          class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between active:bg-gray-50 transition cursor-pointer"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <p class="font-semibold text-gray-800">{{ month }}</p>
              <p class="text-xs text-gray-400">点击查看工资明细</p>
            </div>
          </div>
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>
