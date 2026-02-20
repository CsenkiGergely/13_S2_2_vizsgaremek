<script setup>
import { ref, computed, watch } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'

const { login, register, forgotPassword, upgradeToPartner, loading } = useAuth()
const router = useRouter()

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

// watch a prop változására
watch(() => props.initialMode, (newVal) => {
  mode.value = newVal
})

watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    mode.value = props.initialMode
  }
})

// computedek a template-hez
const isLogin = computed(() => mode.value === 'login')
const isRegister = computed(() => mode.value === 'register')
const isForgotPassword = computed(() => mode.value === 'forgot-password')
const isPhoneLogin = computed(() => mode.value === 'phone-login')

// form adatok
const loginForm = ref({
  email: '',
  password: ''
})

const registerForm = ref({
  owner_first_name: '',
  owner_last_name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const forgotPasswordForm = ref({
  email: ''
})

const phoneLoginForm = ref({
  phone: ''
})

// Jelszó láthatóság toggle
const showLoginPassword = ref(false)
const showRegisterPassword = ref(false)
const showRegisterPasswordConfirm = ref(false)

const toggleLoginPassword = () => {
  showLoginPassword.value = !showLoginPassword.value
}

const toggleRegisterPassword = () => {
  showRegisterPassword.value = !showRegisterPassword.value
}

const toggleRegisterPasswordConfirm = () => {
  showRegisterPasswordConfirm.value = !showRegisterPasswordConfirm.value
}

// jelszó erősség
const passwordStrength = computed(() => {
  const password = registerForm.value.password
  if (!password) return { level: 0, text: '', color: '' }

  let score = 0
  if (password.length >= 12) score += 3
  else if (password.length >= 8) score += 2
  else score += 1

  if (/[a-z]/.test(password)) score += 1
  if (/[A-Z]/.test(password)) score += 1
  if (/[0-9]/.test(password)) score += 1
  if (/[^a-zA-Z0-9]/.test(password)) score += 2
  if (password.length <= 6) score = 0

  if (score <= 4) return { level: 1, text: 'Gyenge jelszó', color: 'text-red-400' }
  else if (score <= 7) return { level: 2, text: 'Közepes jelszó', color: 'text-yellow-400' }
  else return { level: 3, text: 'Erős jelszó', color: 'text-[#4A7434]' }
})

// modal bezárás + form reset
const closeModal = () => {
  emit('close')
  formError.value = null
  successMessage.value = null
  mode.value = 'login'

  loginForm.value = { email: '', password: '' }
  registerForm.value = { owner_first_name: '', owner_last_name: '', email: '', password: '', password_confirmation: '' }
  forgotPasswordForm.value = { email: '' }
  phoneLoginForm.value = { phone: '' }
  
  // Jelszó láthatóság reset
  showLoginPassword.value = false
  showRegisterPassword.value = false
  showRegisterPasswordConfirm.value = false
}

// switchMode
const switchMode = (newMode) => {
  mode.value = newMode
  formError.value = null
  successMessage.value = null
  
  // Jelszó láthatóság reset mode váltáskor
  showLoginPassword.value = false
  showRegisterPassword.value = false
  showRegisterPasswordConfirm.value = false
}

// login
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

// register
const handleRegister = async () => {
  formError.value = null
  if (passwordStrength.value.level < 2) {
    formError.value = 'A jelszónak legalább közepes erősségűnek kell lennie!'
    return
  }

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

// forgot password
const handleForgotPassword = async () => {
  formError.value = null
  successMessage.value = null
  const result = await forgotPassword(forgotPasswordForm.value.email)
  if (result.success) successMessage.value = result.message
  else formError.value = result.error
}

// phone login
const handlePhoneLogin = async () => {
  formError.value = null
  successMessage.value = null
  const phone = phoneLoginForm.value.phone.trim()
  const phoneRegex = /^\+36\d{2}\s?\d{3}\s?\d{4}$/
  if (!phoneRegex.test(phone)) {
    formError.value = 'Érvénytelen telefonszám formátum! Pl: +36701234567 vagy +36 70 123 4567'
    return
  }

const result = await upgradeToPartner(phone)

  if (result.success) {
    emit('success', result.user)
    successMessage.value = result.message || 'Sikeresen partner lettél!'
    setTimeout(() => {
      closeModal()
      router.push('/admin')
}, 800)
  } else {
    formError.value = result.error || 'Hiba történt partner státusz váltás közben.'
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <Transition name="scale">
          <div class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- bezáró gomb -->
            <div class="flex justify-end p-4 pb-0">
              <button @click="closeModal" class="text-gray-400 hover:text-white transition p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="px-8 pb-8">
              <!-- Logo -->
              <div class="flex justify-center mb-6">
                <img src="/img/CampSite.svg" alt="CampSite Logo" class="h-20">
              </div>

              <!-- cím -->
              <h2 class="text-center text-2xl font-bold tracking-tight text-white mb-4">
                <template v-if="isLogin">Bejelentkezés</template>
                <template v-else-if="isRegister">Regisztráció</template>
                <template v-else-if="isForgotPassword">Elfelejtett jelszó</template>
                <template v-else-if="isPhoneLogin"></template>
                 <template v-else-if="isPhoneLogin">Partner státusz igénylése</template>
              </h2>

              <!-- hiba/siker üzenet -->
              <div v-if="successMessage" class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/50 text-green-400 text-sm">
                {{ successMessage }}
              </div>
              <div v-if="formError" class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
                {{ typeof formError === 'object' ? Object.values(formError).flat().join(', ') : formError }}
              </div>

              <!-- login form -->
              <form v-if="isLogin" @submit.prevent="handleLogin" class="space-y-5">
                <div>
                  <label for="login-email" class="block text-sm font-medium text-gray-100 mb-2">Email cím</label>
                  <input id="login-email" v-model="loginForm.email" type="email" required placeholder="pelda@email.com"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label for="login-password" class="block text-sm font-medium text-gray-100">Jelszó</label>
                    <button type="button" @click="switchMode('forgot-password')" class="text-sm font-semibold text-[#4A7434] hover:text-[#F17E21] transition">Elfelejtett jelszó?</button>
                  </div>
                  <div class="relative">
                    <input 
                      id="login-password" 
                      v-model="loginForm.password" 
                      :type="showLoginPassword ? 'text' : 'password'" 
                      required 
                      placeholder="••••••••"
                      autocomplete="new-password"
                      class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 pr-12 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition password-no-reveal" 
                    />
                    <button 
                      type="button" 
                      @click="toggleLoginPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition"
                      tabindex="-1"
                    >
                      <svg v-if="!showLoginPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    </button>
                  </div>
                </div>
                <button type="submit" :disabled="loading"
                  class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  Bejelentkezés
                </button>
              </form>

              <!-- phone login -->
              <form v-else-if="isPhoneLogin" @submit.prevent="handlePhoneLogin" class="space-y-5">
                <div>
                  <label for="phone-login" class="block text-sm font-medium text-gray-100 mb-2">Telefonszám</label>
                  <input id="phone-login" v-model="phoneLoginForm.phone" type="tel" required placeholder="+36 70 123 4567"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>
                <button type="submit" :disabled="loading" class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  Partner leszek
                </button>
              </form>

              <!-- forgot password -->
              <form v-else-if="isForgotPassword && !successMessage" @submit.prevent="handleForgotPassword" class="space-y-5">
                <div>
                  <label for="forgot-email" class="block text-sm font-medium text-gray-100 mb-2">Email cím</label>
                  <input id="forgot-email" v-model="forgotPasswordForm.email" type="email" required placeholder="pelda@email.com"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>
                <button type="submit" :disabled="loading" class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  Küldés
                </button>
              </form>

              <!-- register -->
              <form v-else-if="isRegister" @submit.prevent="handleRegister" class="space-y-5">
                <div>
                  <label for="register-lastname" class="block text-sm font-medium text-gray-100 mb-2">Vezetéknév</label>
                  <input id="register-lastname" v-model="registerForm.owner_last_name" type="text" required placeholder="Kovács"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>                
                <div>
                  <label for="register-firstname" class="block text-sm font-medium text-gray-100 mb-2">Keresztnév</label>
                  <input id="register-firstname" v-model="registerForm.owner_first_name" type="text" required placeholder="János"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>
                <div>
                  <label for="register-email" class="block text-sm font-medium text-gray-100 mb-2">Email cím</label>
                  <input id="register-email" v-model="registerForm.email" type="email" required placeholder="pelda@email.com"
                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition" />
                </div>
                <div>
                  <label for="register-password" class="block text-sm font-medium text-gray-100 mb-2">Jelszó</label>
                  <div class="relative">
                    <input 
                      id="register-password" 
                      v-model="registerForm.password" 
                      :type="showRegisterPassword ? 'text' : 'password'" 
                      required 
                      placeholder="••••••••"
                      autocomplete="new-password"
                      class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 pr-12 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition password-no-reveal" 
                    />
                    <button 
                      type="button" 
                      @click="toggleRegisterPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition"
                      tabindex="-1"
                    >
                      <svg v-if="!showRegisterPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    </button>
                  </div>
                  <p v-if="registerForm.password" class="mt-1 text-sm" :class="passwordStrength.color">{{ passwordStrength.text }}</p>
                </div>
                <div>
                  <label for="register-password-confirm" class="block text-sm font-medium text-gray-100 mb-2">Jelszó megerősítése</label>
                  <div class="relative">
                    <input 
                      id="register-password-confirm" 
                      v-model="registerForm.password_confirmation" 
                      :type="showRegisterPasswordConfirm ? 'text' : 'password'" 
                      required 
                      placeholder="••••••••"
                      autocomplete="new-password"
                      class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 pr-12 text-white placeholder:text-gray-500 focus:border-[#4A7434] focus:ring-2 focus:ring-[#4A7434] focus:outline-none transition password-no-reveal" 
                    />
                    <button 
                      type="button" 
                      @click="toggleRegisterPasswordConfirm"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition"
                      tabindex="-1"
                    >
                      <svg v-if="!showRegisterPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    </button>
                  </div>
                </div>
                <button type="submit" :disabled="loading" class="w-full rounded-lg bg-[#4A7434] px-4 py-3 text-sm font-semibold text-white hover:bg-[#F17E21] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                  Regisztráció
                </button>
              </form>

              <!-- mód váltás -->
              <p class="mt-6 text-center text-sm text-gray-400">
                <template v-if="isLogin">
                  Még nincs fiókod?
                  <button @click="switchMode('register')" class="font-semibold text-[#4A7434] hover:text-[#F17E21] ml-1">Regisztrálj most!</button>
                </template>
                <template v-else-if="isRegister || isForgotPassword || isPhoneLogin">
                  Már van fiókod?
                  <button @click="switchMode('login')" class="font-semibold text-[#4A7434] hover:text-[#F17E21] ml-1">Bejelentkezés</button>
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

/* Edge, IE és más böngészők beépített jelszó megjelenítő gombjának elrejtése */
.password-no-reveal::-ms-reveal,
.password-no-reveal::-ms-clear {
  display: none;
}

/* Webkit böngészők (Chrome, Safari, Edge Chromium) autofill ikonjainak elrejtése */
.password-no-reveal::-webkit-credentials-auto-fill-button,
.password-no-reveal::-webkit-textfield-decoration-container {
  visibility: hidden;
  pointer-events: none;
  position: absolute;
}
</style>
