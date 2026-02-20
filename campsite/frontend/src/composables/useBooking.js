import { ref } from 'vue'
import api from '../api/axios'

const stored = localStorage.getItem('booking')
const booking = ref(stored ? JSON.parse(stored) : null)
const bookings = ref([])
const price = ref({})
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
    console.log('Response Data:', response.data)

    // Mappeljük az API adatokat a vue komponens formátumára
    if (response.data && response.data.data) {
      bookings.value = response.data.data.map(b => ({
        id: b.id,
        guestFirstName: b.user?.owner_first_name || 'Ismeretlen',
        guestLastName: b.user?.owner_last_name || 'Ismeretlen',
        spot: b.camping_spot?.name || 'N/A',
        checkIn: b.arrival_date,
        checkOut: b.departure_date,
        guests: `${b.guests || 0} fő`,
        status: b.status
      }))
    }
  } catch (err) {
    console.error('Hiba a foglalások lekérésekor:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

const getPrices = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await api.get('/booking/prices')

    console.log('Prices Response Data:', response.data)

    price.value = response.data
    return response.data   // ← inkább ezt add vissza

  } catch (err) {
    console.log("TELJES HIBA:", err)
    console.log("STATUS:", err.response?.status)
    console.log("DATA:", err.response?.data)
    return []
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
    getAllBookings,
    price,
    getPrices
  }
}