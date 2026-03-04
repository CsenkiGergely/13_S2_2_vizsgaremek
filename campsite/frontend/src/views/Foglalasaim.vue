<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import QRCode from 'qrcode'

const { user, isAuthenticated } = useAuth()
const router = useRouter()

// --- Állapotok ---
const bookings = ref([])
const bookingsLoading = ref(false)
const bookingsError = ref(null)
const cancellingId = ref(null)
const qrVisible = ref({})       // { [bookingId]: true/false }
const qrGenerating = ref({})    // { [bookingId]: true/false }

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

// --- QR kód generálása JS library-vel ---
const generateQr = async (booking) => {
  const id = booking.id
  if (qrVisible.value[id]) {
    // Ha már látható, toggle ki
    qrVisible.value[id] = false
    return
  }

  qrGenerating.value[id] = true
  try {
    // Ha nincs qr_code a booking-ban, lekérjük a backendtől
    let qrString = booking.qr_code
    if (!qrString) {
      const res = await api.get(`/bookings/${id}/qr-code`)
      qrString = res.data.qr_code
      booking.qr_code = qrString
    }

    qrVisible.value[id] = true

    // Várjuk meg, hogy a canvas megjelenjen a DOM-ban
    await nextTick()

    const canvas = document.getElementById(`qr-canvas-${id}`)
    if (canvas) {
      await QRCode.toCanvas(canvas, qrString, {
        width: 220,
        margin: 2,
        color: { dark: '#1f2937', light: '#ffffff' }
      })
    }
  } catch (err) {
    console.error('QR kód generálási hiba:', err)
    alert('Nem sikerült generálni a QR kódot.')
  } finally {
    qrGenerating.value[id] = false
  }
}

// --- QR kód letöltése ---
const downloadQr = (booking) => {
  const canvas = document.getElementById(`qr-canvas-${booking.id}`)
  if (!canvas) return
  const link = document.createElement('a')
  link.download = `campsite-qr-${booking.id}.png`
  link.href = canvas.toDataURL('image/png')
  link.click()
}

// --- Foglalás lemondása ---
const cancelBooking = async (bookingId) => {
  if (!confirm('Biztosan le szeretnéd mondani ezt a foglalást?')) return
  try {
    cancellingId.value = bookingId
    await api.delete(`/bookings/${bookingId}`)
    const idx = bookings.value.findIndex(b => b.id === bookingId)
    if (idx !== -1) {
      bookings.value[idx].status = 'cancelled'
      qrVisible.value[bookingId] = false
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

const pastBookings = computed(() => {
  return bookings.value.filter(b => ['completed', 'cancelled'].includes(b.status))
})

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

// QR kód megjeleníthető-e (confirmed vagy checked_in státuszúaknál)
const canShowQr = (booking) => {
  return ['confirmed', 'checked_in'].includes(booking.status)
}

onMounted(() => {
  if (!isAuthenticated.value) {
    router.push('/')
    return
  }
  fetchBookings()
})
</script>

<template>
<div class="container">

  <!-- Fejléc -->
  <div class="page-header">
    <h1>📅 Foglalásaim</h1>
    <p class="subtitle" v-if="bookings.length > 0">
      {{ activeBookings.length }} aktív foglalás · {{ bookings.length }} összesen
    </p>
  </div>

  <!-- Betöltés -->
  <div v-if="bookingsLoading" class="empty-text">
    Foglalások betöltése...
  </div>

  <!-- Hiba -->
  <div v-else-if="bookingsError" class="empty-text error">
    {{ bookingsError }}
    <button @click="fetchBookings" class="retry-btn">Újrapróbálás</button>
  </div>

  <!-- Üres -->
  <div v-else-if="bookings.length === 0" class="empty-state">
    <div class="empty-icon">📋</div>
    <p class="empty-title">Még nincs foglalásod</p>
    <p class="empty-subtitle">Keress egy kempinget és foglalj helyet!</p>
    <router-link to="/kereses" class="browse-btn">Kempingek böngészése</router-link>
  </div>

  <!-- Foglalás lista -->
  <template v-else>

    <!-- Aktív foglalások -->
    <div v-if="activeBookings.length > 0" class="section">
      <h2 class="section-title">Aktív foglalások</h2>
      <div class="bookings-list">
        <div
          v-for="booking in activeBookings"
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
            <div class="booking-actions">
              <!-- QR kód gomb - csak confirmed/checked_in esetén -->
              <button
                v-if="canShowQr(booking)"
                @click="generateQr(booking)"
                :disabled="qrGenerating[booking.id]"
                class="qr-btn"
              >
                {{ qrGenerating[booking.id] ? 'Generálás...' : qrVisible[booking.id] ? '✕ QR elrejtése' : '📱 Belépési QR kód' }}
              </button>

              <!-- Lemondás -->
              <button
                v-if="booking.status === 'pending' || booking.status === 'confirmed'"
                @click="cancelBooking(booking.id)"
                :disabled="cancellingId === booking.id"
                class="cancel-btn"
              >
                {{ cancellingId === booking.id ? 'Lemondás...' : '✕ Lemondás' }}
              </button>
            </div>

            <!-- QR kód megjelenítése -->
            <div v-if="qrVisible[booking.id]" class="qr-section">
              <div class="qr-wrapper">
                <canvas :id="`qr-canvas-${booking.id}`"></canvas>
                <p class="qr-hint">Mutasd meg ezt a QR kódot a kemping bejáratánál</p>
                <p class="qr-code-text">{{ booking.qr_code }}</p>
                <button @click="downloadQr(booking)" class="download-btn">
                  ⬇ Letöltés PNG-ként
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Korábbi foglalások -->
    <div v-if="pastBookings.length > 0" class="section">
      <h2 class="section-title">Korábbi foglalások</h2>
      <div class="bookings-list">
        <div
          v-for="booking in pastBookings"
          :key="booking.id"
          class="booking-card past"
        >
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
            </div>

            <div class="booking-price" v-if="booking.camping_spot?.price_per_night">
              <span class="price-total">
                {{ (getNights(booking.arrival_date, booking.departure_date) * booking.camping_spot.price_per_night).toLocaleString('hu-HU') }} Ft
              </span>
            </div>
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
  max-width: 1140px;
  margin: auto;
  padding: 30px 20px;
}

/* Fejléc */
.page-header {
  margin-bottom: 28px;
}

.page-header h1 {
  font-size: 26px;
  font-weight: 700;
  color: #1f2937;
}

.subtitle {
  font-size: 14px;
  color: #6b7280;
  margin-top: 4px;
}

/* Szekciók */
.section {
  margin-bottom: 32px;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 14px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

/* Foglalás lista */
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

.booking-card.past {
  opacity: 0.7;
}

.booking-card.past:hover {
  opacity: 0.85;
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

/* Műveletek */
.booking-actions {
  margin-top: auto;
  padding-top: 8px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.qr-btn {
  padding: 7px 16px;
  border: 1px solid #4A7434;
  background: #f0fdf4;
  color: #4A7434;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.qr-btn:hover {
  background: #dcfce7;
  border-color: #3d6129;
}

.qr-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cancel-btn {
  padding: 7px 16px;
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

/* QR kód szekció */
.qr-section {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
}

.qr-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  background: #f9fafb;
  padding: 20px;
  border-radius: 10px;
  border: 1px dashed #d1d5db;
}

.qr-hint {
  font-size: 13px;
  color: #6b7280;
  text-align: center;
}

.qr-code-text {
  font-size: 11px;
  font-family: monospace;
  color: #9ca3af;
  letter-spacing: 1px;
}

.download-btn {
  margin-top: 4px;
  padding: 6px 14px;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.download-btn:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

/* Üres állapot */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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

.empty-text.error {
  color: #ef4444;
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

/* Reszponzív */
@media (max-width: 640px) {
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

  .page-header h1 {
    font-size: 22px;
  }
}
</style>
