<script setup>
import { ref, onMounted } from 'vue'
import { useBooking } from '../composables/useBooking'
import { useDashboard } from '../composables/useDashboard'
import AuthModal from '../components/AuthModal.vue'
import dayjs from 'dayjs';
import "dayjs/locale/hu";
dayjs.locale("hu");

const { bookings, getAllBookings, prices, getPrices } = useBooking()
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

onMounted(() => {
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

        <div v-if="revenueByType.length > 0" v-for="type in revenueByType" :key="type.name" class="booking">
          <div>
            <div class="name">{{ type.name }}</div>
            <div class="place">{{ type.count }} foglalás</div>
          </div>
          <div class="right">
            <div class="price">{{ type.revenue.toLocaleString('hu-HU') }} Ft</div>
            <p>{{ type.percentage }}%</p>
          </div>
        </div>
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
      display: flex;
      justify-content: space-between;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 12px;
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
  }

  .btn-link:hover {
    background: #2d4609;
  }
</style>