import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import kereses from '../views/kereses.vue'
import fizetes from '../views/fizetes.vue'


const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'kereses', component: kereses },
  { path: '/fizetes', name: 'fizetes', component: fizetes }

]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
