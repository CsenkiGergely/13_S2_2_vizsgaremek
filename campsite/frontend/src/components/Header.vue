<script setup>
import { ref, computed } from 'vue'
import AuthModal from './AuthModal.vue'
import { useAuth } from '../composables/useAuth'

const { user, isAuthenticated, logout } = useAuth()

const mobileMenuOpen = ref(false)
const authModalOpen = ref(false)
const authModalMode = ref('login')
const profileMenuOpen = ref(false)

// Név kezdőbetűje a profil ikonba
const userInitial = computed(() => {
  return user.value?.owner_first_name ? user.value.owner_first_name.charAt(0).toUpperCase() : '?'
})

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const toggleProfileMenu = () => {
  profileMenuOpen.value = !profileMenuOpen.value
}

const closeProfileMenu = () => {
  profileMenuOpen.value = false
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

const handleAuthSuccess = () => {
  authModalOpen.value = false
  // A user és isAuthenticated automatikusan frissül a useAuth composable-ből
}

const handleLogout = async () => {
  await logout()
  mobileMenuOpen.value = false
  profileMenuOpen.value = false
}
</script>

<template>
<header class="bg-white shadow-md sticky top-0 z-50">
  <div class="max-w-[1400px] mx-auto px-6 flex items-center justify-between h-12">
    <!-- logo -->
    <router-link to="/" class="logo flex items-center">
      <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20"/>
    </router-link>

    <!-- desktop nav -->
    <nav class="hidden md:flex items-center space-x-4">
  <!-- Legyél partnerünk gomb - csak ha nincs bejelentkezve VAGY még nem partner (role !== true) -->
  <button 
    v-if="!isAuthenticated || (isAuthenticated && user?.role !== true)"
    @click="openPhoneLoginModal" 
    class=" text-gray-700 px-3 py-1.5 rounded-lg hover:text-[#4A7434] text-base font-medium ml-2"
  >
    Legyél partnerünk
  </button>

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

</template>

      <template v-else>
        <!-- Profil ikon dropdown -->
        <div class="relative" v-click-outside="closeProfileMenu">
          <button 
            @click="toggleProfileMenu"
            class="flex items-center gap-2 hover:opacity-80 transition focus:outline-none"
            :title="user && user.owner_last_name && user.owner_first_name ? `${user.owner_last_name} ${user.owner_first_name}` : ''"
          >
            <div class="w-9 h-9 rounded-full bg-[#4A7434] text-white font-bold text-base flex items-center justify-center hover:bg-[#F17E21] transition focus:outline-none focus:ring-2 focus:ring-[#4A7434] focus:ring-offset-2">
              {{ userInitial }}
            </div>
            <span class="text-gray-700 font-medium text-sm">
              {{ user && user.owner_last_name && user.owner_first_name ? `${user.owner_last_name} ${user.owner_first_name}` : '' }}
            </span>
          </button>

          <!-- Lenyíló menü -->
          <div 
            v-if="profileMenuOpen"
            class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50"
          >
            <!-- Menüpontok -->
            <router-link 
              to="/profil" 
              @click="closeProfileMenu"
              class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] transition"
            >
              👤 Profilom
            </router-link>

            <router-link 
              v-if="user && user.role === true"
              to="/Tulajdonos" 
              @click="closeProfileMenu"
              class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] transition"
            >
              🏕️ Saját szálláshelyeim
            </router-link>

            <button 
              @click="handleLogout"
              class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
            >
              🚪 Kijelentkezés
            </button>
          </div>
        </div>
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
  <nav v-if="mobileMenuOpen" class="md:hidden border-t border-gray-100">
<template v-if="!isAuthenticated">
  <div class="px-4 py-3 space-y-2">
    <button @click="openRegisterModal" class="block w-full text-gray-700 hover:text-[#4A7434] py-2 text-base font-medium text-left">Regisztráció</button>
    <button @click="openLoginModal" class="block w-full bg-[#4A7434] text-white py-2 rounded-lg hover:bg-[#F17E21] text-base font-medium">Bejelentkezés</button>
    <button @click="openPhoneLoginModal" class="block w-full text-gray-700 hover:text-[#4A7434] py-2 text-base font-medium text-left">Legyél partnerünk</button>
  </div>
</template>

    <template v-else-if="isAuthenticated && user?.role !== true">
      <div class="px-4 py-3 space-y-2">
        <div class="flex items-center gap-3 py-2 border-b border-gray-100 mb-1">
          <div class="w-9 h-9 rounded-full bg-[#4A7434] text-white font-bold flex items-center justify-center text-base">
            {{ userInitial }}
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-800">
              {{ user && user.owner_last_name && user.owner_first_name ? `${user.owner_last_name} ${user.owner_first_name}` : '' }}
            </p>
            <p class="text-xs text-gray-500">{{ user?.email }}</p>
          </div>
        </div>
        <router-link to="/profil" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] rounded-lg transition">
          👤 Profilom
        </router-link>
        <button @click="openPhoneLoginModal" class="flex items-center gap-2 w-full text-left px-2 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] rounded-lg transition">
          🤝 Legyél partnerünk
        </button>
        <div class="border-t border-gray-100 mt-1 pt-1">
          <button @click="handleLogout" class="flex items-center gap-2 w-full text-left px-2 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">
            🚪 Kijelentkezés
          </button>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="px-4 py-3 space-y-1">
        <div class="flex items-center gap-3 py-2 border-b border-gray-100 mb-1">
          <div class="w-9 h-9 rounded-full bg-[#4A7434] text-white font-bold flex items-center justify-center text-base">
            {{ userInitial }}
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-800">
              {{ user && user.owner_last_name && user.owner_first_name ? `${user.owner_last_name} ${user.owner_first_name}` : '' }}
            </p>
            <p class="text-xs text-gray-500">{{ user?.email }}</p>
          </div>
        </div>
        <router-link to="/profil" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] rounded-lg transition">
          👤 Profilom
        </router-link>
        <router-link v-if="user && user.role === true" to="/Tulajdonos" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#4A7434] rounded-lg transition">
          🏕️ Saját szálláshelyeim
        </router-link>
        <div class="border-t border-gray-100 mt-1 pt-1">
          <button @click="handleLogout" class="flex items-center gap-2 w-full text-left px-2 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">
            🚪 Kijelentkezés
          </button>
        </div>
      </div>
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

