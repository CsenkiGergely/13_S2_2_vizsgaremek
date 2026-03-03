import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Kereses from '../views/Kereses.vue'
import Tulajdonos from '../views/Tulajdonos.vue'
import Foglalas from '../views/foglalas.vue'
import Adatvedelem from '../views/adatvedel.vue'
import Felhasznalo from '../views/felhasznalo.vue'
import Cookie from '../views/cookie.vue'
import Fizetes from '../views/fizetes.vue'
import SpotKezeles from '../views/SpotKezeles.vue'
import VerifyEmail from '../views/VerifyEmail.vue'
import Profil from '../views/profil.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/kereses', name: 'Kereses', component: Kereses },
  { path: '/Tulajdonos', name: 'Tulajdonos', component: Tulajdonos },
  { path: '/foglalas', name: 'FoglalasLista', component: Foglalas }, // ID nélküli verzió
  { path: '/foglalas/:id', name: 'Foglalas', component: Foglalas }, // ID-s verzió
  { path: '/adatvedelem', name: 'Adatvedelem', component: Adatvedelem },
  { path: '/cookie', name: 'Cookie', component: Cookie },
  { path: '/felhasznalo', name: 'Felhasznalo', component: Felhasznalo },
  { path: '/kemping/:id/helyek', name: 'SpotKezeles', component: SpotKezeles, meta: { requiresAuth: true } },
  { path: '/verify-email', name: 'VerifyEmail', component: VerifyEmail },
  { path: '/fizetes', name: 'Fizetes', component: Fizetes },
  { path: '/profil', name: 'Profil', component: Profil }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

export default router