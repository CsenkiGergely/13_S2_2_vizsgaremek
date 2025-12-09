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
  <!-- fejléc -->
  <header class="bg-white shadow-md">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- logo -->
        <div class="flex items-center">
          <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-12">
        </div>

        <!-- gépes nézet -->
        <nav class="hidden md:flex items-center space-x-6">
          <a href="#" class="text-gray-700 hover:text-[#4A7434] transition">Menü</a>
          <a href="#" class="text-gray-700 hover:text-[#4A7434] transition">Legyél partnerünk</a>
          
          <!-- ha nincs bejelentkezve -->
          <template v-if="!isAuthenticated">
            <button @click="openRegisterModal" class="text-gray-700 hover:text-[#4A7434] transition">Regisztráció</button>
            <button @click="openLoginModal" class="bg-[#4A7434] text-white px-4 py-2 rounded-lg hover:bg-[#F17E21] transition">Bejelentkezés</button>
          </template>
          
          <!-- ha be van jelentkezve -->
          <template v-else>
            <span class="text-gray-700">{{ user?.email }}</span>
            <button @click="handleLogout" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Kijelentkezés</button>
          </template>
        </nav>

        <!-- hamburger menü -->
        <button @click="toggleMobileMenu" class="md:hidden text-gray-700 hover:text-[#4A7434] focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- mobil menü -->
      <nav v-if="mobileMenuOpen" class="md:hidden mt-4 pb-4 space-y-3">
        <a href="#" class="block text-gray-700 hover:text-[#4A7434] transition py-2" text-center>Menü</a>
        <a href="#" class="block text-gray-700 hover:text-[#4A7434] transition py-2" text-center>Legyél partnerünk</a>
        
        <!-- ha nincs bejelentkezve -->
        <template v-if="!isAuthenticated">
          <button @click="openRegisterModal" class="block w-full text-left text-gray-700 hover:text-[#4A7434] transition py-2 text-center">Regisztráció</button>
          <button @click="openLoginModal" class="block w-full bg-[#4A7434] text-white px-4 py-2 rounded-lg hover:bg-[#F17E21] transition text-center">Bejelentkezés</button>
        </template>
        
        <!-- ha be van jelentkezve -->
        <template v-else>
          <span class="block text-gray-700 py-2">{{ user?.email }}</span>
          <button @click="handleLogout" class="block w-full bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition text-center">Kijelentkezés</button>
        </template>
      </nav>
    </div>

    <!-- auth modal -->
    <AuthModal 
      :isOpen="authModalOpen" 
      :initialMode="authModalMode" 
      @close="closeAuthModal"
      @success="handleAuthSuccess"
    />
  </header>
</template>

<style scoped>
</style>
