import { ref } from 'vue'
import api from '../api/axios'

const dashboard = ref(null)
const loading = ref(false)
const error = ref(null)

// Dashboard-adatok lekérése
const getDashboard = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/dashboard')
    dashboard.value = response.data.data || response.data
    return dashboard.value
  } catch (err) {
    console.error('Hiba a dashboard-adatok lekérésekor:', err)

    error.value = err.response?.data?.message || err.message || 'Nem sikerült betölteni a dashboard-adatokat.'
    throw err
  } finally {
    loading.value = false
  }
}

export function useDashboard() {
  return {
    dashboard,
    loading,
    error,
    getDashboard
  }
}
