import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import kereses from '../views/kereses.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'kereses', component: kereses }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
