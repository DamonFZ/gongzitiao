import axios from 'axios'
import { useRouter } from 'vue-router'

const request = axios.create({
  baseURL: '/',
  timeout: 10000,
})

// 请求拦截器：注入 Token
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// 响应拦截器：401 自动跳转
request.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
      window.location.hash = '#/bind'
    }
    return Promise.reject(error)
  }
)

export default request
