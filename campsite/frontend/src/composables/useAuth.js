import { ref, computed } from 'vue'
import api from '../api/axios'

// Reaktív állapotok
const user = ref(JSON.parse(localStorage.getItem('user')) || null)
const token = ref(localStorage.getItem('auth_token') || null)
const loading = ref(false)
const error = ref(null)

// Computed
const isAuthenticated = computed(() => !!token.value)

// Regisztráció
const register = async (userData) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/register', {
      name: userData.name,
      email: userData.email,
      password: userData.password,
      password_confirmation: userData.password_confirmation
    })
    
    const { user: userData2, token: newToken } = response.data
    
    // Token mentése (a register endpoint-nél a token objektumban van)
    const plainToken = newToken.plainTextToken || newToken
    token.value = plainToken
    user.value = userData2
    
    localStorage.setItem('auth_token', plainToken)
    localStorage.setItem('user', JSON.stringify(userData2))
    
    return { success: true, user: userData2 }
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors || 'Regisztrációs hiba történt'
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}

// Bejelentkezés
const login = async (credentials) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/login', {
      email: credentials.email,
      password: credentials.password
    })
    
    // Ellenőrizzük, hogy sikeres volt-e a bejelentkezés
    if (response.data.message === 'Invalid credentials') {
      error.value = 'Hibás email cím vagy jelszó'
      return { success: false, error: error.value }
    }
    
    const { user: userData, token: newToken } = response.data
    
    token.value = newToken
    user.value = userData
    
    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('user', JSON.stringify(userData))
    
    return { success: true, user: userData }
  } catch (err) {
    if (err.response?.status === 422) {
      // Validation error
      const errors = err.response?.data?.errors
      if (errors?.email) {
        error.value = errors.email[0]
      } else if (errors?.password) {
        error.value = errors.password[0]
      } else {
        error.value = 'Hibás adatok'
      }
    } else {
      error.value = err.response?.data?.message || 'Bejelentkezési hiba történt'
    }
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}

// Kijelentkezés
const logout = async () => {
  loading.value = true
  
  try {
    await api.post('/logout')
  } catch (err) {
    // Ha hiba van, akkor is kijelentkeztetjük lokálisan
    console.error('Logout error:', err)
  } finally {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    loading.value = false
  }
}

// Felhasználó adatainak lekérése
const fetchUser = async () => {
  if (!token.value) return null
  
  loading.value = true
  
  try {
    const response = await api.get('/user')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
    return response.data
  } catch (err) {
    // Ha 401, akkor a token lejárt
    if (err.response?.status === 401) {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
    return null
  } finally {
    loading.value = false
  }
}

// Composable exportálása
export function useAuth() {
  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    register,
    login,
    logout,
    fetchUser
  }
}
