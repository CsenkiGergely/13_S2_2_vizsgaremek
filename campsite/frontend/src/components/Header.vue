<script setup>
import { ref } from 'vue'

const mobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}
</script>

<template>
  <!-- Header -->
  <header class="bg-white shadow-md">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center">
          <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-12">
        </div>

        <!-- Desktop Menu -->
        <nav class="hidden md:flex items-center space-x-6">
          <a href="#" class="text-gray-700 hover:text-[#4A7434] transition">Menü</a>
          <a href="#" class="text-gray-700 hover:text-[#4A7434] transition">Legyél partnerünk</a>
          <a href="#" class="text-gray-700 hover:text-[#4A7434] transition">Regisztráció</a>
          <a href="#" class="bg-[#4A7434] text-white px-4 py-2 rounded-lg hover:bg-[#F17E21] transition">Login</a>
        </nav>

        <!-- Mobile Menu Button -->
        <button @click="toggleMobileMenu" class="md:hidden text-gray-700 hover:text-[#4A7434] focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile Menu -->
      <nav v-if="mobileMenuOpen" class="md:hidden mt-4 pb-4 space-y-3">
        <a href="#" class="block text-gray-700 hover:text-[#4A7434] transition py-2">Menü</a>
        <a href="#" class="block text-gray-700 hover:text-[#4A7434] transition py-2">Legyél partnerünk</a>
        <a href="#" class="block text-gray-700 hover:text-[#4A7434] transition py-2">Regisztráció</a>
        <a href="#" class="block bg-[#4A7434] text-white px-4 py-2 rounded-lg hover:bg-[#F17E21] transition text-center">Login</a>
      </nav>
    </div>
  </header>
</template>

<style scoped>
</style>
