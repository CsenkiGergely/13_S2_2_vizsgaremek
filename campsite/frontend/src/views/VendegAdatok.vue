<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api/axios'

const router = useRouter()
const route = useRoute()

// Foglalási adatok a query-ből
const campingId = computed(() => route.query.campingId)
const campingName = computed(() => route.query.campingName || '')
const spotName = computed(() => route.query.spotName || '')
const guestCount = computed(() => Number(route.query.guests) || 1)
const nightsCount = computed(() => Number(route.query.nights) || 0)
const totalAmount = computed(() => Number(route.query.total) || 0)
const arrivalDate = computed(() => route.query.arrivalDate || '')
const departureDate = computed(() => route.query.departureDate || '')

const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const savedGuests = ref([])
const requiredFields = ref([]) // A tulajdonos által kért mezők

// Összes lehetséges mező — ugyanaz, mint a Tulajdonos.vue-ban
const allFields = [
  { key: 'first_name', label: 'Keresztnév', type: 'text', placeholder: 'Pl. János' },
  { key: 'last_name', label: 'Vezetéknév', type: 'text', placeholder: 'Pl. Kovács' },
  { key: 'birth_date', label: 'Születési dátum', type: 'date', placeholder: '' },
  { key: 'place_of_birth', label: 'Születési hely', type: 'text', placeholder: 'Pl. Budapest' },
  { key: 'gender', label: 'Nem', type: 'select', options: ['férfi', 'nő', 'egyéb'] },
  { key: 'citizenship', label: 'Állampolgárság', type: 'text', placeholder: 'Pl. magyar' },
  { key: 'mothers_birth_name', label: 'Anyja születési neve', type: 'text', placeholder: '' },
  { key: 'id_card_number', label: 'Személyigazolvány szám', type: 'text', placeholder: 'Pl. 123456AB' },
  { key: 'passport_number', label: 'Útlevél szám', type: 'text', placeholder: '' },
  { key: 'visa', label: 'Vízum', type: 'text', placeholder: '' },
  { key: 'resident_permit_number', label: 'Tartózkodási engedély szám', type: 'text', placeholder: '' },
  { key: 'date_of_entry', label: 'Belépés dátuma', type: 'date', placeholder: '' },
  { key: 'place_of_entry', label: 'Belépés helye', type: 'text', placeholder: '' },
]

// Csak a tulajdonos által kért mezők
const activeFields = computed(() =>
  allFields.filter(f => requiredFields.value.includes(f.key))
)

// Üres vendég objektum generálása a kért mezőkkel
const createEmptyGuest = () => {
  const obj = { selectedSavedId: null }
  allFields.forEach(f => { obj[f.key] = '' })
  return obj
}

// Foglaló + további vendégek
const selfData = ref(createEmptyGuest())
const additionalGuests = ref([])

// Kemping required_guest_fields lekérése
const fetchCampingFields = async () => {
  try {
    const res = await api.get(`/campings/${campingId.value}`)
    const camping = res.data?.data || res.data?.camping || res.data
    requiredFields.value = camping.required_guest_fields || []
  } catch (e) {
    console.error('Kemping adatok betöltési hiba:', e)
  }
}

const fetchSavedGuests = async () => {
  try {
    const res = await api.get('/user-guests')
    savedGuests.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Mentett vendégek betöltési hiba:', e)
  }
}

const fetchUser = async () => {
  try {
    const res = await api.get('/user')
    const user = res.data
    selfData.value.first_name = user.owner_first_name || ''
    selfData.value.last_name = user.owner_last_name || ''
  } catch (e) {
    console.error('Felhasználó betöltési hiba:', e)
  }
}

onMounted(async () => {
  if (!campingId.value) {
    router.push('/kereses')
    return
  }

  loading.value = true
  await Promise.all([fetchCampingFields(), fetchUser(), fetchSavedGuests()])

  const extraCount = Math.max(0, guestCount.value - 1)
  additionalGuests.value = Array.from({ length: extraCount }, () => createEmptyGuest())
  loading.value = false
})

// Mentett vendég kiválasztása
const selectSavedGuest = (index, guestId) => {
  const saved = savedGuests.value.find(g => g.id === guestId)
  if (!saved) return
  const guest = additionalGuests.value[index]
  allFields.forEach(f => {
    const val = saved[f.key] || ''
    guest[f.key] = f.type === 'date' && val ? val.split('T')[0] : val
  })
  guest.selectedSavedId = guestId
}

const clearSavedGuest = (index) => {
  const guest = additionalGuests.value[index]
  allFields.forEach(f => { guest[f.key] = '' })
  guest.selectedSavedId = null
}

// Validáció — csak a kért mezőket ellenőrizzük
const errors = ref({})

const validate = () => {
  errors.value = {}

  requiredFields.value.forEach(key => {
    if (!selfData.value[key]?.toString().trim()) {
      errors.value[`self_${key}`] = 'Kötelező mező'
    }
  })

  additionalGuests.value.forEach((guest, i) => {
    requiredFields.value.forEach(key => {
      if (!guest[key]?.toString().trim()) {
        errors.value[`guest_${i}_${key}`] = 'Kötelező mező'
      }
    })
  })

  return Object.keys(errors.value).length === 0
}

// Mentés és tovább a fizetésre
const handleContinue = async () => {
  // Ha nincs egy kért mező sem, egyből tovább
  if (requiredFields.value.length === 0) {
    router.push({ path: '/fizetes', query: { ...route.query } })
    return
  }

  if (!validate()) return

  saving.value = true
  error.value = null

  try {
    // Payload összeállítása — csak a kért mezőket küldjük
    const buildPayload = (data) => {
      const payload = {}
      requiredFields.value.forEach(key => {
        if (data[key]) payload[key] = data[key]
      })
      return payload
    }

    // Saját adatok mentése
    const selfPayload = buildPayload(selfData.value)
    const existingSelf = savedGuests.value.find(
      g => g.first_name === selfPayload.first_name && g.last_name === selfPayload.last_name
    )
    if (!existingSelf) {
      await api.post('/user-guests', selfPayload)
    }

    // További vendégek mentése
    for (const guest of additionalGuests.value) {
      if (guest.selectedSavedId) continue
      await api.post('/user-guests', buildPayload(guest))
    }

    router.push({ path: '/fizetes', query: { ...route.query } })
  } catch (e) {
    console.error('Vendég mentési hiba:', e)
    error.value = e.response?.data?.message || 'Hiba történt a vendég adatok mentésekor.'
  } finally {
    saving.value = false
  }
}

const goBack = () => { router.back() }

const formatDisplayDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getFullYear()}. ${String(d.getMonth() + 1).padStart(2, '0')}. ${String(d.getDate()).padStart(2, '0')}.`
}
</script>

<template>
  <div class="page-guests">
    <div class="hero">
      <div class="container">

        <div class="title">
          <h1>Vendég adatok</h1>
          <p class="lead">Add meg a foglaláshoz szükséges személyes adatokat</p>
        </div>

        <!-- Foglalási összesítő -->
        <div class="booking-summary">
          <div class="summary-row" v-if="campingName">
            <span>Kemping:</span>
            <span class="summary-value">{{ campingName }}</span>
          </div>
          <div class="summary-row" v-if="spotName">
            <span>Hely:</span>
            <span class="summary-value">{{ spotName }}</span>
          </div>
          <div class="summary-row" v-if="arrivalDate && departureDate">
            <span>Időszak:</span>
            <span class="summary-value">{{ formatDisplayDate(arrivalDate) }} – {{ formatDisplayDate(departureDate) }}</span>
          </div>
          <div class="summary-row" v-if="nightsCount > 0">
            <span>Éjszakák:</span>
            <span class="summary-value">{{ nightsCount }} éjszaka</span>
          </div>
          <div class="summary-row">
            <span>Vendégek:</span>
            <span class="summary-value">{{ guestCount }} fő</span>
          </div>
          <div class="summary-row total" v-if="totalAmount > 0">
            <span>Összesen:</span>
            <span class="summary-value">{{ totalAmount.toLocaleString('hu-HU') }} Ft</span>
          </div>
        </div>

        <!-- Betöltés -->
        <div v-if="loading" class="loading-state">
          <p>Betöltés...</p>
        </div>

        <template v-else>

          <!-- Nincs kért mező -->
          <div v-if="activeFields.length === 0" class="guest-card" style="text-align:center; padding:2rem;">
            <p style="color:#6b7280;">A kemping tulajdonosa nem kér vendég adatokat.</p>
            <button type="button" class="btn btn-primary" style="margin-top:1rem;" @click="handleContinue">
              Tovább a fizetéshez →
            </button>
          </div>

          <template v-else>
            <!-- 1. Foglaló saját adatai -->
            <div class="guest-card">
              <h2 class="guest-card-title">👤 Foglaló adatai (Te)</h2>
              <div class="form-grid">
                <div v-for="field in activeFields" :key="field.key" class="form-group">
                  <label>{{ field.label }} *</label>
                  <select v-if="field.type === 'select'" v-model="selfData[field.key]"
                          :class="{ 'input-error': errors[`self_${field.key}`] }">
                    <option value="" disabled>-- Válassz --</option>
                    <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                  <input v-else v-model="selfData[field.key]" :type="field.type"
                         :placeholder="field.placeholder"
                         :class="{ 'input-error': errors[`self_${field.key}`] }" />
                  <span v-if="errors[`self_${field.key}`]" class="error-msg">{{ errors[`self_${field.key}`] }}</span>
                </div>
              </div>
            </div>

            <!-- 2. További vendégek -->
            <div v-for="(guest, index) in additionalGuests" :key="index" class="guest-card">
              <h2 class="guest-card-title">👥 {{ index + 2 }}. vendég</h2>

              <!-- Mentett vendég választó -->
              <div v-if="savedGuests.length > 0" class="saved-selector">
                <label>Korábban mentett vendég kiválasztása:</label>
                <div class="saved-selector-row">
                  <select @change="selectSavedGuest(index, Number($event.target.value))"
                          :value="guest.selectedSavedId || ''">
                    <option value="" disabled>-- Válassz mentett vendéget --</option>
                    <option v-for="sg in savedGuests" :key="sg.id" :value="sg.id">
                      {{ sg.last_name }} {{ sg.first_name }}
                    </option>
                  </select>
                  <button v-if="guest.selectedSavedId" type="button" class="btn-clear"
                          @click="clearSavedGuest(index)">✕ Törlés</button>
                </div>
              </div>

              <div class="form-grid">
                <div v-for="field in activeFields" :key="field.key" class="form-group">
                  <label>{{ field.label }} *</label>
                  <select v-if="field.type === 'select'" v-model="guest[field.key]"
                          :class="{ 'input-error': errors[`guest_${index}_${field.key}`] }">
                    <option value="" disabled>-- Válassz --</option>
                    <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                  <input v-else v-model="guest[field.key]" :type="field.type"
                         :placeholder="field.placeholder"
                         :class="{ 'input-error': errors[`guest_${index}_${field.key}`] }" />
                  <span v-if="errors[`guest_${index}_${field.key}`]" class="error-msg">{{ errors[`guest_${index}_${field.key}`] }}</span>
                </div>
              </div>
            </div>

            <!-- Hiba üzenet -->
            <p v-if="error" class="global-error">{{ error }}</p>

            <!-- Gombok -->
            <div class="actions">
              <button type="button" class="btn btn-outline" @click="goBack">← Vissza</button>
              <button type="button" class="btn btn-primary" @click="handleContinue" :disabled="saving">
                {{ saving ? 'Mentés...' : 'Tovább a fizetéshez →' }}
              </button>
            </div>
          </template>

        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-guests {
  background: #fff;
  min-height: 100vh;
  padding: 2rem 0;
}

.hero {
  padding: 2rem 0;
}

.container {
  max-width: 720px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.title {
  text-align: center;
  margin-bottom: 1.5rem;
}
.title h1 {
  font-size: 1.75rem;
  font-weight: 800;
  color: #1f2937;
  margin-bottom: 0.3rem;
}
.lead {
  color: #6b7280;
  font-size: 0.95rem;
}

/* Foglalási összesítő */
.booking-summary {
  background: #f8faf8;
  border: 1px solid #e8ede5;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.35rem 0;
  font-size: 0.9rem;
  color: #6b7280;
}
.summary-row.total {
  border-top: 1px solid #e8ede5;
  margin-top: 0.4rem;
  padding-top: 0.6rem;
  font-weight: 700;
  color: #1f2937;
  font-size: 1rem;
}
.summary-value {
  font-weight: 600;
  color: #1f2937;
}

/* Vendég kártya */
.guest-card {
  background: #fff;
  border-radius: 14px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
  border: 1px solid #e8ede5;
}
.guest-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e8ede5;
}

/* Mentett vendég választó */
.saved-selector {
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #f8faf8;
  border-radius: 8px;
  border: 1px solid #e8ede5;
}
.saved-selector label {
  display: block;
  font-size: 0.82rem;
  color: #6b7280;
  margin-bottom: 0.4rem;
}
.saved-selector-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.saved-selector select {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  background: #fff;
  color: #1f2937;
  outline: none;
}
.saved-selector select:focus {
  border-color: #4A7434;
}
.btn-clear {
  background: none;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.82rem;
  color: #6b7280;
}
.btn-clear:hover {
  border-color: #ef4444;
  color: #ef4444;
}

/* Form grid */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
}

.form-group {
  display: flex;
  flex-direction: column;
}
.form-group label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.3rem;
}
.form-group input {
  padding: 0.6rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.15s;
}
.form-group input:focus {
  border-color: #4A7434;
  box-shadow: 0 0 0 2px rgba(74, 116, 52, 0.15);
}
.form-group input.input-error {
  border-color: #ef4444;
}
.error-msg {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.2rem;
}
.global-error {
  text-align: center;
  color: #dc2626;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 1rem;
}

/* Betöltés */
.loading-state {
  text-align: center;
  color: #6b7280;
  padding: 3rem 0;
  font-size: 1rem;
}

/* Gombok */
.actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 0.5rem;
}
.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: background 0.2s, transform 0.1s;
}
.btn:active {
  transform: scale(0.98);
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-primary {
  background: #F17E21;
  color: #fff;
  flex: 1;
}
.btn-primary:hover:not(:disabled) {
  background: #d96a10;
}
.btn-outline {
  background: transparent;
  color: #4A7434;
  border: 2px solid #4A7434;
}
.btn-outline:hover {
  background: #f0f5ee;
}
</style>
