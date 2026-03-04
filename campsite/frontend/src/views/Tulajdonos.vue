<script setup>
import { ref, onMounted, watch } from 'vue'
import { useBooking } from '../composables/useBooking'
import { useGate } from '../composables/useGate'
import { useDashboard } from '../composables/useDashboard'
import { useCamping } from '../composables/useCamping'
import AuthModal from '../components/AuthModal.vue'
import dayjs from 'dayjs';
import "dayjs/locale/hu";
import { name } from 'dayjs/locale/hu';
dayjs.locale("hu");

const { bookings, getAllBookings, prices, getPrices } = useBooking()
const {
  gates, myCampings, loading: gatesLoading, error: gatesError,
  fetchMyCampings, fetchGates, createGate, updateGate, deleteGate: apiDeleteGate,
  generateToken, revokeToken,
} = useGate()
const { dashboard, getDashboard } = useDashboard()
const { createCamping, campingTagList, addCampingTag, deleteCampingTag, createCampingSpot, getCampingSpotList, getCampingTagList, loading: campingLoading, error: campingError } = useCamping()

const activeTab = ref('dashboard')
const monthlyRevenue = ref(0)
const averageBookingValue = ref(0)
const previousAverageBookingValue = ref(0)
const revenueByType = ref([])
const priceByBookingId = ref({})
const isAuthenticated = ref(false)
const authModalOpen = ref(false)
const authModalMode = ref('login')

const checkAuthentication = () => {
  const token = localStorage.getItem('auth_token')
  isAuthenticated.value = !!token
  return !!token
}

const formatChange = (current, previous) => {
  if (previous === undefined || previous === null) return ''
  const curr = Number(current || 0)
  const prev = Number(previous || 0)
  if (prev === 0) {
    return curr > 0 ? '+100%' : '0%'
  }
  const change = Math.round(((curr - prev) / prev) * 100)
  return (change > 0 ? '+' : '') + change + '%'
}

const loadData = async () => {
  if (!checkAuthentication()) {
    console.warn('Nincs bejelentkezve. Kérjük, lépjen be az alkalmazásba.')
    return
  }
  
  try {
    // Dashboard adatok betöltése
    await getDashboard()
    
    // Foglalások és árak betöltése
    await getAllBookings()
    await getPrices()
    
    // Bevétel típusok szerint
    if (bookings.value && prices.value) {
      const bookingList = Array.isArray(bookings.value) ? bookings.value : []
      const priceMap = Array.isArray(prices.value)
        ? prices.value.reduce((map, item, index) => {
            const bookingId = item.booking_id || item.bookingId || item.id || bookingList[index]?.id
            const bookingPrice = Number(item.price || item.total_price || 0)

            if (bookingId) {
              map[bookingId] = bookingPrice
            }

            return map
          }, {})
        : {}

      priceByBookingId.value = priceMap
      
      revenueByType.value = calculateRevenueByType(bookings.value, priceMap)

      // Számítsuk ki az átlagos foglalási értéket a betöltött árak alapján
      const bookingCount = bookingList.length
      const totalPrice = Object.values(priceMap).reduce((sum, v) => sum + Number(v || 0), 0)
      averageBookingValue.value = bookingCount > 0 ? Math.round(totalPrice / bookingCount) : 0

      // Számítsuk ki az előző hónap átlagos foglalási értékét (ha vannak adatok)
      const now = new Date()
      const prev = new Date(now.getFullYear(), now.getMonth() - 1)
      const prevMonth = prev.getMonth()
      const prevYear = prev.getFullYear()

      const prevBookings = bookingList.filter(b => {
        const dateStr = b.departure_date || b.checkOut || b.departureDate || b.created_at || b.createdAt
        if (!dateStr) return false
        const d = new Date(dateStr)
        return d.getMonth() === prevMonth && d.getFullYear() === prevYear
      })

      const totalPrevPrice = prevBookings.reduce((sum, b) => {
        return sum + Number(priceMap[b.id] || b.total_price || b.price || 0)
      }, 0)

      previousAverageBookingValue.value = prevBookings.length > 0 ? Math.round(totalPrevPrice / prevBookings.length) : 0
    }
  } catch (error) {
    console.error('Hiba az adatok betöltésekor:', error)
  }
}

// Bevétel típusok szerint
const calculateRevenueByType = (bookingsData, pricesData) => {
  if (!bookingsData || !pricesData) return []
  
  const typeMap = {}
  
  bookingsData.forEach(booking => {
    const type = booking.spot
      || booking.camping_spot?.type
      || booking.campingSpot?.type
      || booking.camping_spot?.name
      || booking.campingSpot?.name
      || 'Ismeretlen'

    const price = Number(
      pricesData[booking.id]
      || booking.total_price
      || booking.price
      || 0
    )
    
    if (!typeMap[type]) {
      typeMap[type] = {
        name: type,
        count: 0,
        revenue: 0,
        percentage: 0
      }
    }
    
    typeMap[type].count += 1
    typeMap[type].revenue += price
  })
  
  const totalRevenue = Object.values(typeMap).reduce((sum, item) => sum + item.revenue, 0)
  
  return Object.values(typeMap).map(item => ({
    ...item,
    percentage: totalRevenue > 0 ? Math.round((item.revenue / totalRevenue) * 100) : 0
  }))
}

const getBookingFirstName = (booking) => {
  return booking.guestfirstname
    || booking.guestFirstName
    || booking.user?.owner_first_name
    || '-'
}

const getBookingLastName = (booking) => {
  return booking.guestlastname
    || booking.guestLastName
    || booking.user?.owner_last_name
    || '-'
}

const getBookingSpot = (booking) => {
  return booking.spot
    || booking.campingSpot?.name
    || booking.camping_spot?.name
    || booking.campingSpot?.type
    || booking.camping_spot?.type
    || 'Ismeretlen'
}

const getBookingPrice = (booking) => {
  return Number(
    priceByBookingId.value[booking.id]
    || booking.total_price
    || booking.price
    || 0
  )
}

const formatBookingDate = (dateValue) => {
  return dateValue ? dayjs(dateValue).format('YYYY. MM D.') : '-'
}

const openLoginModal = () => {
  authModalMode.value = 'login'
  authModalOpen.value = true
}

const closeAuthModal = () => {
  authModalOpen.value = false
}

const handleAuthSuccess = () => {
  isAuthenticated.value = checkAuthentication()
  loadData()
}

// Kapuk kezelése
const selectedCampingId = ref(null)
const showGateModal = ref(false)
const showTokenModal = ref(false)
const showRenameModal = ref(false)
const generatedToken = ref(null)
const renameGate = ref({ id: null, name: '' })

const newGate = ref({
  campingId: null,
  openingTime: '08:00',
  closingTime: '20:00',
})

// Ha a felhasználó kempinget választ a szűrőben, töltjük a kapukat
watch(selectedCampingId, async (id) => {
  if (id) {
    await fetchGates(id)
  } else {
    gates.value = []
  }
})

function openGateModal() {
  newGate.value = {
    campingId: selectedCampingId.value || (myCampings.value.length === 1 ? myCampings.value[0].id : null),
    openingTime: '08:00',
    closingTime: '20:00',
  }
  showGateModal.value = true
}

async function addGate() {
  if (!newGate.value.campingId) return
  try {
    await createGate(newGate.value.campingId, {
      opening_time: newGate.value.openingTime,
      closing_time: newGate.value.closingTime,
      name: newGate.value.name,
    })
    showGateModal.value = false
    // Ha a kiválasztott kemping más, váltsunk rá
    if (selectedCampingId.value !== newGate.value.campingId) {
      selectedCampingId.value = newGate.value.campingId
    }
  } catch (err) {
    console.error('Kapu hozzáadása sikertelen:', err)
  }
}

async function handleDeleteGate(gateId) {
  if (!selectedCampingId.value) return
  if (!confirm('Biztosan törölni szeretnéd ezt a kaput?')) return
  try {
    await apiDeleteGate(selectedCampingId.value, gateId)
  } catch (err) {
    console.error('Kapu törlése sikertelen:', err)
  }
}

async function handleGenerateToken(gateId) {
  if (!selectedCampingId.value) return
  try {
    const result = await generateToken(selectedCampingId.value, gateId)
    generatedToken.value = result.auth_token
    showTokenModal.value = true
    // Frissítjük a kapuk listáját a token státusz miatt
    await fetchGates(selectedCampingId.value)
  } catch (err) {
    console.error('Token generálása sikertelen:', err)
  }
}

async function handleRevokeToken(gateId) {
  if (!selectedCampingId.value) return
  if (!confirm('Biztosan visszavonod a tokent? Az szkenner nem fog működni ezután.')) return
  try {
    await revokeToken(selectedCampingId.value, gateId)
  } catch (err) {
    console.error('Token visszavonása sikertelen:', err)
  }
}

function openRenameModal(gate) {
  renameGate.value = { id: gate.id, name: gate.name || '' }
  showRenameModal.value = true
}

async function handleRenameGate() {
  if (!selectedCampingId.value || !renameGate.value.id) return
  try {
    await updateGate(selectedCampingId.value, renameGate.value.id, {
      name: renameGate.value.name,
    })
    showRenameModal.value = false
  } catch (err) {
    console.error('Kapu átnevezése sikertelen:', err)
  }
}

function copyToken() {
  if (generatedToken.value) {
    navigator.clipboard.writeText(generatedToken.value)
    alert('Token vágólapra másolva!')
  }
}

// Új kemping hozzáadása
const newCampingForm = ref({
  camping_name: '',
  description: '',
  company_name: '',
  tax_id: '',
  billing_address: '',
  city: '',
  zip_code: '',
  street_address: '',
  latitude: '',
  longitude: '',
})
const campingFormError = ref(null)
const campingFormSuccess = ref(null)

// Tag kezelés - előre megadható, elküldés a kemping létrehozásával együtt
const showInfoNotice = ref(true)
const availableTags = [
  'Sátorhelyek',
  'Lakókocsi beálló',
  'Áramcsatlakozás',
  'Ivóvíz vételi hely',
  'Közös zuhanyzó',
  'Közös mosdó',
  'Mosókonyha',
  'Tűzrakóhely',
  'Játszótér',
  'Kutyabarát',
  'Kerékpárkölcsönzés',
  'Horgászási lehetőség',
  'Vízparti hozzáférés',
  'Árnyékos parcellák',
  'Hulladékgyűjtő pont',
]
const pendingTags = ref([])   // helyi lista, még nem mentve az API-ra

function toggleTag(tag) {
  if (pendingTags.value.includes(tag)) {
    pendingTags.value = pendingTags.value.filter(t => t !== tag)
  } else {
    pendingTags.value.push(tag)
  }
}

async function handleAddCamping() {
  campingFormError.value = null
  campingFormSuccess.value = null

  if (!newCampingForm.value.camping_name || !newCampingForm.value.description ||
      !newCampingForm.value.city || !newCampingForm.value.zip_code || !newCampingForm.value.street_address ||
      !newCampingForm.value.company_name || !newCampingForm.value.tax_id || !newCampingForm.value.billing_address) {
    campingFormError.value = 'Kérlek töltsd ki az összes kötelező mezőt!'
    return
  }

  try {
    const payload = {
      camping_name: newCampingForm.value.camping_name,
      description: newCampingForm.value.description,
      city: newCampingForm.value.city,
      zip_code: newCampingForm.value.zip_code,
      street_address: newCampingForm.value.street_address,
      company_name: newCampingForm.value.company_name,
      tax_id: newCampingForm.value.tax_id,
      billing_address: newCampingForm.value.billing_address,
    }
    if (newCampingForm.value.latitude) payload.latitude = parseFloat(newCampingForm.value.latitude)
    if (newCampingForm.value.longitude) payload.longitude = parseFloat(newCampingForm.value.longitude)

    const result = await createCamping(payload)
    const newId = result?.id || result?.camping?.id || null

    // Tagek mentése az újonnan létrehozott kempinghez
    if (newId && pendingTags.value.length > 0) {
      await Promise.all(pendingTags.value.map(tag => addCampingTag(newId, { tag })))
    }

    campingFormSuccess.value = 'Kemping sikeresen létrehozva!'
    newCampingForm.value = {
      camping_name: '', description: '', company_name: '', tax_id: '',
      billing_address: '', city: '', zip_code: '', street_address: '',
      latitude: '', longitude: '',
    }
    pendingTags.value = []
    await fetchMyCampings()
  } catch (err) {
    campingFormError.value = err.response?.data?.message || campingError.value || 'Hiba történt a kemping létrehozásakor.'
  }
}

// Áttekintés oldal
const overviewData = ref([])   // [{ camping, spots: [], tags: [] }]
const overviewLoading = ref(false)
const expandedCampingId = ref(null)

async function loadOverview() {
  overviewLoading.value = true
  try {
    const result = []
    for (const camping of myCampings.value) {
      const [spots, tags] = await Promise.all([
        getCampingSpotList(camping.id).catch(() => []),
        getCampingTagList(camping.id).catch(() => []),
      ])
      result.push({
        camping,
        spots: Array.isArray(spots) ? spots : [],
        tags: Array.isArray(tags) ? tags : [],
      })
    }
    overviewData.value = result
  } finally {
    overviewLoading.value = false
  }
}

function toggleOverviewCamping(id) {
  expandedCampingId.value = expandedCampingId.value === id ? null : id
}

// Kemping hely hozzáadása
const selectedSpotCampingId = ref(null)
const newSpotForm = ref({
  name: '',
  type: '',
  capacity: '',
  price_per_night: '',
  description: '',
})
const spotFormError = ref(null)
const spotFormSuccess = ref(null)

const spotTypes = [
  'Sátorhely',
  'Lakókocsi',
  'Karaván',
  'Faház',
  'Glamping',
  'Egyéb',
]

async function handleAddSpot() {
  spotFormError.value = null
  spotFormSuccess.value = null

  if (!selectedSpotCampingId.value) {
    spotFormError.value = 'Kérlek válassz kempinget!'
    return
  }
  if (!newSpotForm.value.name || !newSpotForm.value.type || !newSpotForm.value.capacity || !newSpotForm.value.price_per_night) {
    spotFormError.value = 'Kérlek töltsd ki az összes kötelező mezőt!'
    return
  }

  try {
    const payload = {
      name: newSpotForm.value.name,
      type: newSpotForm.value.type,
      capacity: parseInt(newSpotForm.value.capacity),
      price_per_night: parseFloat(newSpotForm.value.price_per_night),
    }
    if (newSpotForm.value.description) payload.description = newSpotForm.value.description

    await createCampingSpot(selectedSpotCampingId.value, payload)
    spotFormSuccess.value = 'Kemping hely sikeresen hozzáadva!'
    newSpotForm.value = { name: '', type: '', capacity: '', price_per_night: '', description: '' }
  } catch (err) {
    spotFormError.value = err.response?.data?.message || campingError.value || 'Hiba történt a kemping hely létrehozásakor.'
  }
}

onMounted(async () => {  await fetchMyCampings()
  // Ha van kemping, automatikusan kiválasztjuk az elsőt
  if (myCampings.value.length > 0) {
    selectedCampingId.value = myCampings.value[0].id
  }
  loadData()
})

</script>

<template>
  <div class="container">
    <h1>Kemping Tulajdonos</h1>
    <div class="subtitle">Kezelje a foglalásokat és monitorizálja a kemping működését</div>

    <div v-if="!isAuthenticated" class="auth-error">
      <h2>⚠️ Nincs bejelentkezve</h2>
      <p>A dashboard eléréséhez kérjük, lépjen be az alkalmazásba.</p>
      <button type="button" @click="openLoginModal" class="btn-link">Bejelentkezés</button>
    </div>

    <template v-else>
      <!-- Tabs -->
      <div class="tabs">
        <div class="tab" :class="{ active: activeTab === 'dashboard' }" @click="activeTab = 'dashboard'">Dashboard</div>
        <div class="tab" :class="{ active: activeTab === 'attekintes' }" @click="activeTab = 'attekintes'; loadOverview()">Áttekintés</div>
        <div class="tab" :class="{ active: activeTab === 'foglalasok' }" @click="activeTab = 'foglalasok'">Foglalások</div>
        <div class="tab" :class="{ active: activeTab === 'kapuk' }" @click="activeTab = 'kapuk'">Kapuk</div>
        <div class="tab" :class="{ active: activeTab === 'terkep' }" @click="activeTab = 'terkep'">Térkép</div>
        <div class="tab" :class="{ active: activeTab === 'bevetelek' }" @click="activeTab = 'bevetelek'">Bevételek</div>
        <div class="tab" :class="{ active: activeTab === 'ujkemping' }" @click="activeTab = 'ujkemping'">+ Új kemping</div>
        <div class="tab" :class="{ active: activeTab === 'ujhely' }" @click="activeTab = 'ujhely'">+ Kemping hely</div>
      </div>

      <!-- ÁTTEKINTÉS -->
    <div v-if="activeTab === 'attekintes'">
      <div class="gates-header">
        <div>
          <h2 class="gates-title">Kempingek áttekintése</h2>
          <p class="gates-subtitle">Minden kempinged hellyel, tagekkel és adatokkal</p>
        </div>
        <button class="btn-add-gate" @click="loadOverview" :disabled="overviewLoading">
          {{ overviewLoading ? '⏳ Betöltés...' : '🔄 Frissítés' }}
        </button>
      </div>

      <!-- Betöltés -->
      <div v-if="overviewLoading" class="gates-empty">
        <p>Betöltés...</p>
      </div>

      <!-- Nincs kemping -->
      <div v-else-if="myCampings.length === 0" class="spot-no-camping">
        <div class="spot-no-camping-icon">🏕️</div>
        <h3>Még nincs kempinged</h3>
        <p>Hozz létre egy kempinget az áttekintés megtekintéséhez.</p>
        <button class="btn-submit-camping" style="max-width: 260px;" @click="activeTab = 'ujkemping'">+ Új kemping létrehozása</button>
      </div>

      <!-- Kemping kártyák -->
      <div v-else class="overview-list">
        <div v-for="item in overviewData" :key="item.camping.id" class="overview-card">

          <!-- Kemping fejléc -->
          <div class="overview-card-header" @click="toggleOverviewCamping(item.camping.id)">
            <div class="overview-card-title-row">
              <span class="overview-camping-icon">🏕️</span>
              <div>
                <div class="overview-camping-name">{{ item.camping.camping_name }}</div>
                <div class="overview-camping-meta">
                  {{ item.camping.city }}, {{ item.camping.zip_code }} · {{ item.camping.street_address }}
                </div>
              </div>
            </div>
            <div class="overview-card-stats">
              <span class="overview-stat-badge">{{ item.spots.length }} hely</span>
              <span class="overview-stat-badge tag-color">{{ item.tags.length }} tag</span>
              <span class="overview-chevron" :class="{ open: expandedCampingId === item.camping.id }">▼</span>
            </div>
          </div>

          <!-- Kibontott tartalom -->
          <div v-if="expandedCampingId === item.camping.id" class="overview-card-body">

            <!-- Tagek -->
            <div class="overview-section">
              <h5 class="overview-section-title">🏷️ Tagek</h5>
              <div v-if="item.tags.length > 0" class="tag-list">
                <span v-for="t in item.tags" :key="t.id ?? t.tag ?? t" class="tag-badge">
                  {{ t.tag ?? t }}
                </span>
              </div>
              <p v-else class="tag-empty">Nincsenek tagek hozzáadva.</p>
            </div>

            <!-- Kemping helyek -->
            <div class="overview-section">
              <h5 class="overview-section-title">📍 Kemping helyek</h5>
              <div v-if="item.spots.length > 0" class="overview-spots-grid">
                <div v-for="spot in item.spots" :key="spot.id ?? spot.spot_id" class="overview-spot-card">
                  <div class="overview-spot-header">
                    <span class="overview-spot-name">{{ spot.name }}</span>
                    <span class="overview-spot-type">{{ spot.type }}</span>
                  </div>
                  <div class="overview-spot-details">
                    <div class="overview-spot-detail">
                      <span class="overview-spot-detail-label">Kapacitás</span>
                      <span class="overview-spot-detail-value">{{ spot.capacity }} fő</span>
                    </div>
                    <div class="overview-spot-detail">
                      <span class="overview-spot-detail-label">Ár/éj</span>
                      <span class="overview-spot-detail-value">{{ Number(spot.price_per_night).toLocaleString('hu-HU') }} Ft</span>
                    </div>
                  </div>
                  <div v-if="spot.description" class="overview-spot-desc">{{ spot.description }}</div>
                </div>
              </div>
              <div v-else class="overview-empty-spots">
                <p>Nincsenek kemping helyek hozzáadva.</p>
                <button class="btn-add-gate" style="font-size:13px; padding: 8px 14px;" @click="activeTab = 'ujhely'; selectedSpotCampingId = item.camping.id">+ Hely hozzáadása</button>
              </div>
            </div>

            <!-- Céges adatok -->
            <div v-if="item.camping.company_name" class="overview-section">
              <h5 class="overview-section-title">🏢 Céges adatok</h5>
              <div class="overview-company-grid">
                <div class="overview-company-row"><span>Cég neve</span><strong>{{ item.camping.company_name }}</strong></div>
                <div class="overview-company-row" v-if="item.camping.tax_id"><span>Adószám</span><strong>{{ item.camping.tax_id }}</strong></div>
                <div class="overview-company-row" v-if="item.camping.billing_address"><span>Számlázási cím</span><strong>{{ item.camping.billing_address }}</strong></div>
              </div>
            </div>

          </div>
        </div>

        <!-- Ha még nem töltöttük be -->
        <div v-if="overviewData.length === 0 && !overviewLoading" class="gates-empty">
          <p>Kattints a 🔄 Frissítés gombra az adatok betöltéséhez.</p>
        </div>
      </div>
    </div>

      <!-- DASHBOARD -->
    <div v-if="activeTab === 'dashboard'">
      <div class="stats">
        <div class="card">
          <small>Összes foglalás</small>
          <h2>{{ dashboard?.totalBookings || 0 }}</h2>
          <div class="trend">{{ formatChange(dashboard?.totalBookings, dashboard?.previousTotalBookings) || '—' }}</div>
        </div>

        <div class="card">
          <small>Aktív vendégek</small>
          <h2>{{ dashboard?.activeGuests || 0 }}</h2>
          <div class="trend">{{ formatChange(dashboard?.activeGuests, dashboard?.previousActiveGuests) || '—' }}</div>
        </div>

        <div class="card">
          <small>Foglalt helyek</small>
          <h2>{{ dashboard?.bookedSpots || 0 }} / {{ dashboard?.totalSpots || 0 }}</h2>
          <div class="trend">{{ dashboard?.occupancyPercentage || 0 }}% foglaltság {{ formatChange(dashboard?.occupancyPercentage, dashboard?.previousOccupancyPercentage) }}</div>
        </div>

        <div class="card">
          <small>Havi bevétel</small>
          <h2>{{ (dashboard?.monthlyRevenue || 0).toLocaleString('hu-HU') }} Ft</h2>
          <div class="trend">{{ formatChange(dashboard?.monthlyRevenue, dashboard?.previousMonthlyRevenue) || '—' }}</div>
        </div>
      </div>

      <div class="section">
        <h3>Legutóbbi foglalások</h3>
        <p>Az elmúlt hét foglalásai</p>

        <div v-for="booking in dashboard?.recentBookings" :key="booking.id" class="booking">
          <div>
            <div class="name">{{ booking.guestFirstName }} {{ booking.guestLastName }}</div>
            <div class="place">Hely: {{ booking.spot }}</div>
          </div>
          <div class="right">
            <div class="price">{{ (booking.price || 0).toLocaleString('hu-HU') }} Ft</div>
            <span :class="['badge', booking.status === 'pending' ? 'pending' : booking.status === 'confirmed' ? 'confirmed' : booking.status === 'checked_in' ? 'checked_in' : booking.status === 'finished' ? 'finished' : booking.status === 'cancelled' ? 'cancelled' : '']">
              {{ booking.status === 'pending' ? 'Függőben van' : booking.status === 'confirmed' ? 'Megerősített' : booking.status === 'checked_in' ? 'Bejelentkezett' : booking.status === 'finished' ? 'Befejezett' : booking.status === 'cancelled' ? 'Lemondott' : ''}}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- FOGLALÁSOK -->
    <div v-if="activeTab === 'foglalasok'">
      <div class="card">
        <h2>Összes foglalás</h2>
        <p>Kezelje a jelenlegi és múltbeli foglalásokat</p>
        <table>
          <thead>
            <tr>
              <th>Azonosító</th>
              <th>Vendég keresztneve</th>
              <th>Vendég vezetékneve</th>
              <th>Hely</th>
              <th>Érkezés</th>
              <th>Távozás</th>
              <th>Vendégek</th>
              <th>Státusz</th>
              <th>Ár</th>
              <th>Műveletek</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in bookings" :key="booking.id">
              <td><strong>{{ booking.id }}</strong></td>
              <td>{{ getBookingFirstName(booking) }}</td>
              <td>{{ getBookingLastName(booking) }}</td>
              <td>{{ getBookingSpot(booking) }}</td>
              <td>{{ formatBookingDate(booking.checkIn || booking.arrival_date || booking.arrivalDate) }}</td>
              <td>{{ formatBookingDate(booking.checkOut || booking.departure_date || booking.departureDate) }}</td>
              <td>{{ booking.guests }}</td>
              <td><span :class="['badge', booking.status === 'pending' ? 'pending' : booking.status === 'confirmed' ? 'confirmed' : booking.status === 'checked_in' ? 'checked_in' : booking.status === 'finished' ? 'finished' : booking.status === 'cancelled' ? 'cancelled' : '']">
                {{ booking.status === 'pending' ? 'Függőben van' : booking.status === 'confirmed' ? 'Megerősített' : booking.status === 'checked_in' ? 'Bejelentkezett' : booking.status === 'finished' ? 'Befejezett' : booking.status === 'cancelled' ? 'Lemondott' : ''}}
              </span></td>
              <td><strong>{{ getBookingPrice(booking).toLocaleString('hu-HU') }} Ft</strong></td>
              <td><button class="btn">👁 Részletek</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KAPUK -->
    <div v-if="activeTab === 'kapuk'">
      <div class="gates-header">
        <div>
          <h2 class="gates-title">Kapuk kezelése</h2>
          <p class="gates-subtitle">Kapuk hozzáadása, nyitvatartás beállítása</p>
        </div>
        <div class="gates-header-right">
          <select class="form-select" v-model="selectedCampingId">
            <option :value="null" disabled>Válassz kempinget...</option>
            <option v-for="c in myCampings" :key="c.id" :value="c.id">{{ c.camping_name }}</option>
          </select>
          <button class="btn-add-gate" @click="openGateModal" :disabled="myCampings.length === 0">+ Új kapu</button>
        </div>
      </div>

      <!-- Betöltés -->
      <div v-if="gatesLoading" class="gates-empty">
        <p>Betöltés...</p>
      </div>

      <!-- Hiba -->
      <div v-else-if="gatesError" class="gates-empty">
        <p style="color: #dc2626;">Hiba: {{ gatesError }}</p>
      </div>

      <!-- Nincs kemping -->
      <div v-else-if="myCampings.length === 0" class="gates-empty">
        <p>Még nincs kempinged. Hozz létre egyet először!</p>
      </div>

      <!-- Nincs kemping kiválasztva -->
      <div v-else-if="!selectedCampingId" class="gates-empty">
        <p>Válassz kempinget a legördülő menüből a kapuk megjelenítéséhez.</p>
      </div>

      <!-- Kapuk listája -->
      <template v-else>
        <div class="gates-grid">
          <div class="gate-card" v-for="gate in gates" :key="gate.id">
            <div class="gate-card-header">
              <div class="gate-name-row">
                <svg class="gate-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="9" /><path d="M3 12v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6" /></svg>
                <span class="gate-name">{{ gate.name || 'Névtelen kapu' }} #{{ gate.id }}</span>
              </div>
              <div class="gate-actions">
                <button class="gate-action-btn edit" title="Átnevezés" @click="openRenameModal(gate)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="gate-action-btn delete" title="Törlés" @click="handleDeleteGate(gate.id)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </div>
            </div>

            <div class="gate-time">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span v-if="gate.opening_time && gate.closing_time">{{ gate.opening_time }} – {{ gate.closing_time }}</span>
              <span v-else>Nincs nyitvatartás megadva</span>
              <span>| Kemping ID: {{ selectedCampingId }}</span>
            </div>

            <div class="gate-divider"></div>

            <!-- Token státusz -->
            <div class="gate-token-row">
              <div v-if="gate.has_token" class="gate-token-info">
                <span class="token-badge active">Token aktív</span>
                <span class="token-prefix">{{ gate.token_prefix }}</span>
                <button class="btn-revoke" @click="handleRevokeToken(gate.id)">Visszavonás</button>
              </div>
              <div v-else class="gate-token-info">
                <span class="token-badge inactive">Nincs token</span>
                <button class="btn-generate" @click="handleGenerateToken(gate.id)">Token generálása</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Üres állapot -->
        <div v-if="gates.length === 0" class="gates-empty">
          <p>Ehhez a kempinghez még nincs kapu hozzáadva.</p>
          <button class="btn-add-gate" @click="openGateModal">+ Új kapu hozzáadása</button>
        </div>
      </template>

      <!-- Modal: Új kapu -->
      <div class="modal-overlay" v-if="showGateModal" @click.self="showGateModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Új kapu hozzáadása</h3>
            <button class="modal-close" @click="showGateModal = false">&times;</button>
          </div>

          <div class="form-group">
            <label class="form-label">Kemping *</label>
            <select class="form-input" v-model="newGate.campingId">
              <option :value="null" disabled>Válassz kempinget...</option>
              <option v-for="c in myCampings" :key="c.id" :value="c.id">{{ c.camping_name }}</option>
            </select>
          </div>



          <div class="form-row">
            <div class="form-group half">
              <label class="form-label">Nyitás</label>
              <input type="time" class="form-input" v-model="newGate.openingTime" />
            </div>
            <div class="form-group half">
              <label class="form-label">Zárás</label>
              <input type="time" class="form-input" v-model="newGate.closingTime" />
            </div>
          </div>
          <div class="form-group">
              <label class="form-label">Kapu neve</label>
              <input type="text" class="form-input" v-model="newGate.name" placeholder="Főbejárat"/>
          </div>
          <button class="btn-submit" @click="addGate" :disabled="!newGate.campingId">Hozzáadás</button>
        </div>
      </div>

      <!-- Modal: Kapu átnevezése -->
      <div class="modal-overlay" v-if="showRenameModal" @click.self="showRenameModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Kapu átnevezése</h3>
            <button class="modal-close" @click="showRenameModal = false">&times;</button>
          </div>
          <div class="form-group">
            <label class="form-label">Új név</label>
            <input
              type="text"
              class="form-input"
              v-model="renameGate.name"
              placeholder="pl. Főbejárat"
              @keyup.enter="handleRenameGate"
            />
          </div>
          <button class="btn-submit" @click="handleRenameGate">Mentés</button>
        </div>
      </div>

      <!-- Modal: Generált token -->
      <div class="modal-overlay" v-if="showTokenModal" @click.self="showTokenModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Scanner token generálva</h3>
            <button class="modal-close" @click="showTokenModal = false">&times;</button>
          </div>
          <p class="token-warning">Mentsd el ezt a tokent! Többé nem jelenik meg teljes egészében.</p>
          <div class="token-display">
            <code>{{ generatedToken }}</code>
            <button class="btn-copy" @click="copyToken">Másolás</button>
          </div>
          <button class="btn-submit" @click="showTokenModal = false" style="margin-top: 16px;">Rendben</button>
        </div>
      </div>
    </div>

    <!-- TÉRKÉP -->
    <div v-if="activeTab === 'terkep'">
      <div class="card">
        <h2>Térkép</h2>
        <p>Kemping térképes nézete.</p>
      </div>
    </div>

    <!-- BEVÉTELEK -->
    <div v-if="activeTab === 'bevetelek'">
      <div class="stats">
        <div class="card">
          <small>Havi bevétel</small>
          <h2>{{ (dashboard?.monthlyRevenue || 0).toLocaleString('hu-HU') }} Ft</h2>
          <div class="trend">{{ formatChange(dashboard?.monthlyRevenue, dashboard?.previousMonthlyRevenue) || '—' }} az előző hónaphoz képest</div>
        </div>
        <div class="card">
          <small>Átlagos foglalási érték</small>
          <h2>{{ (averageBookingValue || 0).toLocaleString('hu-HU') }} Ft</h2>
          <div class="trend">{{ formatChange(averageBookingValue, previousAverageBookingValue) || '—' }} az előző hónaphoz képest</div>
        </div>
      </div>

      <div class="section">
        <h3>Bevétel típusok szerint</h3>
        <p>Helyek típusainak bevétel megoszlása</p>

        <template v-if="revenueByType.length > 0">
          <div v-for="type in revenueByType" :key="type.name" class="booking">
            <div>
              <div class="name">{{ type.name }}</div>
              <div class="place">{{ type.count }} foglalás</div>
            </div>
            <div class="right">
              <div class="price">{{ type.revenue.toLocaleString('hu-HU') }} Ft</div>
              <p>{{ type.percentage }}%</p>
            </div>
          </div>
        </template>
        <div v-else class="booking">
          <p>Nincsenek adatok</p>
        </div>
      </div>
    </div>

    <!-- ÚJ KEMPING -->
    <div v-if="activeTab === 'ujkemping'">
      <div class="new-camping-header">
        <div>
          <h2 class="gates-title">Új kemping létrehozása</h2>
          <p class="gates-subtitle">Töltsd ki az adatokat az új kemping regisztrálásához</p>
        </div>
      </div>

      <div v-if="showInfoNotice" class="info-notice">
        <span class="info-notice-icon">💡</span>
        <div class="info-notice-text">
          <strong>Egyedi igénye van?</strong>
          Ha különleges kategóriát szeretne hozzáadni, vagy egyedi igénye van a kempingjével kapcsolatban,
          kérjük írjon nekünk e-mailt:
          <a href="mailto:info@campsite.hu">info@campsite.hu</a>
        </div>
        <button class="info-notice-close" @click="showInfoNotice = false" title="Bezárás">✕</button>
      </div>

      <div class="new-camping-card-full">
        <!-- Hiba üzenet -->
        <div v-if="campingFormError" class="form-alert error">
          ⚠️ {{ campingFormError }}
        </div>

        <!-- Alap adatok + Tagek egy sorban -->
        <div class="new-camping-top-row">
          <div class="form-section new-camping-section-grow">
            <h4 class="form-section-title">Alap adatok</h4>
            <div class="form-group">
              <label class="form-label">
                Kemping neve <span class="required">*</span>
                <span class="char-hint">(max. 100 karakter)</span>
              </label>
              <input type="text" class="form-input" v-model="newCampingForm.camping_name" placeholder="pl. Napfény Kemping" maxlength="100" />
            </div>
            <div class="form-group">
              <label class="form-label">
                Leírás <span class="required">*</span>
                <span class="char-hint">(max. 1000 karakter)</span>
              </label>
              <textarea class="form-textarea" v-model="newCampingForm.description" rows="4" placeholder="Írj egy rövid leírást a kempingről..." maxlength="1000"></textarea>
            </div>
          </div>

          <div class="form-section new-camping-section-tags">
            <h4 class="form-section-title">Tagek (opcionális)</h4>
            <p class="form-section-desc">Pipáld ki a kempingre jellemző tulajdonságokat. A kemping létrehozásával együtt mentődnek el.</p>
            <div class="tag-checkbox-grid">
              <label
                v-for="tag in availableTags"
                :key="tag"
                class="tag-checkbox-item"
                :class="{ selected: pendingTags.includes(tag) }"
              >
                <input type="checkbox" :value="tag" :checked="pendingTags.includes(tag)" @change="toggleTag(tag)" />
                <span>{{ tag }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Helyszín + Céges adatok egy sorban -->
        <div class="new-camping-bottom-row">
          <div class="form-section new-camping-section-grow">
            <h4 class="form-section-title">Helyszín</h4>
            <div class="form-row">
              <div class="form-group half">
                <label class="form-label">Város <span class="required">*</span></label>
                <input type="text" class="form-input" v-model="newCampingForm.city" placeholder="pl. Budapest" />
              </div>
              <div class="form-group half">
                <label class="form-label">Irányítószám <span class="required">*</span></label>
                <input type="text" class="form-input" v-model="newCampingForm.zip_code" placeholder="pl. 1011" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Utca, házszám <span class="required">*</span></label>
              <input type="text" class="form-input" v-model="newCampingForm.street_address" placeholder="pl. Fő utca, 12." />
            </div>
          </div>

          <div class="form-section new-camping-section-grow">
            <h4 class="form-section-title">Céges adatok</h4>
            <div class="form-group">
              <label class="form-label">Cég neve <span class="required">*</span></label>
              <input type="text" class="form-input" v-model="newCampingForm.company_name" placeholder="pl. Napfény Kemping Kft." />
            </div>
            <div class="form-group">
              <label class="form-label">Adószám <span class="required">*</span></label>
              <input type="text" class="form-input" v-model="newCampingForm.tax_id" placeholder="pl. 12345678-1-41" />
            </div>
            <div class="form-group">
              <label class="form-label">Számlázási cím <span class="required">*</span></label>
              <input type="text" class="form-input" v-model="newCampingForm.billing_address" placeholder="pl. Budapest, Fő u. 12." />
            </div>
          </div>
        </div>

        <div v-if="campingFormSuccess" class="form-alert success">
          ✅ {{ campingFormSuccess }}
        </div>

        <button class="btn-submit-camping" @click="handleAddCamping" :disabled="campingLoading">
          <span v-if="campingLoading">⏳ Létrehozás folyamatban...</span>
          <span v-else>🏕️ Kemping létrehozása</span>
        </button>
      </div>
    </div>
    <!-- KEMPING HELY HOZZÁADÁSA -->
    <div v-if="activeTab === 'ujhely'">
      <div class="new-camping-header">
        <div>
          <h2 class="gates-title">Kemping hely hozzáadása</h2>
          <p class="gates-subtitle">Adj hozzá új férőhelyet egy meglévő kempinghez</p>
        </div>
      </div>

      <!-- Nincs kemping -->
      <div v-if="myCampings.length === 0" class="spot-no-camping">
        <div class="spot-no-camping-icon">🏕️</div>
        <h3>Még nincs kempinged</h3>
        <p>Kemping hely hozzáadásához először hozz létre egy kempinget.</p>
        <button class="btn-submit-camping" style="max-width: 260px;" @click="activeTab = 'ujkemping'">
          + Új kemping létrehozása
        </button>
      </div>

      <!-- Van kemping -->
      <template v-else>
        <!-- Hely form -->
        <div class="new-camping-card-full">
          <div v-if="spotFormError" class="form-alert error">⚠️ {{ spotFormError }}</div>

          <div class="form-group" style="max-width: 420px; margin-bottom: 24px;">
            <label class="form-label">Kemping kiválasztása <span class="required">*</span></label>
            <select
              class="form-select"
              style="width: 100%;"
              v-model="selectedSpotCampingId"
              @change="spotFormError = null; spotFormSuccess = null"
            >
              <option :value="null" disabled>Válassz kempinget...</option>
              <option v-for="c in myCampings" :key="c.id" :value="c.id">
                {{ c.camping_name }} – {{ c.city }}
              </option>
            </select>
          </div>

          <div class="new-camping-top-row">
            <!-- Bal: alap adatok -->
            <div class="form-section new-camping-section-grow">
              <h4 class="form-section-title">Hely adatai</h4>
              <div class="form-group">
                <label class="form-label">
                  Hely neve <span class="required">*</span>
                  <span class="char-hint">(max. 100 karakter)</span>
                </label>
                <input type="text" class="form-input" v-model="newSpotForm.name" placeholder="pl. A1, Napfény sarok" maxlength="100" />
              </div>
              <div class="form-group">
                <label class="form-label">Típus <span class="required">*</span></label>
                <div class="spot-type-grid">
                  <label
                    v-for="t in spotTypes"
                    :key="t"
                    class="tag-checkbox-item"
                    :class="{ selected: newSpotForm.type === t }"
                    @click="newSpotForm.type = t"
                  >
                    <input type="radio" :value="t" v-model="newSpotForm.type" style="accent-color: #3f6212; width:15px; height:15px; flex-shrink:0; cursor:pointer;" />
                    <span>{{ t }}</span>
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Leírás <span class="char-hint">(opcionális)</span></label>
                <textarea class="form-textarea" v-model="newSpotForm.description" rows="3" placeholder="Pl. árnyékos hely, közel a mosdóhoz..."></textarea>
              </div>
            </div>

            <!-- Jobb: kapacitás és ár -->
            <div class="form-section new-camping-section-grow">
              <h4 class="form-section-title">Kapacitás és ár</h4>
              <div class="form-group">
                <label class="form-label">Kapacitás (fő) <span class="required">*</span></label>
                <input type="number" class="form-input" v-model="newSpotForm.capacity" min="1" max="50" placeholder="pl. 4" />
              </div>
              <div class="form-group">
                <label class="form-label">Ár / éjszaka (Ft) <span class="required">*</span></label>
                <input type="number" class="form-input" v-model="newSpotForm.price_per_night" min="0" placeholder="pl. 5000" />
              </div>

              <!-- Összefoglaló -->
              <div v-if="newSpotForm.name || newSpotForm.type || newSpotForm.capacity || newSpotForm.price_per_night" class="spot-summary">
                <h5 class="spot-summary-title">Összefoglaló</h5>
                <div class="spot-summary-row"><span>Kemping:</span><strong>{{ myCampings.find(c => c.id === selectedSpotCampingId)?.camping_name || '—' }}</strong></div>
                <div class="spot-summary-row"><span>Hely neve:</span><strong>{{ newSpotForm.name || '—' }}</strong></div>
                <div class="spot-summary-row"><span>Típus:</span><strong>{{ newSpotForm.type || '—' }}</strong></div>
                <div class="spot-summary-row"><span>Kapacitás:</span><strong>{{ newSpotForm.capacity ? newSpotForm.capacity + ' fő' : '—' }}</strong></div>
                <div class="spot-summary-row"><span>Ár:</span><strong>{{ newSpotForm.price_per_night ? Number(newSpotForm.price_per_night).toLocaleString('hu-HU') + ' Ft/éj' : '—' }}</strong></div>
              </div>
            </div>
          </div>

          <div v-if="spotFormSuccess" class="form-alert success">✅ {{ spotFormSuccess }}</div>

          <button class="btn-submit-camping" @click="handleAddSpot" :disabled="campingLoading">
            <span v-if="campingLoading">⏳ Mentés folyamatban...</span>
            <span v-else>➕ Kemping hely hozzáadása</span>
          </button>
        </div>
      </template>
    </div>
    </template>
    <AuthModal
      :isOpen="authModalOpen"
      :initialMode="authModalMode"
      @close="closeAuthModal"
      @success="handleAuthSuccess"
    />
  </div>
</template>


<style scoped>
  * {
    box-sizing: border-box;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }

  body {
    margin: 0;
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
    background: #f6f7fb;
    padding: 30px;
    color: #1f2937;
  }

  .container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 20px 60px;
  }

  h1 {
    margin: 0;
    font-size: 28px;
    color: #3f6212;
  }

  .subtitle {
    color: #6b7280;
    margin-bottom: 20px;
    margin-top: 4px;
  }

  .tabs {
    margin-top: 20px;
    display: inline-flex;
    background: #eef0f4;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 25px;
    max-width: 100%;
  }

  @media (max-width: 768px) {
    .tabs {
      display: flex;
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none; /* Firefox */
    }

    .tabs::-webkit-scrollbar {
      display: none; /* Chrome, Safari */
    }
  }

  .tab {
    padding: 8px 14px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 8px;
    color: #374151;
  }

  .tab.active {
    background: white;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
  }

  .stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-top: 28px;
  }

  @media (max-width: 768px) {
    .stats {
      grid-template-columns: 1fr;
    }
  }

  .card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, .06);
  }

  .card small { color: #64748b; }

  .card h2 {
    margin: 6px 0 5px;
    font-size: 26px;
  }

  .card p {
    margin-bottom: 20px;
    color: #6b7280;
    font-size: 14px;
  }

  .trend {
    font-size: 13px;
    color: #16a34a;
  }

  .section {
    margin-top: 28px;
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 22px;
  }

  .section h3 { margin: 0;}

  .section p {
    margin: 4px 0 18px;
    color: #64748b;
  }

  .booking { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #e6eef8; }
  .booking > div:first-child { flex: 1; }
  .booking .right { text-align: right; min-width: 140px; display: flex; flex-direction: column; align-items: flex-end; }

  @media (max-width: 640px) {
    .booking {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
    
    .container {
      padding: 16px 12px 60px;
    }

    h1 {
      font-size: 22px;
    }

    .subtitle {
      font-size: 14px;
    }

    .gates-header {
      flex-direction: column;
      gap: 12px;
      align-items: stretch;
    }

    .gates-title {
      font-size: 20px;
    }

    .gates-header-right {
      flex-direction: column;
      gap: 8px;
    }

    .form-select {
      width: 100%;
      min-width: unset;
    }

    .btn-add-gate {
      width: 100%;
    }

    .modal-content {
      margin: 12px;
      max-width: calc(100% - 24px);
      padding: 20px;
    }

    .modal-header h3 {
      font-size: 16px;
    }

    .form-row {
      flex-direction: column;
    }

    .form-group.half {
      width: 100%;
    }

    /* Táblázat görgethetővé tétele */
    .card {
      overflow-x: auto;
      padding: 16px;
    }

    table {
      min-width: 700px;
      font-size: 13px;
    }

    th, td {
      padding: 10px 8px;
    }

    .gate-card {
      padding: 16px;
    }

    .section {
      padding: 16px;
    }
  }

  .booking:last-child { margin-bottom: 0; }

  .name { font-weight: 600; }

  .place { color: #64748b; font-size: 14px; }

  .right { text-align: right; }

  .price { font-weight: 600; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }

  th {
    text-align: left;
    color: #6b7280;
    font-weight: 500;
    padding-bottom: 10px;
  }

  td {
    padding: 14px 0;
    border-top: 1px solid #dbeafe;
  }

  .pending {
    background: #f3f4f6;
    color: #82a2d4;
  }

  .confirmed {
    background: #dbeafe;
    color: #1e40af;
  }

  .checked_in {
    background: #dbeafe;
    color: #30b965;
  }

  .finished {
    background: #f3f4f6;
    color: #374151;
  }

  .cancelled {
    background: #f3f4f6;
    color: #ff0000;
  }


  .btn {
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
  }

  .btn:hover {
    background: #f9fafb;
  }

  /* Kapuk  */
  .gates-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
  }

  .gates-title {
    margin: 0;
    font-size: 24px;
    color: #3f6212;
  }

  .gates-subtitle {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 14px;
  }

  .btn-add-gate {
    padding: 10px 20px;
    background: #3f6212;
    color: white;
    border: 2px solid #3f6212;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
  }

  .btn-add-gate:hover {
    background: #4d7c0f;
    border-color: #4d7c0f;
  }

  .gates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
  }

  @media (max-width: 768px) {
    .gates-grid {
      grid-template-columns: 1fr;
    }
  }

  .gate-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
  }

  .gate-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .gate-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .gate-icon {
    width: 20px;
    height: 20px;
    color: #6b7280;
  }

  .gate-name {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
  }

  .gate-actions {
    display: flex;
    gap: 6px;
  }

  .gate-action-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 4px;
    border-radius: 6px;
    transition: color .15s, background .15s;
  }

  .gate-action-btn:hover {
    color: #374151;
    background: #f3f4f6;
  }

  .gate-action-btn.delete:hover {
    color: #dc2626;
    background: #fef2f2;
  }

  .gate-action-btn.edit:hover {
    color: #3f6212;
    background: #f0fdf4;
  }

  .gate-time {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    font-size: 13px;
    color: #6b7280;
  }

  .gate-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 14px 0;
  }

  .gate-status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .gate-status-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
  }

  /* Toggle switch */
  .toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #d1d5db;
    border-radius: 999px;
    transition: background .2s;
  }

  .toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
  }

  .toggle input:checked + .toggle-slider {
    background: #3f6212;
  }

  .toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
  }

  .toggle-row {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
  }

  .gates-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal-content {
    background: white;
    border-radius: 16px;
    padding: 28px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .15);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #1f2937;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
    line-height: 1;
    padding: 0;
  }

  .modal-close:hover {
    color: #374151;
  }

  .form-group {
    margin-bottom: 18px;
  }

  .form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
  }

  .form-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    color: #1f2937;
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
  }

  .form-input:focus {
    border-color: #3f6212;
  }

  .form-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    color: #1f2937;
    outline: none;
    resize: vertical;
    font-family: inherit;
    transition: border-color .15s;
    box-sizing: border-box;
  }

  .form-textarea:focus {
    border-color: #3f6212;
  }

  .form-row {
    display: flex;
    gap: 12px;
  }

  .form-group.half {
    flex: 1;
  }

  .btn-submit {
    width: 100%;
    padding: 12px;
    background: #3f6212;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 6px;
    transition: background .15s;
  }

  .btn-submit:hover {
    background: #4d7c0f;
  }

  .btn-submit:disabled {
    background: #9ca3af;
    cursor: not-allowed;
  }

  /*Kemping szűrő & header */
  .gates-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .form-select {
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    color: #1f2937;
    outline: none;
    background: white;
    cursor: pointer;
    min-width: 200px;
    transition: border-color .15s;
  }

  .form-select:focus {
    border-color: #3f6212;
  }

  .btn-add-gate:disabled {
    background: #9ca3af;
    border-color: #9ca3af;
    cursor: not-allowed;
  }

  /*Token */
  .gate-token-row {
    margin-top: 4px;
  }

  .gate-token-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .token-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
  }

  .token-badge.active {
    background: #dcfce7;
    color: #166534;
  }

  .token-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
  }

  .token-prefix {
    font-family: monospace;
    font-size: 12px;
    color: #6b7280;
  }

  .btn-generate, .btn-revoke {
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid;
    transition: background .15s;
  }

  .btn-generate {
    background: #3f6212;
    color: white;
    border-color: #3f6212;
  }

  .btn-generate:hover {
    background: #4d7c0f;
  }

  .btn-revoke {
    background: white;
    color: #dc2626;
    border-color: #fecaca;
  }

  .btn-revoke:hover {
    background: #fef2f2;
  }

  /* Token modal */
  .token-warning {
    color: #b45309;
    font-size: 14px;
    background: #fffbeb;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #fde68a;
    margin-bottom: 16px;
  }

  .token-display {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f3f4f6;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 16px;
  }

  .token-display code {
    flex: 1;
    font-family: monospace;
    font-size: 16px;
    color: #1f2937;
    word-break: break-all;
  }

  .btn-copy {
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
  }

  .btn-copy:hover {
    background: #f9fafb;
  }

  .auth-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-top: 20px;
  }

  .auth-error h2 {
    color: #dc2626;
    margin: 0 0 10px 0;
  }

  .auth-error p {
    color: #991b1b;
    margin: 0 0 20px 0;
  }

  .btn-link {
    display: inline-block;
    padding: 10px 20px;
    background: #3f6212;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s;
    border: none;
    cursor: pointer;
  }

  .btn-link:hover {
    background: #2d4609;
  }

  /* Új kemping form */
  .new-camping-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
  }

  .info-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
  }

  .info-notice-icon {
    font-size: 20px;
    flex-shrink: 0;
    line-height: 1.4;
  }

  .info-notice-text {
    font-size: 14px;
    color: #78350f;
    line-height: 1.6;
    flex: 1;
  }

  .info-notice-text strong {
    display: block;
    margin-bottom: 2px;
    color: #92400e;
  }

  .info-notice-text a {
    color: #b45309;
    font-weight: 600;
    text-decoration: underline;
  }

  .info-notice-text a:hover {
    color: #78350f;
  }

  .info-notice-close {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: #b45309;
    padding: 0 0 0 8px;
    line-height: 1;
    flex-shrink: 0;
    opacity: 0.7;
    transition: opacity .15s;
    align-self: flex-start;
  }

  .info-notice-close:hover {
    opacity: 1;
  }

  .new-camping-card-full {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 10px rgba(0,0,0,.06);
  }

  .new-camping-top-row,
  .new-camping-bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    align-items: start;
  }

  @media (max-width: 900px) {
    .new-camping-top-row,
    .new-camping-bottom-row {
      grid-template-columns: 1fr;
      gap: 0;
    }
  }

  .new-camping-section-grow {
    min-width: 0;
  }

  .new-camping-section-tags {
    min-width: 0;
  }

  .form-section {
    margin-bottom: 0;
    padding-bottom: 20px;
    border-bottom: none;
  }

  .new-camping-top-row {
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 20px;
  }

  .form-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 4px 0;
    padding-left: 10px;
    border-left: 3px solid #3f6212;
  }

  .form-section-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 4px 0 14px 0;
  }

  .required {
    color: #dc2626;
  }

  .char-hint {
    font-size: 11px;
    font-weight: 400;
    color: #9ca3af;
    margin-left: 4px;
  }

  /* Tag checkbox grid */
  .tag-checkbox-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 4px;
  }

  .tag-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    transition: border-color .15s, background .15s;
    user-select: none;
  }

  .tag-checkbox-item:hover {
    border-color: #86efac;
    background: #f0fdf4;
  }

  .tag-checkbox-item.selected {
    border-color: #3f6212;
    background: #dcfce7;
    color: #166534;
    font-weight: 600;
  }

  .tag-checkbox-item input[type="checkbox"] {
    accent-color: #3f6212;
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    cursor: pointer;
  }

  .tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
  }

  .tag-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
  }

  .tag-remove {
    background: none;
    border: none;
    cursor: pointer;
    color: #166534;
    font-size: 16px;
    line-height: 1;
    padding: 0;
    display: flex;
    align-items: center;
    opacity: 0.6;
    transition: opacity .15s;
  }

  .tag-remove:hover {
    opacity: 1;
  }

  .tag-empty {
    font-size: 13px;
    color: #9ca3af;
    margin: 0;
  }

  .btn-submit-camping {
    width: 100%;
    padding: 14px;
    background: #3f6212;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    margin-top: 8px;
  }

  .btn-submit-camping:hover {
    background: #4d7c0f;
  }

  .btn-submit-camping:disabled {
    background: #9ca3af;
    cursor: not-allowed;
  }

  .form-alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 16px;
    font-weight: 500;
  }

  .form-alert.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
  }

  .form-alert.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
  }

  .tab.active[data-tab="ujkemping"] {
    background: #f0fdf4;
    color: #3f6212;
  }

  /* Áttekintés */
  .overview-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .overview-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    overflow: hidden;
  }

  .overview-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    cursor: pointer;
    transition: background .15s;
    gap: 12px;
  }

  .overview-card-header:hover {
    background: #f9fafb;
  }

  .overview-card-title-row {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
  }

  .overview-camping-icon {
    font-size: 28px;
    flex-shrink: 0;
  }

  .overview-camping-name {
    font-size: 17px;
    font-weight: 700;
    color: #1f2937;
  }

  .overview-camping-meta {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
  }

  .overview-card-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .overview-stat-badge {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: #f3f4f6;
    color: #374151;
  }

  .overview-stat-badge.tag-color {
    background: #dcfce7;
    color: #166534;
  }

  .overview-chevron {
    font-size: 12px;
    color: #9ca3af;
    transition: transform .2s;
    margin-left: 4px;
  }

  .overview-chevron.open {
    transform: rotate(180deg);
  }

  .overview-card-body {
    border-top: 1px solid #f3f4f6;
    padding: 0 22px 22px;
  }

  .overview-section {
    margin-top: 20px;
  }

  .overview-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 12px 0;
  }

  .overview-spots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
  }

  .overview-spot-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px;
  }

  .overview-spot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    gap: 6px;
  }

  .overview-spot-name {
    font-weight: 700;
    font-size: 14px;
    color: #1f2937;
  }

  .overview-spot-type {
    font-size: 11px;
    font-weight: 600;
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 8px;
    border-radius: 999px;
    white-space: nowrap;
  }

  .overview-spot-details {
    display: flex;
    gap: 12px;
  }

  .overview-spot-detail {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .overview-spot-detail-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .overview-spot-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
  }

  .overview-spot-desc {
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
    border-top: 1px solid #e5e7eb;
    padding-top: 8px;
  }

  .overview-empty-spots {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    color: #6b7280;
    font-size: 14px;
  }

  .overview-empty-spots p {
    margin: 0;
  }

  .overview-company-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: #f8fafc;
    border-radius: 10px;
    padding: 14px 16px;
  }

  .overview-company-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #6b7280;
    padding: 4px 0;
    border-bottom: 1px solid #f3f4f6;
  }

  .overview-company-row:last-child {
    border-bottom: none;
  }

  .overview-company-row strong {
    color: #1f2937;
  }

  /* Kemping hely felvitel */
  .spot-no-camping {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
  }

  .spot-no-camping-icon {
    font-size: 52px;
    margin-bottom: 12px;
  }

  .spot-no-camping h3 {
    font-size: 20px;
    color: #374151;
    margin: 0 0 8px;
  }

  .spot-no-camping p {
    margin: 0 0 24px;
    font-size: 14px;
  }

  .spot-camping-select-section {
    margin-bottom: 4px;
  }

  .spot-section-label {
    font-size: 14px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 12px 0;
  }

  .spot-camping-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
  }

  .spot-camping-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    box-shadow: 0 2px 6px rgba(0,0,0,.04);
  }

  .spot-camping-card:hover {
    border-color: #86efac;
    background: #f0fdf4;
  }

  .spot-camping-card.selected {
    border-color: #3f6212;
    background: #f0fdf4;
  }

  .spot-camping-card-icon {
    font-size: 28px;
    flex-shrink: 0;
  }

  .spot-camping-card-info {
    flex: 1;
    min-width: 0;
  }

  .spot-camping-card-name {
    font-weight: 700;
    font-size: 14px;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .spot-camping-card-city {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
  }

  .spot-camping-card-check {
    font-size: 18px;
    color: #3f6212;
    font-weight: 700;
    flex-shrink: 0;
  }

  .spot-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-top: 4px;
  }

  @media (max-width: 640px) {
    .spot-type-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  .spot-summary {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-top: 8px;
  }

  .spot-summary-title {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .spot-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    padding: 5px 0;
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
  }

  .spot-summary-row:last-child {
    border-bottom: none;
  }

  .spot-summary-row strong {
    color: #1f2937;
  }

</style>