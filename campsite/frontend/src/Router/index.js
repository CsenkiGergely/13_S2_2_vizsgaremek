import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Kereses from '../views/Kereses.vue'
import Tulajdonos from '../views/Tulajdonos.vue'
import Foglalas from '../views/Foglalas.vue'
import Adatvedelem from '../views/Adatvedelem.vue'
import Felhasznalo from '../views/Felhasznalo.vue'
import Cookie from '../views/Cookie.vue'
import Fizetes from '../views/Fizetes.vue'
import SpotKezeles from '../views/SpotKezeles.vue'
import VerifyEmail from '../views/VerifyEmail.vue'
import Profil from '../views/Profil.vue'
import Foglalasaim from '../views/Foglalasaim.vue'
import ResetPassword from '../views/ResetPassword.vue'
import VendegAdatok from '../views/VendegAdatok.vue'

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
  { path: '/vendeg-adatok', name: 'VendegAdatok', component: VendegAdatok, meta: { requiresAuth: true } },
  { path: '/profil', name: 'Profil', component: Profil },
  { path: '/foglalasaim', name: 'Foglalasaim', component: Foglalasaim, meta: { requiresAuth: true } },
  { path: '/reset-password', name: 'ResetPassword', component: ResetPassword },
  { path: '/:pathMatch(.*)*', name: 'NotFound', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth && !localStorage.getItem('auth_token')) {
    next({ name: 'Felhasznalo' })
  } else {
    next()
  }
})

export default router