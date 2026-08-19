import { createRouter, createWebHashHistory } from 'vue-router'
import Bind from '../views/Bind.vue'
import SalaryList from '../views/SalaryList.vue'
import SalaryDetail from '../views/SalaryDetail.vue'

const routes = [
  { path: '/', redirect: '/bind' },
  { path: '/bind', component: Bind },
  { path: '/list', component: SalaryList },
  { path: '/detail', component: SalaryDetail },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
