import { ref } from 'vue'
import api from '../api/axios'

const campingDetails = ref(null)
const campingList = ref([])
const campingSpotList = ref([])
const campingSpotDetails = ref(null)
const campingPhotoList = ref([])
const campingPhotoDetails = ref(null)
const campingTagList = ref([])
const campingAvailability = ref([])
const campingGeojson = ref(null)
const loading = ref(false)
const error = ref(null)

const getErrorMessage = (err) => {
  return err.response?.data?.message || err.message
}

// Összes kemping lekérése
const getCampingList = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/campings')
    campingList.value = response.data.data || response.data
    return campingList.value
  } catch (err) {
    console.error('Hiba a kempingek lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Konkrét kemping lekérése
const getCampingDetails = async (id) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${id}`)
    campingDetails.value = response.data.data || response.data
    return campingDetails.value
  } catch (err) {
    console.error('Hiba a kemping lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Új kemping létrehozása
const createCamping = async (data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post('/campings', data)
    campingDetails.value = response.data.data || response.data
    return campingDetails.value
  } catch (err) {
    console.error('Hiba a kemping létrehozásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping frissítése
const updateCamping = async (id, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.put(`/campings/${id}`, data)
    campingDetails.value = response.data.data || response.data
    return campingDetails.value
  } catch (err) {
    console.error('Hiba a kemping frissítésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping törlése
const deleteCamping = async (id) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${id}`)
    return response.data
  } catch (err) {
    console.error('Hiba a kemping törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping helyeinek lekérése
const getCampingSpotList = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/spots`)
    campingSpotList.value = response.data.data || response.data
    return campingSpotList.value
  } catch (err) {
    console.error('Hiba a kemping helyeinek lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Konkrét kempinghely lekérése
const getCampingSpotDetails = async (campingId, spotId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/spots/${spotId}`)
    campingSpotDetails.value = response.data.data || response.data
    return campingSpotDetails.value
  } catch (err) {
    console.error('Hiba a kempinghely lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping elérhetőségének lekérése
const getCampingAvailability = async (campingId, params) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/availability`, { params })
    campingAvailability.value = response.data.data || response.data
    return campingAvailability.value
  } catch (err) {
    console.error('Hiba az elérhetőség lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Új kempinghely létrehozása
const createCampingSpot = async (campingId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/spots`, data)
    campingSpotDetails.value = response.data.data || response.data
    return campingSpotDetails.value
  } catch (err) {
    console.error('Hiba a kempinghely létrehozásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kempinghely frissítése
const updateCampingSpot = async (campingId, spotId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.put(`/campings/${campingId}/spots/${spotId}`, data)
    campingSpotDetails.value = response.data.data || response.data
    return campingSpotDetails.value
  } catch (err) {
    console.error('Hiba a kempinghely frissítésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kempinghely törlése
const deleteCampingSpot = async (campingId, spotId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/spots/${spotId}`)
    return response.data
  } catch (err) {
    console.error('Hiba a kempinghely törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping fotóinak lekérése
const getCampingPhotoList = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/photos`)
    campingPhotoList.value = response.data.data || response.data
    return campingPhotoList.value
  } catch (err) {
    console.error('Hiba a kemping fotóinak lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping fotójának feltöltése
const uploadCampingPhoto = async (campingId, file) => {
  loading.value = true
  error.value = null
  try {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await api.post(`/campings/${campingId}/photos`, formData)
    campingPhotoDetails.value = response.data.data || response.data
    return campingPhotoDetails.value
  } catch (err) {
    console.error('Hiba a fotó feltöltésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping fotójának hozzáadása URL-ről
const addCampingPhotoByUrl = async (campingId, url) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/photos/url`, { url })
    campingPhotoDetails.value = response.data.data || response.data
    return campingPhotoDetails.value
  } catch (err) {
    console.error('Hiba a fotó URL-ről történő hozzáadásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping fotójának törlése
const deleteCampingPhoto = async (campingId, photoId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/photos/${photoId}`)
    return response.data
  } catch (err) {
    console.error('Hiba a fotó törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Kemping tagjeinek lekérése
const getCampingTagList = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/tags`)
    campingTagList.value = response.data.data || response.data
    return campingTagList.value
  } catch (err) {
    console.error('Hiba a tagok lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Új tag hozzáadása
const addCampingTag = async (campingId, data) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.post(`/campings/${campingId}/tags`, data)
    return response.data.data || response.data
  } catch (err) {
    console.error('Hiba a tag hozzáadásakor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// Tag törlése
const deleteCampingTag = async (campingId, tagId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/tags/${tagId}`)
    return response.data
  } catch (err) {
    console.error('Hiba a tag törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// GeoJSON lekérése
const getCampingGeojson = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/campings/${campingId}/geojson`)
    campingGeojson.value = response.data.data || response.data
    return campingGeojson.value
  } catch (err) {
    console.error('Hiba a GeoJSON lekérésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// GeoJSON feltöltése (fájlként)
const uploadCampingGeojson = async (campingId, file) => {
  loading.value = true
  error.value = null
  try {
    const formData = new FormData()
    formData.append('geojson', file)
    const response = await api.post(`/campings/${campingId}/geojson`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data.data || response.data
  } catch (err) {
    console.error('Hiba a GeoJSON feltöltésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

// GeoJSON törlése
const deleteCampingGeojson = async (campingId) => {
  loading.value = true
  error.value = null
  try {
    const response = await api.delete(`/campings/${campingId}/geojson`)
    return response.data
  } catch (err) {
    console.error('Hiba a GeoJSON törlésekor:', err)
    error.value = getErrorMessage(err)
    throw err
  } finally {
    loading.value = false
  }
}

export function useCamping() {
  return {
    campingDetails,
    campingList,
    campingSpotList,
    campingSpotDetails,
    campingPhotoList,
    campingPhotoDetails,
    campingTagList,
    campingAvailability,
    campingGeojson,
    loading,
    error,
    getCampingList,
    getCampingDetails,
    createCamping,
    updateCamping,
    deleteCamping,
    getCampingSpotList,
    getCampingSpotDetails,
    getCampingAvailability,
    createCampingSpot,
    updateCampingSpot,
    deleteCampingSpot,
    getCampingPhotoList,
    uploadCampingPhoto,
    addCampingPhotoByUrl,
    deleteCampingPhoto,
    getCampingTagList,
    addCampingTag,
    deleteCampingTag,
    getCampingGeojson,
    uploadCampingGeojson,
    deleteCampingGeojson
  }
}