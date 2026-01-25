<script setup>
import { ref, onMounted } from 'vue'
import Header from './components/Header.vue'
import Footer from './components/Footer.vue'
import ResetPassword from './views/ResetPassword.vue'


const currentView = ref('home')

onMounted(() => {
  // link alapján dob be a reset jelszó oldalra
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('token') && urlParams.has('email')) {
    currentView.value = 'reset-password'
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- reset password full screen nézet -->
    <template v-if="currentView === 'reset-password'">
      <ResetPassword />
    </template>

    <!-- normál oldal layout -->
    <template v-else>
      <Header/>
      <main class="grow">
        <router-view />
      </main>
      <Footer/>
    </template>
  </div>
</template>

<style scoped>
</style>
