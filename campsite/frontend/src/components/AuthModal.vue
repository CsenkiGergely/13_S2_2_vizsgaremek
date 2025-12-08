<script setup>
import { ref, computed, watch } from 'vue'
import { useAuth } from '../composables/useAuth'

const { login, register, forgotPassword, loading } = useAuth()

const props = defineProps({
  isOpen: Boolean,
  initialMode: {
    type: String,
    default: 'login'
  }
})

const emit = defineEmits(['close', 'success'])

const mode = ref(props.initialMode)
const formError = ref(null)
const successMessage = ref(null)


watch(() => props.initialMode, (newVal) => {
  mode.value = newVal
})

const isLogin = computed(() => mode.value === 'login')
const isRegister = computed(() => mode.value === 'register')
const isForgotPassword = computed(() => mode.value === 'forgot-password')

// mező változók
const loginForm = ref({
  email: '',
  password: ''
})

const registerForm = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const forgotPasswordForm = ref({
  email: ''
})

const closeModal = () => {
  emit('close')
  formError.value = null
  successMessage.value = null
  mode.value = 'login' // visszaállítás login módba
  // reset form
  loginForm.value = { email: '', password: '' }
  registerForm.value = { name: '', email: '', password: '', password_confirmation: '' }
  forgotPasswordForm.value = { email: '' }
}

const switchMode = (newMode) => {
  console.log('Switching mode to:', newMode)
  mode.value = newMode
  formError.value = null
  successMessage.value = null
  console.log('Current mode is now:', mode.value)
}

const handleLogin = async () => {
  formError.value = null
  
  const result = await login(loginForm.value)
  
  if (result.success) {
    emit('success', result.user)
    closeModal()
  } else {
    formError.value = result.error
  }
}

const handleRegister = async () => {
  formError.value = null
  
  // jelszó check
  if (registerForm.value.password !== registerForm.value.password_confirmation) {
    formError.value = 'A jelszavak nem egyeznek!'
    return
  }
  
  const result = await register(registerForm.value)
  
  if (result.success) {
    emit('success', result.user)
    closeModal()
  } else {
    formError.value = result.error
  }
}

const handleForgotPassword = async () => {
  formError.value = null
  successMessage.value = null
  
  const result = await forgotPassword(forgotPasswordForm.value.email)
  
  if (result.success) {
    successMessage.value = result.message
  } else {
    formError.value = result.error
  }
}
</script>

<template>
  <!-- modal háttér -->
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="isOpen" 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <!-- modal tartalom -->
        <Transition name="scale">
          <div 
            v-if="isOpen"
            class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
          >
            <!-- bezáró gomb -->
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

            <!-- modal tartalom -->
            <div class="px-8 pb-8">
              <!-- Logo -->
              <div class="flex justify-center mb-6">
                <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20">
              </div>

              <!-- cím -->
              <h2 class="text-center text-2xl font-bold tracking-tight text-white mb-4">
                <template v-if="isLogin">Bejelentkezés</template>
                <template v-else-if="isRegister">Regisztráció</template>
                <template v-else>Elfelejtett jelszó</template>
              </h2>

              <!-- leírás az elfelejtett jelszóhoz -->
              <p v-if="isForgotPassword" class="text-center text-gray-400 text-sm mb-6">
                Add meg az email címed és küldünk egy linket a jelszó visszaállításához.
              </p>

              <!-- sikeres üzenet -->
              <div v-if="successMessage" class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/50 text-green-400 text-sm">
                {{ successMessage }}
              </div>

              <!-- hibaüzenet -->
              <div v-if="formError" class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
                {{ typeof formError === 'object' ? Object.values(formError).flat().join(', ') : formError }}
              </div>

              <!-- bejelentkezési űrlap -->
              <form v-if="isLogin" @submit.prevent="handleLogin" class="space-y-5">
                <div>
                  <label for="login-email" class="block text-sm font-medium text-gray-100 mb-2">
                    Email cím
                  </label>
                  <input 
                    id="login-email" 
                    v-model="loginForm.email"
                    type="email" 
                    required 
                    autocomplete="email"
                    placeholder="pelda@email.com"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
                  />
                </div>

                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label for="login-password" class="block text-sm font-medium text-gray-100">
                      Jelszó
                    </label>
                    <button 
                      type="button"
                      @click="switchMode('forgot-password')"
                      class="text-sm font-semibold text-[#4A7434] hover:text-[#F17E21] transition"
                    >
                      Elfelejtett jelszó?
                    </button>
                  </div>
                  <input 
                    id="login-password" 
                    v-model="loginForm.password"
                    type="password" 
                    required 
                    autocomplete="current-password"
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
                    </svg>
                    Bejelentkezés...
                  </span>
                  <span v-else>Bejelentkezés</span>
                </button>
              </form>

              <!-- elfelejtett jelszó űrlap -->
              <form v-if="isForgotPassword && !successMessage" @submit.prevent="handleForgotPassword" class="space-y-5">
                <div>
                  <label for="forgot-email" class="block text-sm font-medium text-gray-100 mb-2">
                    Email cím
                  </label>
                  <input 
                    id="forgot-email" 
                    v-model="forgotPasswordForm.email"
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

              <!-- regisztrációs űrlap -->
              <form v-else-if="isRegister" @submit.prevent="handleRegister" class="space-y-5">
                <div>
                  <label for="register-name" class="block text-sm font-medium text-gray-100 mb-2">
                    Teljes név
                  </label>
                  <input 
                    id="register-name" 
                    v-model="registerForm.name"
                    type="text" 
                    required 
                    autocomplete="name"
                    placeholder="Kovács János"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
                  />
                </div>

                <div>
                  <label for="register-email" class="block text-sm font-medium text-gray-100 mb-2">
                    Email cím
                  </label>
                  <input 
                    id="register-email" 
                    v-model="registerForm.email"
                    type="email" 
                    required 
                    autocomplete="email"
                    placeholder="pelda@email.com"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
                  />
                </div>

                <div>
                  <label for="register-password" class="block text-sm font-medium text-gray-100 mb-2">
                    Jelszó
                  </label>
                  <input 
                    id="register-password" 
                    v-model="registerForm.password"
                    type="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition"
                  />
                </div>

                <div>
                  <label for="register-password-confirm" class="block text-sm font-medium text-gray-100 mb-2">
                    Jelszó megerősítése
                  </label>
                  <input 
                    id="register-password-confirm" 
                    v-model="registerForm.password_confirmation"
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
                    </svg>
                    Regisztráció...
                  </span>
                  <span v-else>Regisztráció</span>
                </button>
              </form>

              <!-- mód váltás -->
              <p class="mt-6 text-center text-sm text-gray-400">
                <template v-if="isLogin">
                  Még nincs fiókod?
                  <button 
                    @click="switchMode('register')"
                    class="font-semibold text-[#4A7434] hover:text-[#F17E21] transition ml-1"
                  >
                    Regisztrálj most!
                  </button>
                </template>
                <template v-else-if="isRegister">
                  Már van fiókod?
                  <button 
                    @click="switchMode('login')"
                    class="font-semibold text-[#4A7434] hover:text-[#F17E21] transition ml-1"
                  >
                    Jelentkezz be!
                  </button>
                </template>
                <template v-else-if="isForgotPassword">
                  <button 
                    @click="switchMode('login')"
                    class="font-semibold text-[#4A7434] hover:text-[#F17E21] transition"
                  >
                    ← Vissza a bejelentkezéshez
                  </button>
                </template>
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* fade átmenet a háttérhez */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scale átmenet a modálhoz */
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
