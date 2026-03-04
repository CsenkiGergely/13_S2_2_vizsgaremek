import { ref, computed } from 'vue'
import api from '../api/axios'

const user = ref(JSON.parse(localStorage.getItem('user')) || null) // aktuális felhasználó objektuma (ha van)
const token = ref(localStorage.getItem('auth_token') || null) // hitelesítési token
const loading = ref(false) // általános betöltési állapot
const error = ref(null) // utolsó hibaüzenet

// Gyors ellenőrzés, hogy be van-e jelentkezve a felhasználó
const isAuthenticated = computed(() => !!token.value)

// Ha az axios interceptor 401-et kap, töröljük a reaktív állapotot is
window.addEventListener('auth:unauthenticated', () => {
  token.value = null
  user.value = null
})

// Regisztráció
// userData: { owner_first_name, owner_last_name, email, password, password_confirmation }
const register = async (userData) => {
  loading.value = true
  error.value = null
  
  try {
    // Kérést küld a backend /register végpontjára
    const response = await api.post('/register', {
      owner_first_name: userData.owner_first_name,
      owner_last_name: userData.owner_last_name,
      email: userData.email,
      password: userData.password,
      password_confirmation: userData.password_confirmation
    })
    
    // Backend válasza várhatóan tartalmaz user és token mezőket
    const { user: userData2, token: newToken } = response.data
    
    // Laravel Sanctum esetén lehet plainTextToken, egyébként csak token
    const plainToken = newToken.plainTextToken || newToken
    token.value = plainToken
    user.value = userData2
    
    // Lokális tárolás a session megőrzéséhez oldalfrissítés után is
    localStorage.setItem('auth_token', plainToken)
    localStorage.setItem('user', JSON.stringify(userData2))
    
    return { success: true, user: userData2 }
  } catch (err) {
    // Hibák egyszerűsített összegyűjtése a UI számára
    error.value = err.response?.data?.message || err.response?.data?.errors || 'Regisztrációs hiba történt.'
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
    // POST a /login végpontra
    const response = await api.post('/login', {
      email: credentials.email,
      password: credentials.password
    })
    
    // Ha a backend explicit "Invalid credentials" üzenetet ad vissza
    if (response.data.message === 'Invalid credentials') {
      error.value = 'Hibás email-cím vagy jelszó.'
      return { success: false, error: error.value }
    }
    
    // Sikeres bejelentkezés: user és token várható
    const { user: userData, token: newToken } = response.data
    
    // Laravel Sanctum esetén lehet plainTextToken, egyébként csak token
    const plainToken = newToken?.plainTextToken || newToken
    token.value = plainToken
    user.value = userData
    
    // Mentés lokálisan a következő oldalletöltéshez
    localStorage.setItem('auth_token', plainToken)
    localStorage.setItem('user', JSON.stringify(userData))
    
    return { success: true, user: userData }
  } catch (err) {
    // 422 validációs hibák kezelése
    if (err.response?.status === 422) {
      const errors = err.response?.data?.errors
      if (errors && Object.keys(errors).length > 0) {
        // Az első mezőhöz tartozó első hibaüzenetet jelenítjük meg
        error.value = Object.values(errors).flat()[0]
      } else {
        error.value = err.response?.data?.message || 'Hibás adatok.'
      }
    } else if (err.response?.status === 401) {
      error.value = 'Hibás email-cím vagy jelszó.'
    } else if (err.response?.status === 403) {
      error.value = err.response?.data?.message || 'A fiók nincs aktiválva.'
    } else {
      // Egyéb hibák: backend üzenet vagy általános hibaüzenet
      error.value = err.response?.data?.message || 'Bejelentkezési hiba történt.'
    }
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}

const upgradeToPartner = async (phoneNumber) => {
  loading.value = true
  error.value = null

  try {
    const normalizedPhone = phoneNumber.replace(/\s+/g, '')
    const response = await api.post('/upgrade-to-partner', {
      phone_number: normalizedPhone
    })

    const updatedUser = response.data.user
    user.value = updatedUser
    localStorage.setItem('user', JSON.stringify(updatedUser))

    return {
      success: true,
      user: updatedUser,
      message: response.data.message || 'Sikeres partnerstátusz-váltás.'
    }
  } catch (err) {
    if (err.response?.status === 401) {
      error.value = 'A partner státuszhoz előbb be kell jelentkezned.'
    } else if (err.response?.status === 422) {
      error.value = err.response?.data?.errors?.phone_number?.[0] || 'Érvénytelen telefonszám.'
    } else {
      error.value = err.response?.data?.message || 'Hiba történt partnerstátusz-váltás közben.'
    }

    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}
// Kijelentkezés
// Először töröljük a lokális adatokat, majd megpróbáljuk értesíteni a szervert
const logout = async () => {
  loading.value = true

  // Lokális állapot azonnali törlése (a szerver válaszától függetlenül)
  const currentToken = token.value
  token.value = null
  user.value = null
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')

  try {
    // Szerver értesítése a tokennel (amit még eltettünk)
    await api.post('/logout', {}, {
      headers: { Authorization: `Bearer ${currentToken}` }
    })
  } catch (err) {
    // 401 vagy hálózati hiba esetén sem probléma — már kijelentkeztünk lokálisan
    if (err.response?.status !== 401) {
      console.error('Logout error:', err)
    }
  } finally {
    loading.value = false
  }
}

// Felhasználói adatok lekérése a szerverről
// Visszatér a felhasználó objektummal, vagy null-lal, ha nincs token vagy hiba történt
const fetchUser = async () => {
  if (!token.value) return null
  
  loading.value = true
  
  try {
    // Kérést küldünk a /user végpontra, amely auth-olt adatokat ad vissza
    const response = await api.get('/user')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
    return response.data
  } catch (err) {
    // Ha a token lejárt vagy érvénytelen, tisztítjuk a lokális állapotot
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

// Elfelejtett jelszó: e-mail küldése a backendre (jelszó-visszaállító link)
const forgotPassword = async (email) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.post('/forgot-password', { email })
    return { 
      success: true, 
      message: response.data.message || 'Jelszó-visszaállító linket elküldtük az e-mail-címedre.' 
    }
  } catch (err) {
    // Hibák lehetnek általános üzenetben vagy mezőspecifikus validációs hibákban
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Hiba történt. Próbáld újra később.'
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}

// Jelszó módosítása a reset űrlapról érkező adatokkal (token, email, password, password_confirmation)
const resetPassword = async (data) => {
  loading.value = true
  error.value = null
  
  try {
    // Küldjük a reset adatokat a szervernek
    const response = await api.post('/reset-password', data)
    return { 
      success: true, 
      message: response.data.message || 'Jelszó sikeresen megváltoztatva.' 
    }
  } catch (err) {
    // Kezeljük a lehetséges hibaválaszokat: általános üzenet vagy mezőspecifikus hibák
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Hiba történt. Próbáld újra később.'
    return { success: false, error: error.value }
  } finally {
    loading.value = false
  }
}


// Jelszó erősség számítása a megadott jelszó alapján
const passwordStrength = (password) => {
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
    resetPassword,
    upgradeToPartner,
    passwordStrength
  }
}