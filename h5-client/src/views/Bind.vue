<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import request from '../utils/request'

const router = useRouter()
const loading = ref(false)

const form = reactive({
  name: '',
  phone: '',
  id_card: '',
})

function validate() {
  if (!form.name.trim()) {
    showToast('请输入姓名')
    return false
  }
  if (!/^1[3-9]\d{9}$/.test(form.phone)) {
    showToast('手机号格式不正确')
    return false
  }
  if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(form.id_card)) {
    showToast('身份证号格式不正确')
    return false
  }
  return true
}

async function handleBind() {
  if (!validate()) return

  loading.value = true
  try {
    const res = await request.post('/api/h5/bind', {
      name: form.name.trim(),
      phone: form.phone.trim(),
      id_card: form.id_card.trim(),
      openid: '',
    })

    const token = res.data.token
    localStorage.setItem('token', token)
    showToast({ type: 'success', message: '绑定成功' })

    setTimeout(() => {
      router.push('/list')
    }, 800)
  } catch (err) {
    showToast(err.response?.data?.message || '绑定失败，请检查信息是否正确')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <!-- 顶部 -->
    <div class="bg-blue-600 text-white px-4 py-6 text-center">
      <h1 class="text-xl font-bold">工资条查询</h1>
      <p class="text-sm opacity-80 mt-1">首次使用请先绑定个人信息</p>
    </div>

    <!-- 表单卡片 -->
    <div class="px-4 -mt-4">
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="space-y-4">
          <!-- 姓名 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              姓名 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="请输入您的姓名"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
          </div>

          <!-- 手机号 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              手机号 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="请输入11位手机号"
              maxlength="11"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
          </div>

          <!-- 身份证号 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              身份证号 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.id_card"
              type="text"
              placeholder="请输入18位身份证号"
              maxlength="18"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            />
          </div>

          <!-- 绑定按钮 -->
          <button
            @click="handleBind"
            :disabled="loading"
            class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 active:bg-blue-800 transition disabled:opacity-50 disabled:cursor-not-allowed text-base"
          >
            {{ loading ? '绑定中...' : '立即绑定' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 底部说明 -->
    <div class="px-6 mt-6 text-center">
      <p class="text-xs text-gray-400 leading-relaxed">
        请填写与工资表中一致的个人信息<br />
        信息仅用于身份验证，不会泄露
      </p>
    </div>
  </div>
</template>
