import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('booking')
const booking = ref(stored ? JSON.parse(stored) : null)
const loading = ref(false)

const fetchBooking = async (params) => {
  loading.value = true

  try {
    const response = await api.get('/booking', {
      params
    })
    booking.value = response.data
    localStorage.setItem('booking', JSON.stringify(booking.value))
    return booking.value
  } catch (error) {
    console.error('Booking fetch error:', error)
    throw error
  } finally {
    loading.value = false
  }
}



export function useBooking() {
  return {
    booking,
    loading,
    fetchBooking
  }
}