import { ref } from 'vue'
import api from '../api/axios'

const results = ref([])
const loading = ref(false)
const error = ref(null)

const getErrorMessage = (err) => {
  return err.response?.data?.message || err.message
}

// Általános keresés
const search = async (q) => {
  loading.value = true
  error.value = null

  try {
    const response = await api.get('/search', {
      params: { search: q }
    })
    results.value = response.data.data || response.data
    return results.value
  } catch (err) {
    console.error('Hiba a keresés során:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

export function useSearch() {
  return {
    results,
    loading,
    error,
    search
  }
}