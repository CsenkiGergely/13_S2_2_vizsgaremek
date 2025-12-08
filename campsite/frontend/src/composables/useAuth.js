import { ref, computed } from 'vue'
import api from '../api/axios'

const user = ref(JSON.parse(localStorage.getItem('user')) || null)
const token = ref(localStorage.getItem('auth_token') || null)
const loading = ref(false)
const error = ref(null)


const isAuthenticated = computed(() => !!token.value)

// registrateion
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

// bejelentkezés
const login = async (credentials) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/login', {
      email: credentials.email,
      password: credentials.password
    })
    
    // bejelentkezés check
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

// kijelentkezés
const logout = async () => {
  loading.value = true
  
  try {
    await api.post('/logout')
  } catch (err) {
    // ha hiba van, akkor is kijelentkeztetjük lokálisan
    console.error('Logout error:', err)
  } finally {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    loading.value = false
  }
}

// felhasználó adatainak lekérése
const fetchUser = async () => {
  if (!token.value) return null
  
  loading.value = true
  
  try {
    const response = await api.get('/user')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
    return response.data
  } catch (err) {
    // ha 401 akkor a token lejárt
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

// elfelejtett jelszó
const forgotPassword = async (email) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/forgot-password', { email })
    return { 
      success: true, 
      message: response.data.message || 'Jelszó visszaállító linket elküldtük az email címedre.' 
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Hiba történt. Próbáld újra később.'
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}

// jelszó visszaállítás
const resetPassword = async (data) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/reset-password', data)
    return { 
      success: true, 
      message: response.data.message || 'Jelszó sikeresen megváltoztatva!' 
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Hiba történt. Próbáld újra később.'
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}


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
    fetchUser,
    forgotPassword,
    resetPassword
  }
}
