import { ref } from 'vue'
import api from '../api/axios'
import { useSearch } from './useSearch'
import { useCamping } from './useCamping'

const booking = ref(null)
const bookings = ref([])
const searchResults = ref([])
const prices = ref(null)
const loading = ref(false)
const error = ref(null)

const {
  campingDetails,
  campingList,
  campingSpotList,
  campingAvailability,
  getCampingList,
  getCampingDetails,
  getCampingSpotList: fetchCampingSpotList,
  getCampingAvailability
} = useCamping()

const { search: globalSearch } = useSearch()

const getErrorMessage = (err) => {
  return err.response?.data?.message || err.message
}

const searchGlobal = async (searchTerm) => {
  loading.value = true
  error.value = null
  try {
    const data = await globalSearch(searchTerm)
    searchResults.value = data
    return searchResults.value
  } catch (err) {
    console.error('Hiba az általános keresésben:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

const getCampingSpotList = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    return await fetchCampingSpotList(campingId)
  } catch (err) {
    console.error('Hiba a kemping helyeinek lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Jelenlegi felhasználó foglalásai
const getMyBookings = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/bookings')
    bookings.value = response.data.data || response.data
    return bookings.value
  } catch (err) {
    console.error('Hiba a felhasználó foglalásainak lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Egyes foglalás lekérése ID alapján
const getBooking = async (id) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/bookings/${id}`)
    booking.value = response.data.data || response.data
    return booking.value
  } catch (err) {
    console.error('Hiba a foglalás lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Új foglalás létrehozása
const createBooking = async (data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post('/bookings', data)
    booking.value = response.data.data || response.data
    return booking.value
  } catch (err) {
    console.error('Hiba a foglalás létrehozásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Foglalás frissítése
const updateBooking = async (id, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.put(`/bookings/${id}`, data)
    booking.value = response.data.data || response.data
    return booking.value
  } catch (err) {
    console.error('Hiba a foglalás frissítésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Foglalás törlése
const deleteBooking = async (id) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/bookings/${id}`)
    return response.data
  } catch (err) {
    console.error('Hiba a foglalás törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Összes foglalás lekérése (adminisztratív nézet)
const getAllBookings = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/bookings/getAll')
    bookings.value = response.data.data || response.data
    return bookings.value
  } catch (err) {
    console.error('Hiba az összes foglalás lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Tulajdonos foglalásai
const getOwnerBookings = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/owner/bookings')
    bookings.value = response.data.data || response.data
    return bookings.value
  } catch (err) {
    console.error('Hiba a tulajdonos foglalásainak lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Foglalási árak lekérése
const getPrices = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/bookings/prices')
    prices.value = response.data.data || response.data
    return prices.value
  } catch (err) {
    console.error('Hiba az árak lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Foglalások keresése
const searchBookings = async (params) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/booking/search', { params })
    bookings.value = response.data.data || response.data
    return bookings.value
  } catch (err) {
    console.error('Hiba a foglalások keresésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Foglalás státuszának frissítése
const updateBookingStatus = async (id, status) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.patch(`/bookings/${id}/status`, { status })
    booking.value = response.data.data || response.data
    return booking.value
  } catch (err) {
    console.error('Hiba a foglalás státuszának frissítésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// QR kód lekérése a foglaláshoz
const getQrCode = async (id) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/bookings/${id}/qr-code`)
    return response.data
  } catch (err) {
    console.error('Hiba a QR kód lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// QR kód beolvasása
const scanQrCode = async (data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post('/bookings/scan', data)
    return response.data
  } catch (err) {
    console.error('Hiba a QR kód beolvasásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

export function useBooking() {
  return {
    booking,
    bookings,
    searchResults,
    campingDetails,
    campingList,
    campingSpotList,
    campingAvailability,
    prices,
    loading,
    error,
    searchGlobal,
    getCampingList,
    getCampingDetails,
    getCampingSpotList,
    getCampingAvailability,
    getMyBookings,
    getBooking,
    createBooking,
    updateBooking,
    deleteBooking,
    getAllBookings,
    getOwnerBookings,
    getPrices,
    searchBookings,
    updateBookingStatus,
    getQrCode,
    scanQrCode
  }
}