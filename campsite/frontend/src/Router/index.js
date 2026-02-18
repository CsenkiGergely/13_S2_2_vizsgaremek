import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Kereses from '../views/Kereses.vue'
import Admin from '../views/admin.vue'
import Foglalas from '../views/foglalas.vue'
import Adatvedelem from '../views/adatvedel.vue'
import Felhasznalo from '../views/felhasznalo.vue'
import Cookie from '../views/cookie.vue'
import Fizetes from '../views/fizetes.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'Kereses', component: Kereses },
  { path: '/admin', name: 'Admin', component: Admin }, // ez fontos
  { path: '/foglalas', name: 'Foglalas', component: Foglalas },
  { path: '/adatvedelem', name: 'Adatvedelem', component: Adatvedelem },
  { path: '/cookie', name: 'Cookie', component: Cookie },
  { path: '/felhasznalo', name: 'Felhasznalo', component: Felhasznalo },
   { path: '/fizetes', name: 'Fizetes', component: Fizetes }
]



const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
