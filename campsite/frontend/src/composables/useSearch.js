import { ref } from 'vue'
import api from '../api/axios'

const results = ref(localStorage.getItem('searchResults') || null)
const loading = ref(false)

const search = async (searchData) => {
  loading.value = true

  try {
    const response = await api.get('/search', {
      params: searchData
      })
  } catch(error) {
    console.error('Search error:', error)
  } finally {
    loading.value = false
  }
}



export function useSearch() {
  return {
    search
  }
}