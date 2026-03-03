import { ref } from 'vue'
import api from '../api/axios'

const gates = ref([])
const myCampings = ref([])
const loading = ref(false)
const error = ref(null)

/**
 * Bejelentkezett felhasználó saját kempingjeinek lekérése (dropdown-hoz).
 */
const fetchMyCampings = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/my-campings')
    myCampings.value = response.data
    return myCampings.value
  } catch (err) {
    console.error('Saját kempingek lekérése sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Egy adott kemping kapuinak lekérése.
 */
const fetchGates = async (campingId) => {
  if (!campingId) {
    gates.value = []
    return []
  }
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/gates`)
    gates.value = response.data
    return gates.value
  } catch (err) {
    console.error('Kapuk lekérése sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Új kapu létrehozása.
 */
const createGate = async (campingId, gateData) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/gates`, gateData)
    // Frissítjük a listát
    await fetchGates(campingId)
    return response.data
  } catch (err) {
    console.error('Kapu létrehozása sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Kapu módosítása.
 */
const updateGate = async (campingId, gateId, gateData) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.put(`/campings/${campingId}/gates/${gateId}`, gateData)
    await fetchGates(campingId)
    return response.data
  } catch (err) {
    console.error('Kapu módosítása sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Kapu törlése.
 */
const deleteGate = async (campingId, gateId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/gates/${gateId}`)
    await fetchGates(campingId)
    return response.data
  } catch (err) {
    console.error('Kapu törlése sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Token generálása egy kapuhoz.
 */
const generateToken = async (campingId, gateId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/gates/${gateId}/auth-token`)
    return response.data
  } catch (err) {
    console.error('Token generálása sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

/**
 * Token visszavonása egy kapuról.
 */
const revokeToken = async (campingId, gateId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/gates/${gateId}/auth-token`)
    await fetchGates(campingId)
    return response.data
  } catch (err) {
    console.error('Token visszavonása sikertelen:', err)
    error.value = err.response?.data?.message || err.message
    throw err
  } finally {
    loading.value = false
  }
}

export function useGate() {
  return {
    gates,
    myCampings,
    loading,
    error,
    fetchMyCampings,
    fetchGates,
    createGate,
    updateGate,
    deleteGate,
    generateToken,
    revokeToken,
  }
}
