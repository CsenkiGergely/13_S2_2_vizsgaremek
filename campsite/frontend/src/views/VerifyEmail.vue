<template>
  <div class="verify-container">
    <div class="verify-card">
      <div v-if="loading" class="verify-loading">
        <i class="pi pi-spin pi-spinner" style="font-size: 2rem;"></i>
        <p>Email cím megerősítése folyamatban...</p>
      </div>

      <div v-else-if="success" class="verify-success">
        <i class="pi pi-check-circle" style="font-size: 3rem; color: #22c55e;"></i>
        <h2>Email sikeresen megerősítve!</h2>
        <p>{{ message }}</p>
        <button class="btn-login" @click="goToLogin">Bejelentkezés</button>
      </div>

      <div v-else class="verify-error">
        <i class="pi pi-times-circle" style="font-size: 3rem; color: #ef4444;"></i>
        <h2>Hiba történt</h2>
        <p>{{ message }}</p>
        <button class="btn-retry" @click="goToHome">Vissza a főoldalra</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const success = ref(false)
const message = ref('')

onMounted(async () => {
  const token = route.query.token
  const email = route.query.email

  if (!token || !email) {
    loading.value = false
    success.value = false
    message.value = 'Hiányzó token vagy email cím.'
    return
  }

  try {
    const response = await api.post('/verify-email', { email, token })
    success.value = true
    message.value = response.data.message
  } catch (error) {
    success.value = false
    message.value = error.response?.data?.message || 'Ismeretlen hiba történt.'
  } finally {
    loading.value = false
  }
})

const goToLogin = () => {
  router.push('/')
}

const goToHome = () => {
  router.push('/')
}
</script>

<style scoped>
.verify-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 80vh;
  padding: 2rem;
}

.verify-card {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  text-align: center;
  max-width: 450px;
  width: 100%;
}

.verify-loading p {
  margin-top: 1rem;
  color: #666;
}

.verify-success h2,
.verify-error h2 {
  margin: 1rem 0 0.5rem;
}

.verify-success p,
.verify-error p {
  color: #666;
  margin-bottom: 1.5rem;
}

.btn-login,
.btn-retry {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  color: white;
}

.btn-login {
  background: #22c55e;
}

.btn-login:hover {
  background: #16a34a;
}

.btn-retry {
  background: #3b82f6;
}

.btn-retry:hover {
  background: #2563eb;
}
</style>
