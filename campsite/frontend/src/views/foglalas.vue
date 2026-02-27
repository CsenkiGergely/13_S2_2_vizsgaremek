<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'
import { useAuth } from '../composables/useAuth'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const route = useRoute()
const router = useRouter()
const { isAuthenticated } = useAuth()

// Állapotok
const camping = ref(null)
const comments = ref([])
const loading = ref(true)
const error = ref(null)
const currentImage = ref(0)
const selectedSpot = ref(null)
const bookingLoading = ref(false)
const bookingError = ref(null)

// Foglalási form
const bookingForm = ref({
  checkIn: '',
  checkOut: '',
  guests: 2
})

// Naptár állapot
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

// Kemping adatok betöltése
const fetchCamping = async () => {
  const id = route.params.id
  if (!id) {
    error.value = 'Nincs megadva kemping azonosító.'
    loading.value = false
    return
  }
  try {
    loading.value = true
    const [campingRes, commentsRes] = await Promise.all([
      api.get(`/campings/${id}`),
      api.get(`/campings/${id}/comments`).catch(() => ({ data: { data: [] } }))
    ])
    camping.value = campingRes.data
    comments.value = commentsRes.data.comments || commentsRes.data.data || []
  } catch (err) {
    console.error('Hiba a kemping betöltésekor:', err)
    error.value = 'Nem sikerült betölteni a kemping adatait.'
  } finally {
    loading.value = false
  }
}

// Computed adatok
const images = computed(() => {
  if (!camping.value) return []
  if (camping.value.photos && camping.value.photos.length > 0) {
    return camping.value.photos.map(p => p.photo_url)
  }
  return ['https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800']
})

const spots = computed(() => {
  if (!camping.value || !camping.value.spots) return []
  return camping.value.spots
})

const tags = computed(() => {
  if (!camping.value || !camping.value.tags) return []
  return camping.value.tags
})

const location = computed(() => {
  if (!camping.value || !camping.value.location) return null
  return camping.value.location
})

const locationText = computed(() => {
  if (!location.value) return ''
  const l = location.value
  return [l.zip_code, l.city, l.street_address].filter(Boolean).join(', ')
})

const pricePerNight = computed(() => {
  if (selectedSpot.value) return selectedSpot.value.price_per_night
  return camping.value?.min_price || 0
})

const nightCount = computed(() => {
  if (!bookingForm.value.checkIn || !bookingForm.value.checkOut) return 0
  const checkIn = new Date(bookingForm.value.checkIn)
  const checkOut = new Date(bookingForm.value.checkOut)
  const diff = checkOut - checkIn
  const nights = Math.ceil(diff / (1000 * 60 * 60 * 24))
  return nights > 0 ? nights : 0
})

const totalPrice = computed(() => {
  return nightCount.value * pricePerNight.value
})

const avgRating = computed(() => {
  return camping.value?.average_rating || 0
})

const reviewsCount = computed(() => {
  return camping.value?.reviews_count || 0
})

const hasGeoJson = computed(() => {
  return !!(camping.value?.geojson)
})

// Galéria navigáció
const nextImage = () => {
  currentImage.value = (currentImage.value + 1) % images.value.length
}
const prevImage = () => {
  currentImage.value = (currentImage.value - 1 + images.value.length) % images.value.length
}

//  Naptár
const monthNames = ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
  'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December']

const currentMonthName = computed(() => monthNames[currentMonth.value])

const previousMonth = () => {
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value-- }
  else currentMonth.value--
}
const nextMonth = () => {
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++ }
  else currentMonth.value++
}

const calendarDays = computed(() => {
  const days = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)

  let startDay = firstDay.getDay() - 1
  if (startDay < 0) startDay = 6

  const prevMonthLastDay = new Date(currentYear.value, currentMonth.value, 0).getDate()
  for (let i = startDay - 1; i >= 0; i--) {
    const day = prevMonthLastDay - i
    days.push({
      day, date: '', isCurrentMonth: false, isDisabled: true,
      isSelected: false, isInRange: false, key: `prev-${day}`
    })
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(currentYear.value, currentMonth.value, day)
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    const isPast = date < today
    const isCheckIn = dateStr === bookingForm.value.checkIn
    const isCheckOut = dateStr === bookingForm.value.checkOut
    const isInRange = bookingForm.value.checkIn && bookingForm.value.checkOut &&
      dateStr > bookingForm.value.checkIn && dateStr < bookingForm.value.checkOut

    days.push({
      day, date: dateStr, isCurrentMonth: true, isDisabled: isPast,
      isSelected: isCheckIn || isCheckOut, isInRange,
      key: `current-${day}`
    })
  }

  const remainingDays = 42 - days.length
  for (let day = 1; day <= remainingDays; day++) {
    days.push({
      day, date: '', isCurrentMonth: false, isDisabled: true,
      isSelected: false, isInRange: false, key: `next-${day}`
    })
  }
  return days
})

const selectDate = (dateObj) => {
  if (dateObj.isDisabled || !dateObj.isCurrentMonth) return
  if (!bookingForm.value.checkIn || (bookingForm.value.checkIn && bookingForm.value.checkOut)) {
    bookingForm.value.checkIn = dateObj.date
    bookingForm.value.checkOut = ''
  } else if (bookingForm.value.checkIn && !bookingForm.value.checkOut) {
    if (dateObj.date < bookingForm.value.checkIn) {
      bookingForm.value.checkIn = dateObj.date
    } else if (dateObj.date === bookingForm.value.checkIn) {
      return
    } else {
      bookingForm.value.checkOut = dateObj.date
    }
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getFullYear()}. ${String(d.getMonth() + 1).padStart(2, '0')}. ${String(d.getDate()).padStart(2, '0')}.`
}

// Spot kiválasztás
const selectSpot = (spot) => {
  if (!spot.is_available) return
  selectedSpot.value = spot
}

// Foglalás létrehozása
const handleBooking = async () => {
  bookingError.value = null

  if (!isAuthenticated.value) {
    bookingError.value = 'A foglaláshoz be kell jelentkezned!'
    return
  }
  if (!bookingForm.value.checkIn || !bookingForm.value.checkOut) {
    bookingError.value = 'Kérlek válassz érkezési és távozási dátumot!'
    return
  }
  if (nightCount.value <= 0) {
    bookingError.value = 'A távozás dátumának az érkezés után kell lennie!'
    return
  }
  if (!selectedSpot.value) {
    bookingError.value = 'Kérlek válassz egy helyet a térképen vagy a listából!'
    return
  }
  if (bookingForm.value.guests > selectedSpot.value.capacity) {
    bookingError.value = `A kiválasztott hely maximum ${selectedSpot.value.capacity} fős!`
    return
  }

  try {
    bookingLoading.value = true
    const response = await api.post('/bookings', {
      camping_id: camping.value.id,
      camping_spot_id: selectedSpot.value.spot_id,
      arrival_date: bookingForm.value.checkIn,
      departure_date: bookingForm.value.checkOut,
      guests: bookingForm.value.guests
    })
    const booking = response.data.booking || response.data
    router.push({
      path: '/fizetes',
      query: {
        bookingId: booking.id,
        total: totalPrice.value,
        nights: nightCount.value,
        campingName: camping.value.camping_name,
        spotName: selectedSpot.value.name
      }
    })
  } catch (err) {
    console.error('Foglalási hiba:', err)
    bookingError.value = err.response?.data?.message || 'Nem sikerült a foglalás. Próbáld újra!'
  } finally {
    bookingLoading.value = false
  }
}

// Térkép inicializálás
let map = null
const initMap = async () => {
  await nextTick()
  const mapEl = document.getElementById('campingMap')
  if (!mapEl || map) return

  const lat = location.value?.latitude || 47.4979
  const lng = location.value?.longitude || 19.0402

  map = L.map('campingMap', { zoomControl: true }).setView([lat, lng], 16)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 20
  }).addTo(map)

  // GeoJSON renderelés — a kempinghez hozzárendelt teljes FeatureCollection
  const geojsonData = camping.value.geojson
  if (geojsonData) {
    try {
      const parsed = typeof geojsonData === 'string' ? JSON.parse(geojsonData) : geojsonData
      const geojsonLayer = L.geoJSON(parsed, {
        style: (feature) => {
      // Feature properties-ből stílus, ha van – különben alapértelmezett
          const props = feature.properties || {}
          return {
            color: props.stroke || '#16a34a',
            weight: props['stroke-width'] || 2,
            opacity: props['stroke-opacity'] || 1,
            fillColor: props.fill || '#bbf7d0',
            fillOpacity: props['fill-opacity'] || 0.2
          }
        },
        pointToLayer: (feature, latlng) => {
          return L.circleMarker(latlng, {
            radius: 8,
            fillColor: feature.properties?.['marker-color'] || '#16a34a',
            color: '#fff',
            weight: 2,
            fillOpacity: 0.8
          })
        },
        onEachFeature: (feature, layer) => {
          if (feature.properties) {
            const props = feature.properties
            const parts = []
            if (props.name) parts.push(`<b>${props.name}</b>`)
            if (props.description) parts.push(props.description)
            if (parts.length > 0) {
              layer.bindPopup(parts.join('<br>'))
            }
          }
        }
      }).addTo(map)

      // Térkép igazítása a GeoJSON kiterjedéséhez
      const bounds = geojsonLayer.getBounds()
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [30, 30] })
      }
    } catch (e) { console.warn('GeoJSON parse error:', e) }
  }

  // Spot markerek (ha van row/column pozíciójuk)
  spots.value.forEach(spot => {
    if (spot.row == null || spot.column == null) return
    const spotLat = lat + (spot.row || 0) * 0.0001
    const spotLng = lng + (spot.column || 0) * 0.0001
    const color = !spot.is_available ? '#ef4444' : '#22c55e'

    const marker = L.circleMarker([spotLat, spotLng], {
      radius: 10, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.9
    }).addTo(map)

    marker.bindTooltip(`${spot.name || 'Hely'} · ${spot.type} · ${spot.price_per_night} Ft/éj`, { direction: 'top', offset: [0, -10] })

    marker.on('click', () => {
      if (!spot.is_available) return
      selectedSpot.value = spot
    })
  })
}

// Inicializálás
onMounted(async () => {
  if (route.query.checkIn) bookingForm.value.checkIn = route.query.checkIn
  if (route.query.checkOut) bookingForm.value.checkOut = route.query.checkOut
  if (route.query.guests) bookingForm.value.guests = parseInt(route.query.guests) || 2

  await fetchCamping()
  if (camping.value && hasGeoJson.value) {
    await nextTick()
    initMap()
  }
})
</script>

<template>
  <div class="container mx-auto px-4 py-6 space-y-6">

    <!-- Betöltés -->
    <div v-if="loading" class="text-center py-20">
      <p class="text-lg text-gray-500">Kemping betöltése...</p>
    </div>

    <!-- Hiba -->
    <div v-else-if="error" class="text-center py-20">
      <p class="text-lg text-red-600">{{ error }}</p>
      <router-link to="/kereses" class="text-[#4A7434] underline mt-4 inline-block">Vissza a kereséshez</router-link>
    </div>

    <!-- Tartalom -->
    <template v-else-if="camping">

      <!-- Képgaléria -->
      <div class="relative rounded-xl overflow-hidden shadow-lg" v-if="images.length > 0">
        <img :src="images[currentImage]" :alt="camping.camping_name" class="w-full h-96 md:h-[500px] object-cover"
             @error="$event.target.src = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800'" />
        <template v-if="images.length > 1">
          <button @click="prevImage"
                  class="gallery-btn absolute left-3 top-1/2 -translate-y-1/2">&#8249;</button>
          <button @click="nextImage"
                  class="gallery-btn absolute right-3 top-1/2 -translate-y-1/2">&#8250;</button>
          <div class="absolute bottom-3 right-3 bg-black/50 text-white text-xs px-3 py-1 rounded-full">
            {{ currentImage + 1 }} / {{ images.length }}
          </div>
        </template>
      </div>

      <!-- 2 oszlopos elrendezés -->
      <div class="flex flex-col md:flex-row gap-6">

        <!-- Bal oldal: Információk -->
        <div class="md:w-2/3 space-y-6">

          <!-- Címsor és értékelés -->
          <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ camping.camping_name }}</h1>
            <div class="flex items-center gap-4 text-sm text-gray-600 flex-wrap">
              <div class="flex items-center gap-1" v-if="avgRating > 0">
                <span class="text-yellow-500">&#11088;</span>
                <span class="font-semibold text-gray-800">{{ avgRating.toFixed(1) }}</span>
                <span class="text-gray-500">({{ reviewsCount }} értékelés)</span>
              </div>
              <span class="text-gray-500" v-if="locationText">&#128205; {{ locationText }}</span>
            </div>
          </div>

          <!-- Tagek / tulajdonságok -->
          <div class="flex flex-wrap gap-3" v-if="tags.length > 0">
            <div v-for="tag in tags" :key="tag.id"
                 class="flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg text-sm">
              <span class="font-medium text-gray-700">{{ tag.tag }}</span>
            </div>
          </div>

          <!-- Leírás -->
          <div class="border-t border-gray-200 pt-6" v-if="camping.description">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">A szállásról</h2>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ camping.description }}</p>
          </div>

          <!-- Helyek / Spot-ok -->
          <div class="border-t border-gray-200 pt-6" v-if="spots.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Helyek</h2>

            <!-- Jelmagyarázat + Térkép (csak ha van GeoJSON) -->
            <template v-if="hasGeoJson">
              <div class="flex gap-4 mb-4 text-sm">
                <span class="flex items-center gap-1">
                  <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
                  <span class="text-gray-600">Szabad</span>
                </span>
                <span class="flex items-center gap-1">
                  <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                  <span class="text-gray-600">Foglalt</span>
                </span>
                <span class="flex items-center gap-1" v-if="selectedSpot">
                  <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                  <span class="text-gray-600">Kiválasztva</span>
                </span>
              </div>

              <div id="campingMap" class="w-full rounded-lg border border-gray-200 mb-4" style="height: 400px; z-index: 0;"></div>
            </template>

            <!-- Spot kártyák -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div v-for="spot in spots" :key="spot.spot_id"
                   @click="selectSpot(spot)"
                   :class="[
                     'p-4 rounded-lg border-2 cursor-pointer transition-all',
                     !spot.is_available ? 'bg-red-50 border-red-200 opacity-60 cursor-not-allowed' : '',
                     spot.is_available && selectedSpot?.spot_id !== spot.spot_id ? 'bg-white border-gray-200 hover:border-[#4A7434]' : '',
                     selectedSpot?.spot_id === spot.spot_id ? 'bg-green-50 border-[#4A7434] ring-2 ring-[#4A7434]/20' : ''
                   ]">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-semibold text-gray-800">{{ spot.name || `Hely #${spot.spot_id}` }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ spot.type }} · Max {{ spot.capacity }} fő</p>
                    <p class="text-xs text-gray-400 mt-1" v-if="spot.description">{{ spot.description }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-[#4A7434]">{{ spot.price_per_night?.toLocaleString('hu-HU') }} Ft</p>
                    <p class="text-xs text-gray-500">/ éjszaka</p>
                  </div>
                </div>
                <div class="mt-2">
                  <span v-if="!spot.is_available" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Foglalt</span>
                  <span v-else-if="selectedSpot?.spot_id === spot.spot_id" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">&#10003; Kiválasztva</span>
                  <span v-else class="text-xs bg-green-50 text-green-600 px-2 py-0.5 rounded-full">Szabad</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Vendég vélemények -->
          <div class="border-t border-gray-200 pt-6" v-if="comments.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Vendég vélemények</h2>
            <div class="flex items-center gap-2 mb-6" v-if="avgRating > 0">
              <span class="text-yellow-500 text-3xl">&#9733;</span>
              <span class="text-2xl font-bold text-gray-800">{{ avgRating.toFixed(1) }}</span>
              <span class="text-gray-500">· {{ reviewsCount }} értékelés</span>
            </div>

            <div class="space-y-4">
              <div v-for="comment in comments" :key="comment.id" class="pb-4 border-b border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                  <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-semibold text-gray-600">
                    {{ comment.user?.owner_first_name?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                  <div>
                    <p class="font-medium text-gray-800">
                      {{ comment.user ? `${comment.user.owner_last_name || ''} ${comment.user.owner_first_name || ''}`.trim() : 'Ismeretlen' }}
                    </p>
                    <p class="text-xs text-gray-500">{{ new Date(comment.created_at).toLocaleDateString('hu-HU') }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-1 mb-2" v-if="comment.rating">
                  <span v-for="i in 5" :key="i" class="text-xl" :class="i <= comment.rating ? 'text-yellow-400' : 'text-gray-300'">&#9733;</span>
                  <span class="text-sm font-semibold text-gray-700 ml-1">{{ comment.rating }}/5</span>
                </div>
                <p class="text-gray-600" v-if="comment.comment">{{ comment.comment }}</p>

                <!-- Válaszok -->
                <div v-if="comment.children_recursive && comment.children_recursive.length > 0" class="ml-12 mt-3 space-y-3">
                  <div v-for="reply in comment.children_recursive" :key="reply.id" class="bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center gap-2 mb-1">
                      <p class="font-medium text-gray-700 text-sm">
                        {{ reply.user ? `${reply.user.owner_last_name || ''} ${reply.user.owner_first_name || ''}`.trim() : 'Tulajdonos' }}
                      </p>
                      <p class="text-xs text-gray-400">{{ new Date(reply.created_at).toLocaleDateString('hu-HU') }}</p>
                    </div>
                    <p class="text-gray-600 text-sm">{{ reply.comment }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Jobb oldal: Foglalási panel -->
        <div class="md:w-1/3">
          <div class="booking-panel bg-white p-5 rounded-xl shadow-md">
            <!-- Ár -->
            <div class="mb-4">
              <div class="flex items-baseline gap-2 mb-1">
                <span class="text-2xl font-bold text-gray-800">{{ pricePerNight.toLocaleString('hu-HU') }} Ft</span>
                <span class="text-gray-500">/ éjszaka</span>
              </div>
              <p class="text-xs text-gray-400" v-if="!selectedSpot && camping.min_price !== camping.max_price">
                Helytől függően {{ camping.min_price?.toLocaleString('hu-HU') }} – {{ camping.max_price?.toLocaleString('hu-HU') }} Ft
              </p>
              <p class="text-xs text-[#4A7434]" v-if="selectedSpot">
                {{ selectedSpot.name }} · {{ selectedSpot.type }}
              </p>
            </div>

            <!-- Dátum választó kártyák -->
            <div class="grid grid-cols-2 gap-2 mb-4">
              <div class="border border-gray-300 rounded-lg p-3 cursor-pointer hover:border-gray-400 transition"
                   :class="{ 'border-[#4A7434]': bookingForm.checkIn && !bookingForm.checkOut }">
                <label class="text-xs font-semibold text-gray-600 uppercase block">Érkezés</label>
                <div class="text-sm mt-1 text-gray-700">
                  {{ bookingForm.checkIn ? formatDate(bookingForm.checkIn) : 'Válassz' }}
                </div>
              </div>
              <div class="border border-gray-300 rounded-lg p-3 cursor-pointer hover:border-gray-400 transition">
                <label class="text-xs font-semibold text-gray-600 uppercase block">Távozás</label>
                <div class="text-sm mt-1 text-gray-700">
                  {{ bookingForm.checkOut ? formatDate(bookingForm.checkOut) : 'Válassz' }}
                </div>
              </div>
            </div>

            <!-- Naptár -->
            <div class="mb-4 border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-4">
                <button @click="previousMonth" type="button" class="nav-btn p-1 hover:bg-gray-100 rounded text-lg">&#8249;</button>
                <span class="font-semibold text-gray-800">{{ currentMonthName }} {{ currentYear }}</span>
                <button @click="nextMonth" type="button" class="nav-btn p-1 hover:bg-gray-100 rounded text-lg">&#8250;</button>
              </div>
              <div class="grid grid-cols-7 gap-1 mb-2">
                <div v-for="day in ['H', 'K', 'Sz', 'Cs', 'P', 'Sz', 'V']" :key="day"
                     class="text-center text-xs font-medium text-gray-500 py-1">{{ day }}</div>
              </div>
              <div class="grid grid-cols-7 gap-1">
                <div v-for="date in calendarDays" :key="date.key"
                     @click="selectDate(date)"
                     :class="[
                       'text-center py-2 text-sm rounded-full cursor-pointer transition-colors',
                       date.isCurrentMonth ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-300',
                       date.isDisabled ? 'cursor-not-allowed opacity-30 hover:bg-transparent' : '',
                       date.isSelected ? 'bg-[#4A7434] text-white hover:bg-[#4A7434]' : '',
                       date.isInRange ? 'bg-[#E8F5E9]' : ''
                     ]">
                  {{ date.day }}
                </div>
              </div>
            </div>

            <!-- Vendégek -->
            <div class="border border-gray-300 rounded-lg p-3 mb-4">
              <label class="text-xs font-semibold text-gray-600 uppercase">Vendégek</label>
              <select v-model.number="bookingForm.guests" class="w-full text-sm mt-1 border-0 p-0 focus:ring-0 text-gray-700 bg-transparent">
                <option v-for="n in (selectedSpot ? selectedSpot.capacity : 10)" :key="n" :value="n">{{ n }} vendég</option>
              </select>
            </div>

            <!-- Kiválasztott hely info -->
            <div v-if="selectedSpot" class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
              <p class="font-semibold text-sm text-gray-800">{{ selectedSpot.name }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ selectedSpot.type }} · Max {{ selectedSpot.capacity }} fő · {{ selectedSpot.price_per_night?.toLocaleString('hu-HU') }} Ft/éj</p>
            </div>
            <div v-else class="mb-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
              <p class="text-xs text-yellow-700">Válassz egy helyet a foglaláshoz!</p>
            </div>

            <!-- Hiba üzenet -->
            <div v-if="bookingError" class="mb-4 p-3 bg-red-50 rounded-lg border border-red-200">
              <p class="text-sm text-red-600">{{ bookingError }}</p>
            </div>

            <!-- Foglalás gomb -->
            <button
              @click="handleBooking"
              :disabled="bookingLoading"
              class="w-full bg-[#4A7434] hover:bg-[#3d6129] text-white font-semibold py-4 rounded-lg transition-colors duration-200 disabled:opacity-50">
              {{ bookingLoading ? 'Foglalás folyamatban...' : 'Foglalás' }}
            </button>

            <p class="text-center text-xs text-gray-500 mt-3">Még nem kerül felszámításra</p>

            <!-- Összesítő -->
            <div v-if="bookingForm.checkIn && bookingForm.checkOut && nightCount > 0 && selectedSpot"
                 class="mt-6 pt-6 border-t border-gray-200 space-y-2">
              <div class="flex justify-between text-sm text-gray-600">
                <span class="underline">{{ pricePerNight.toLocaleString('hu-HU') }} Ft × {{ nightCount }} éjszaka</span>
                <span>{{ totalPrice.toLocaleString('hu-HU') }} Ft</span>
              </div>
              <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-200 text-gray-800">
                <span>Összesen</span>
                <span>{{ totalPrice.toLocaleString('hu-HU') }} Ft</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
  .gallery-btn {
    background: rgba(255,255,255,0.8);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    color: #333;
  }

  .gallery-btn:hover {
    background: rgba(255,255,255,1);
  }

  .nav-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #555;
    width: auto;
    padding: 4px 8px;
  }

  .nav-btn:hover {
    background: #f3f4f6;
    border-radius: 6px;
  }

  .booking-panel {
    position: sticky;
    top: 90px;
    z-index: 10;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
  }

  img {
    border-radius: 0.5rem;
    object-fit: cover;
  }
  
</style>
