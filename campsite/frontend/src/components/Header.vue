<script setup>
import { ref } from 'vue'
import AuthModal from './AuthModal.vue'
import { useAuth } from '../composables/useAuth'

const { user, isAuthenticated, logout } = useAuth()

const mobileMenuOpen = ref(false)
const authModalOpen = ref(false)
const authModalMode = ref('login')

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}



const openLoginModal = () => {
  authModalMode.value = 'login'
  authModalOpen.value = true
  mobileMenuOpen.value = false
}

const openRegisterModal = () => {
  authModalMode.value = 'register'
  authModalOpen.value = true
  mobileMenuOpen.value = false
}
const openPhoneLoginModal = () => {
  authModalMode.value = 'phone-login'
  authModalOpen.value = true
  mobileMenuOpen.value = false
}


const closeAuthModal = () => {
  authModalOpen.value = false
}

const handleAuthSuccess = (userData) => {
  console.log('Sikeres bejelentkezés/regisztráció:', userData)
}

const handleLogout = async () => {
  await logout()
  mobileMenuOpen.value = false
}
</script>

<template>
<header class="bg-white shadow-md sticky top-0 z-50">
  <div class="container mx-auto px-4 flex items-center justify-between h-12">
    <!-- logo -->
    <router-link to="/" class="logo flex items-center">
      <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20"/>
    </router-link>

    <!-- desktop nav -->
    <nav class="hidden md:flex items-center space-x-4">
      <a href="#" class="text-gray-700 hover:text-[#4A7434] transition text-base font-medium">Menü</a>



<template v-if="!isAuthenticated">
  <button 
    @click="openRegisterModal" 
    class="text-gray-700 hover:text-[#4A7434] transition text-base font-medium py-1 px-2"
  >
    Regisztráció
  </button>

  <button 
    @click="openLoginModal" 
    class="bg-[#4A7434] text-white px-3 py-1.5 rounded-lg hover:bg-[#F17E21] text-base font-medium"
  >
    Bejelentkezés
  </button>

  <!-- Új telefonos bejelentkezés gomb -->
  <button 
    @click="openPhoneLoginModal" 
    class="bg-[#4A7434] text-white px-3 py-1.5 rounded-lg hover:bg-[#F17E21] text-base font-medium ml-2"
  >
    Legyél partnerünk
  </button>
</template>

      <template v-else>
        <span class="text-gray-700 text-base font-medium">{{ user?.email }}</span>
        <button @click="handleLogout" class="bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 text-base font-medium">Kijelentkezés</button>
      </template>
    </nav>

    <!-- hamburger -->
    <button @click="toggleMobileMenu" class="md:hidden text-gray-700 hover:text-[#4A7434] focus:outline-none p-1">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <!-- mobil menu -->
  <nav v-if="mobileMenuOpen" class="md:hidden px-4 space-y-2 py-1">
    <a href="#" class="block text-gray-700 hover:text-[#4A7434] text-base font-medium">Menü</a>

<template v-if="!isAuthenticated">
  <button @click="openRegisterModal" class="block w-full text-gray-700 hover:text-[#4A7434] py-1 text-base font-medium">Regisztráció</button>
  <button @click="openLoginModal" class="block w-full bg-[#4A7434] text-white py-1.5 rounded-lg hover:bg-[#F17E21] text-base font-medium">Bejelentkezés</button>
  <button @click="openPhoneLoginModal" class="block w-full bg-[#4A7434] text-white py-1.5 rounded-lg hover:bg-[#F17E21] text-base font-medium">Legyél partnerünk</button>
</template>


    <template v-else>
      <span class="block text-gray-700 py-1 text-base font-medium">{{ user?.email }}</span>
      <button @click="handleLogout" class="block w-full bg-red-500 text-white py-1.5 rounded-lg hover:bg-red-600 text-base font-medium">Kijelentkezés</button>
    </template>
  </nav>

  
  <AuthModal 
    :isOpen="authModalOpen" 
    :initialMode="authModalMode" 
    @close="closeAuthModal"
    @success="handleAuthSuccess"
  />
</header>


</template>

<style>
/* Header és tartalom közötti space csökkentése */
body {
  margin: 0;
  padding-top: 0; /* ha a container top-marginja miatt lenne extra space */
}

header {
  padding-top: 0.5rem; /* kisebb padding */
  padding-bottom: 0.5rem; /* kisebb padding */
}

/* Sticky lebegés */
.sticky {
  position: sticky;
  top: 0;
  z-index: 50;
}
</style>

