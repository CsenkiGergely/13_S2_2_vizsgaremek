<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'

const { resetPassword, loading } = useAuth()

const token = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const formError = ref(null)
const successMessage = ref(null)

onMounted(() => {
  // link cím név gen adatok
  const urlParams = new URLSearchParams(window.location.search)
  token.value = urlParams.get('token') || ''
  email.value = urlParams.get('email') || ''
})

const handleSubmit = async () => {
  formError.value = null
  successMessage.value = null
  
  if (password.value !== password_confirmation.value) {
    formError.value = 'A jelszavak nem egyeznek!'
    return
  }
  
  if (password.value.length < 6) {
    formError.value = 'A jelszónak legalább 6 karakter hosszúnak kell lennie!'
    return
  }
  
  const result = await resetPassword({
    email: email.value,
    token: token.value,
    password: password.value,
    password_confirmation: password_confirmation.value
  })
  
  if (result.success) {
    successMessage.value = result.message
  } else {
    formError.value = result.error
  }
}

const goToHome = () => {
  window.location.href = '/'
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden p-8">
      <!-- logo -->
      <div class="flex justify-center mb-6">
        <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20">
      </div>

      <!-- cím -->
      <h2 class="text-center text-2xl font-bold tracking-tight text-white mb-6">
        Új jelszó megadása
      </h2>

      <!-- sikeres üzenet -->
      <div v-if="successMessage" class="text-center">
        <div class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/50 text-green-400 text-sm">
          {{ successMessage }}
        </div>
        <button 
          @click="goToHome"
          class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300"
        >
          Vissza a főoldalra
        </button>
      </div>

      <!-- hiba -->
      <div v-if="formError" class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
        {{ formError }}
      </div>

      <!-- reset lejart token -->
      <div v-if="!token && !successMessage" class="text-center">
        <div class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
          Érvénytelen vagy hiányzó token!
        </div>
        <button 
          @click="goToHome"
          class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300"
        >
          Vissza a főoldalra
        </button>
      </div>

      <!-- adat mezők -->
      <form v-if="token && !successMessage" @submit.prevent="handleSubmit" class="space-y-5">
        <div>
          <label for="reset-email" class="block text-sm font-medium text-gray-100 mb-2">
            Email cím
          </label>
          <input 
            id="reset-email" 
            v-model="email"
            type="email" 
            required 
            readonly
            class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-gray-400 cursor-not-allowed"
          />
        </div>

        <div>
          <label for="reset-password" class="block text-sm font-medium text-gray-100 mb-2">
            Új jelszó
          </label>
          <input 
            id="reset-password" 
            v-model="password"
            type="password" 
            required 
            autocomplete="new-password"
            placeholder="••••••••"
            class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
          />
        </div>

        <div>
          <label for="reset-password-confirm" class="block text-sm font-medium text-gray-100 mb-2">
            Új jelszó megerősítése
          </label>
          <input 
            id="reset-password-confirm" 
            v-model="password_confirmation"
            type="password" 
            required 
            autocomplete="new-password"
            placeholder="••••••••"
            class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
          />
        </div>

        <button 
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] focus:outline-none focus:ring-2 focus:ring-[#4A7434] focus:ring-offset-2 focus:ring-offset-gray-900 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>  <!-- töltés svg ikon gen -->
            Mentés...
          </span>
          <span v-else>Jelszó mentése</span>
        </button>
      </form>

      <!-- vissza -->
      <p v-if="token && !successMessage" class="mt-6 text-center text-sm text-gray-400">
        <a href="/" class="font-semibold text-[#4A7434] hover:text-[#F17E21] transition">
          ← Vissza a főoldalra
        </a>
      </p>
    </div>
  </div>
</template>
