<script setup>
import { ref } from 'vue'
import { useAuth } from '../composables/useAuth'

const { forgotPassword, loading } = useAuth()

const props = defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close', 'backToLogin'])

const email = ref('')
const formError = ref(null)
const successMessage = ref(null)

const closeModal = () => {
  emit('close')
  email.value = ''
  formError.value = null
  successMessage.value = null
}

const goBackToLogin = () => {
  emit('backToLogin')
  email.value = ''
  formError.value = null
  successMessage.value = null
}

const handleSubmit = async () => {
  formError.value = null
  successMessage.value = null
  
  const result = await forgotPassword(email.value)
  
  if (result.success) {
    successMessage.value = result.message
  } else {
    formError.value = result.error
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="isOpen" 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <Transition name="scale">
          <div 
            v-if="isOpen"
            class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
          >
            <!-- Close Button -->
            <div class="flex justify-end p-4 pb-0">
              <button 
                @click="closeModal"
                class="text-gray-400 hover:text-white transition p-1"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="px-8 pb-8">
              <!-- Logo -->
              <div class="flex justify-center mb-6">
                <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20">
              </div>

              <!-- Title -->
              <h2 class="text-center text-2xl font-bold tracking-tight text-white mb-4">
                Elfelejtett jelszó
              </h2>
              
              <p class="text-center text-gray-400 text-sm mb-6">
                Add meg az email címed és küldünk egy linket a jelszó visszaállításához.
              </p>

              <!-- Success Message -->
              <div v-if="successMessage" class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/50 text-green-400 text-sm">
                {{ successMessage }}
              </div>

              <!-- Error Message -->
              <div v-if="formError" class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
                {{ formError }}
              </div>

              <!-- Form -->
              <form v-if="!successMessage" @submit.prevent="handleSubmit" class="space-y-5">
                <div>
                  <label for="forgot-email" class="block text-sm font-medium text-gray-100 mb-2">
                    Email cím
                  </label>
                  <input 
                    id="forgot-email" 
                    v-model="email"
                    type="email" 
                    required 
                    autocomplete="email"
                    placeholder="pelda@email.com"
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
                    </svg>
                    Küldés...
                  </span>
                  <span v-else>Email küldése</span>
                </button>
              </form>

              <!-- Back to Login -->
              <p class="mt-6 text-center text-sm text-gray-400">
                <button 
                  @click="goBackToLogin"
                  class="font-semibold text-[#4A7434] hover:text-[#F17E21] transition"
                >
                  ← Vissza a bejelentkezéshez
                </button>
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.scale-enter-active,
.scale-leave-active {
  transition: all 0.3s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
