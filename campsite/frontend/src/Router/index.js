import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Kereses from '../views/Kereses.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'Kereses', component: Kereses }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
