<script setup>
import { ref, onMounted, watch } from 'vue'
import { useBooking } from '../composables/useBooking'
import { useGate } from '../composables/useGate'
import { useDashboard } from '../composables/useDashboard'
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

onMounted(async () => {
  await fetchMyCampings()
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
        <div class="tab" :class="{ active: activeTab === 'foglalasok' }" @click="activeTab = 'foglalasok'">Foglalások</div>
        <div class="tab" :class="{ active: activeTab === 'kapuk' }" @click="activeTab = 'kapuk'">Kapuk</div>
        <div class="tab" :class="{ active: activeTab === 'terkep' }" @click="activeTab = 'terkep'">Térkép</div>
        <div class="tab" :class="{ active: activeTab === 'bevetelek' }" @click="activeTab = 'bevetelek'">Bevételek</div>
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
          <div class="trend">{{ dashboard?.occupancyPercentage || 0 }}% foglaltság, {{ formatChange(dashboard?.occupancyPercentage, dashboard?.previousOccupancyPercentage) }}</div>
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
    
    /* Mobil nézet javítása: kerüljük a jobb oszlop fix min-width használatát, ami vízszintes túlfolyást és
       elcsúszást okozott a dashboard és bevételek listáján. Kis képernyőn a jobb rész legyen teljes szélességű,
       balra rendezett, hogy az összegek és százalékok szépen egymás alá törjenek. */
    .booking .right {
      min-width: 0; /* allow to shrink */
      align-items: flex-start; /* align contents to the left */
      text-align: left;
      width: 100%;
    }

    .booking .right .price {
      margin-top: 6px;
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

</style>