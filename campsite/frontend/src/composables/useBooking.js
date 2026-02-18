import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('booking')
const booking = ref(stored ? JSON.parse(stored) : null)
const bookings = ref([])
const loading = ref(false)
const error = ref(null)

const fetchBooking = async (params) => {
  loading.value = true

  try {
    const response = await api.get('/booking', {
      params
    })
    booking.value = response.data
    localStorage.setItem('booking', JSON.stringify(booking.value))
    return booking.value
  } catch (err) {
    console.error('Booking fetch error:', err)
    error.value = err.message
    throw err
  } finally {
    loading.value = false
  }
}

// Összes foglalás lekérése
const getAllBookings = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/bookings/getAll')
    console.log('API Response:', response)
    console.log('Response Data:', response.data)

    // Mappeljük az API adatokat a vue komponens formátumára
    if (response.data && response.data.data) {
      bookings.value = response.data.data.map(b => ({
        id: b.id,
        guestName: b.user?.name || 'Ismeretlen',
        spot: b.camping_spot?.name || 'N/A',
        checkIn: b.arrival_date,
        checkOut: b.departure_date,
        guests: `${b.guests || 0} fő`,
        status: b.status,
        price: `${b.total_price || 0} Ft`
      }))
      console.log('Mappelt bookings:', bookings.value)
    }
  } catch (err) {
    console.error('Hiba a foglalások lekérésekor:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

export function useBooking() {
  return {
    booking,
    bookings,
    loading,
    error,
    fetchBooking,
    getAllBookings
  }
}