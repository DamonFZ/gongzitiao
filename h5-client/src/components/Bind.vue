<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const form = reactive({
  name: '',
  phone: '',
  id_card: '',
})

const errors = reactive({
  name: '',
  phone: '',
  id_card: '',
})

function validatePhone(phone) {
  return /^1[3-9]\d{9}$/.test(phone)
}

function validateIdCard(idCard) {
  return /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(idCard)
}

function clearErrors() {
  errors.name = ''
  errors.phone = ''
  errors.id_card = ''
}

async function handleSubmit() {
  clearErrors()
  errorMsg.value = ''
  successMsg.value = ''

  let valid = true

  if (!form.name.trim()) {
    errors.name = '请输入姓名'
    valid = false
  }

  if (!form.phone.trim()) {
    errors.phone = '请输入手机号'
    valid = false
  } else if (!validatePhone(form.phone)) {
    errors.phone = '手机号格式不正确'
    valid = false
  }

  if (!form.id_card.trim()) {
    errors.id_card = '请输入身份证号'
    valid = false
  } else if (!validateIdCard(form.id_card)) {
    errors.id_card = '身份证号格式不正确'
    valid = false
  }

  if (!valid) return

  loading.value = true

  try {
    const res = await axios.post('/api/h5/bind', {
      name: form.name.trim(),
      phone: form.phone.trim(),
      id_card: form.id_card.trim(),
      openid: '',
    })

    const token = res.data.token
    localStorage.setItem('token', token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

    successMsg.value = '绑定成功！'
    setTimeout(() => {
      router.push('/salary')
    }, 1000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || '绑定失败，请重试'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-gray-800 text-center mb-2">
          员工绑定
        </h1>
        <p class="text-gray-500 text-center text-sm mb-8">
          请填写您的个人信息以绑定工资条查询
        </p>

        <div class="space-y-5">
          <!-- 姓名 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              姓名
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="请输入您的姓名"
              class="w-full px-4 py-3 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              :class="errors.name ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">
              {{ errors.name }}
            </p>
          </div>

          <!-- 手机号 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              手机号
            </label>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="请输入11位手机号"
              maxlength="11"
              class="w-full px-4 py-3 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              :class="errors.phone ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="errors.phone" class="text-red-500 text-xs mt-1">
              {{ errors.phone }}
            </p>
          </div>

          <!-- 身份证号 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              身份证号
            </label>
            <input
              v-model="form.id_card"
              type="text"
              placeholder="请输入18位身份证号"
              maxlength="18"
              class="w-full px-4 py-3 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              :class="errors.id_card ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="errors.id_card" class="text-red-500 text-xs mt-1">
              {{ errors.id_card }}
            </p>
          </div>

          <!-- 提示信息 -->
          <p v-if="errorMsg" class="text-red-500 text-sm text-center bg-red-50 rounded-lg py-2">
            {{ errorMsg }}
          </p>
          <p v-if="successMsg" class="text-green-600 text-sm text-center bg-green-50 rounded-lg py-2">
            {{ successMsg }}
          </p>

          <!-- 提交按钮 -->
          <button
            @click="handleSubmit"
            :disabled="loading"
            class="w-full py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 active:bg-blue-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? '绑定中...' : '确认绑定' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
