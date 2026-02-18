import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('bookingSearchResults')
const results = ref(stored ? JSON.parse(stored) : null)
const loading = ref(false)

const search = async (searchData) => {
  loading.value = true

  try {
    const response = await api.get('/booking/search', {
      params: searchData
    })
    results.value = response.data
    localStorage.setItem('bookingSearchResults', JSON.stringify(results.value))
    return results.value
  } catch (error) {
    console.error('Booking search error:', error)
    throw error
  } finally {
    loading.value = false
  }
}

export function useBookingSearch() {
  return {
    results,
    loading,
    search
  }
}
