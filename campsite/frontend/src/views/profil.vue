<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'
import api from '../api/axios'

const { user, logout } = useAuth()
const router = useRouter()

// --- Állapotok ---
const activeTab = ref('profile') // 'profile' | 'bookings'
const bookings = ref([])
const bookingsLoading = ref(false)
const bookingsError = ref(null)
const cancellingId = ref(null)

// --- Foglalások betöltése ---
const fetchBookings = async () => {
  try {
    bookingsLoading.value = true
    bookingsError.value = null
    const res = await api.get('/bookings')
    bookings.value = res.data.data || res.data || []
  } catch (err) {
    console.error('Foglalások betöltési hiba:', err)
    bookingsError.value = 'Nem sikerült betölteni a foglalásokat.'
  } finally {
    bookingsLoading.value = false
  }
}

// --- Foglalás lemondása ---
const cancelBooking = async (bookingId) => {
  if (!confirm('Biztosan le szeretnéd mondani ezt a foglalást?')) return
  try {
    cancellingId.value = bookingId
    await api.delete(`/bookings/${bookingId}`)
    // Frissítjük a listát
    const idx = bookings.value.findIndex(b => b.id === bookingId)
    if (idx !== -1) {
      bookings.value[idx].status = 'cancelled'
    }
  } catch (err) {
    console.error('Lemondási hiba:', err)
    alert(err.response?.data?.message || 'Nem sikerült lemondani a foglalást.')
  } finally {
    cancellingId.value = null
  }
}

// --- Computed ---
const activeBookings = computed(() => {
  return bookings.value.filter(b => ['pending', 'confirmed', 'checked_in'].includes(b.status))
})

const totalBookings = computed(() => bookings.value.length)

const statusLabels = {
  pending: 'Függőben',
  confirmed: 'Megerősítve',
  checked_in: 'Bejelentkezve',
  completed: 'Befejezett',
  cancelled: 'Lemondva'
}

const statusColors = {
  pending: 'status-pending',
  confirmed: 'status-confirmed',
  checked_in: 'status-checked',
  completed: 'status-completed',
  cancelled: 'status-cancelled'
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('hu-HU', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const getNights = (arrival, departure) => {
  if (!arrival || !departure) return 0
  const a = new Date(arrival)
  const d = new Date(departure)
  return Math.ceil((d - a) / (1000 * 60 * 60 * 24))
}

const handleLogout = async () => {
  await logout()
  router.push('/')
}

// --- Tab váltás ---
const switchTab = (tab) => {
  activeTab.value = tab
  if (tab === 'bookings' && bookings.value.length === 0 && !bookingsLoading.value) {
    fetchBookings()
  }
}

onMounted(() => {
  // Előre betöltjük a foglalásokat a statisztikákhoz
  fetchBookings()
})
</script>

<template>
<div class="container">

    <div class="top-bar">
        <div class="profile-header">
            <div class="avatar">{{ user?.owner_first_name?.charAt(0).toUpperCase() || 'U' }}</div>
            <div class="profile-info">
                <h1>{{ user?.owner_first_name }} {{ user?.owner_last_name }}</h1>
                <p>{{ user?.email }}</p>
            </div>
        </div>
        <button @click="handleLogout" class="logout-btn">
          🚪 Kijelentkezés
        </button>
    </div>

    <!-- Tab navigáció -->
    <div class="tab-nav">
      <button
        :class="['tab-btn', { active: activeTab === 'profile' }]"
        @click="switchTab('profile')"
      >
        👤 Profilom
      </button>
      <button
        :class="['tab-btn', { active: activeTab === 'bookings' }]"
        @click="switchTab('bookings')"
      >
        📅 Foglalásaim
        <span v-if="activeBookings.length > 0" class="tab-badge">{{ activeBookings.length }}</span>
      </button>
    </div>

    <!-- PROFIL TAB -->
    <template v-if="activeTab === 'profile'">
      <div class="cards">
        <div class="card">
            <h2>Profil adatok</h2>

            <div class="form-group">
                <label>Név</label>
                <input type="text" :value="(user?.owner_last_name || '') + ' ' + (user?.owner_first_name || '')" readonly>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" :value="user?.email" readonly>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="text" :value="user?.phone || 'Nincs megadva'" readonly>
            </div>
        </div>

        <div class="card">
            <h2>Foglalási statisztikák</h2>
            <div class="stats">
                <div>
                    <div class="stat-number">{{ activeBookings.length }}</div>
                    <div class="stat-label">Aktív foglalás</div>
                </div>
                <div>
                    <div class="stat-number">{{ totalBookings }}</div>
                    <div class="stat-label">Összes foglalás</div>
                </div>
            </div>
        </div>
      </div>
    </template>

    <!-- FOGLALÁSAIM TAB -->
    <template v-if="activeTab === 'bookings'">

      <!-- Betöltés -->
      <div v-if="bookingsLoading" class="empty-text">
        Foglalások betöltése...
      </div>

      <!-- Hiba -->
      <div v-else-if="bookingsError" class="empty-text" style="color: #ef4444;">
        {{ bookingsError }}
        <button @click="fetchBookings" class="retry-btn">Újrapróbálás</button>
      </div>

      <!-- Üres -->
      <div v-else-if="bookings.length === 0" class="bookings-section">
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <p class="empty-title">Még nincs foglalásod</p>
          <p class="empty-subtitle">Keress egy kempinget és foglalj helyet!</p>
          <router-link to="/kereses" class="browse-btn">Kempingek böngészése</router-link>
        </div>
      </div>

      <!-- Foglalás lista -->
      <div v-else class="bookings-list">
        <div
          v-for="booking in bookings"
          :key="booking.id"
          class="booking-card"
        >
          <!-- Kép -->
          <div class="booking-image">
            <img
              v-if="booking.camping?.photos?.length > 0"
              :src="booking.camping.photos[0].photo_url"
              :alt="booking.camping?.camping_name"
              @error="$event.target.src = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=400'"
            />
            <img
              v-else
              src="https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=400"
              alt="Kemping"
            />
            <span :class="['booking-status', statusColors[booking.status]]">
              {{ statusLabels[booking.status] || booking.status }}
            </span>
          </div>

          <!-- Adatok -->
          <div class="booking-details">
            <h3 class="booking-name">{{ booking.camping?.camping_name || 'Kemping' }}</h3>

            <p class="booking-location" v-if="booking.camping?.location">
              📍 {{ [booking.camping.location.zip_code, booking.camping.location.city, booking.camping.location.street_address].filter(Boolean).join(', ') }}
            </p>

            <div class="booking-info-grid">
              <div class="info-item">
                <span class="info-label">Érkezés</span>
                <span class="info-value">{{ formatDate(booking.arrival_date) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Távozás</span>
                <span class="info-value">{{ formatDate(booking.departure_date) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Éjszakák</span>
                <span class="info-value">{{ getNights(booking.arrival_date, booking.departure_date) }} éj</span>
              </div>
              <div class="info-item" v-if="booking.camping_spot">
                <span class="info-label">Hely</span>
                <span class="info-value">{{ booking.camping_spot.name || `#${booking.camping_spot.spot_id}` }}</span>
              </div>
            </div>

            <!-- Ár -->
            <div class="booking-price" v-if="booking.camping_spot?.price_per_night">
              <span class="price-total">
                {{ (getNights(booking.arrival_date, booking.departure_date) * booking.camping_spot.price_per_night).toLocaleString('hu-HU') }} Ft
              </span>
              <span class="price-detail">
                ({{ booking.camping_spot.price_per_night.toLocaleString('hu-HU') }} Ft × {{ getNights(booking.arrival_date, booking.departure_date) }} éj)
              </span>
            </div>

            <!-- Műveletek -->
            <div class="booking-actions" v-if="booking.status === 'pending' || booking.status === 'confirmed'">
              <button
                @click="cancelBooking(booking.id)"
                :disabled="cancellingId === booking.id"
                class="cancel-btn"
              >
                {{ cancellingId === booking.id ? 'Lemondás...' : '✕ Lemondás' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

</div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

.container {
    max-width: 1100px;
    margin: auto;
    padding: 30px 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #4A7434;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 22px;
}

.profile-info h1 {
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
}

.profile-info p {
    color: #6b7280;
    font-size: 14px;
}

.logout-btn {
    padding: 10px 18px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: white;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}
.logout-btn:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}

/* Tab navigáció */
.tab-nav {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 10px;
}

.tab-btn {
    flex: 1;
    padding: 10px 16px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.tab-btn:hover {
    color: #374151;
}

.tab-btn.active {
    background: white;
    color: #4A7434;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tab-badge {
    background: #4A7434;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}

/* Cards */
.cards {
    display: flex;
    gap: 25px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    flex: 1;
}

.card h2 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #1f2937;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
}

input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    color: #374151;
    background: #f9fafb;
}

.stats {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #4A7434;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

/* Foglalások lista */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.booking-card {
    display: flex;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.booking-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.booking-image {
    position: relative;
    width: 220px;
    min-height: 180px;
    flex-shrink: 0;
}

.booking-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.booking-status {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}
.status-confirmed {
    background: #d1fae5;
    color: #065f46;
}
.status-checked {
    background: #dbeafe;
    color: #1e40af;
}
.status-completed {
    background: #e5e7eb;
    color: #374151;
}
.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.booking-details {
    flex: 1;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.booking-name {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.booking-location {
    font-size: 13px;
    color: #6b7280;
}

.booking-info-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 24px;
    margin-top: 4px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.info-label {
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.booking-price {
    margin-top: 4px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.price-total {
    font-size: 18px;
    font-weight: 700;
    color: #4A7434;
}

.price-detail {
    font-size: 12px;
    color: #9ca3af;
}

.booking-actions {
    margin-top: auto;
    padding-top: 8px;
}

.cancel-btn {
    padding: 6px 14px;
    border: 1px solid #fca5a5;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.cancel-btn:hover {
    background: #fee2e2;
    border-color: #ef4444;
}

.cancel-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Üres állapot */
.bookings-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    padding: 40px 20px;
}

.empty-state {
    text-align: center;
    padding: 20px 0;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.empty-title {
    font-size: 18px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 6px;
}

.empty-subtitle {
    font-size: 14px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.browse-btn {
    display: inline-block;
    padding: 10px 24px;
    background: #4A7434;
    color: white;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: background 0.2s;
}

.browse-btn:hover {
    background: #3d6129;
}

.empty-text {
    text-align: center;
    color: #6b7280;
    padding: 30px 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.retry-btn {
    display: block;
    margin: 12px auto 0;
    padding: 8px 16px;
    background: #4A7434;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 900px) {
    .cards {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .booking-card {
        flex-direction: column;
    }

    .booking-image {
        width: 100%;
        height: 180px;
    }

    .booking-info-grid {
        gap: 8px 16px;
    }

    .profile-info h1 {
        font-size: 20px;
    }
}
</style>