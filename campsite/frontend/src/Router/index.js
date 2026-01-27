import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Kereses from '../views/Kereses.vue'
import Admin from '../views/admin.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'Kereses', component: Kereses },
  { path: '/admin', name: 'Admin', component: Admin } // ez fontos
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
