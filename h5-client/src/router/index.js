import { createRouter, createWebHistory } from 'vue-router'
import Bind from '../components/Bind.vue'
import SalaryDetail from '../components/SalaryDetail.vue'

const routes = [
  { path: '/', redirect: '/bind' },
  { path: '/bind', component: Bind },
  { path: '/salary/:month?', component: SalaryDetail },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
