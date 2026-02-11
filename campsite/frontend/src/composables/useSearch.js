import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('searchResults')
const results = ref(stored ? JSON.parse(stored) : null)
const loading = ref(false)

const search = async (q) => {
  loading.value = true

  try {
    const response = await api.get('/search', {
      params: { q }
    })
    results.value = response.data
    localStorage.setItem('searchResults', JSON.stringify(results.value))
    return results.value
  } catch (error) {
    console.error('Search error:', error)
    throw error
  } finally {
    loading.value = false
  }
}

export function useSearch() {
  return {
    results,
    loading,
    search
  }
}