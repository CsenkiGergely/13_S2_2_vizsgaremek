<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()
const activeTab = ref('visa') // visa, mastercard, amex
const paymentSuccess = ref(false)
const paymentLoading = ref(false)

// Foglalási adatok a query-ből
const bookingId = computed(() => route.query.bookingId || null)
const totalAmount = computed(() => Number(route.query.total) || 0)
const nightsCount = computed(() => Number(route.query.nights) || 0)
const campingName = computed(() => route.query.campingName || '')
const spotName = computed(() => route.query.spotName || '')

const paymentForm = ref({
  cardNumber: '',
  expiryDate: '',
  cvv: '',
  firstName: '',
  lastName: '',
  city: '',
  streetAndNumber: '',
  postalCode: ''
})

const errors = ref({})

// Validációs függvények
const validateCardNumber = (cardNumber) => {
  const cleaned = cardNumber.replace(/\s/g, '')
  
  // Kártyatípus szerint validálás
  if (activeTab.value === 'visa') {
    if (!/^4\d{15}$/.test(cleaned)) {
      return 'Visa kártya 16 számjegyből áll és 4-gyel kezdődik'
    }
  } else if (activeTab.value === 'mastercard') {
    if (!/^5[1-5]\d{14}$/.test(cleaned)) {
      return 'Mastercard 16 számjegyből áll és 51-55 között kezdődik'
    }
  } else if (activeTab.value === 'amex') {
    if (!/^3[47]\d{13}$/.test(cleaned)) {
      return 'American Express 15 számjegyből áll és 34 vagy 37-tel kezdődik'
    }
  }
  
  // Luhn algoritmus
  let sum = 0
  let isEven = false
  
  for (let i = cleaned.length - 1; i >= 0; i--) {
    let digit = parseInt(cleaned[i])
    
    if (isEven) {
      digit *= 2
      if (digit > 9) digit -= 9
    }
    
    sum += digit
    isEven = !isEven
  }
  
  if (sum % 10 !== 0) {
    return 'Érvénytelen kártyaszám'
  }
  
  return null
}

const validateExpiryDate = (expiry) => {
  if (!/^\d{2}\/\d{2}$/.test(expiry)) {
    return 'Formátum: HH/ÉÉ'
  }
  
  const [month, year] = expiry.split('/').map(Number)
  
  if (month < 1 || month > 12) {
    return 'Érvénytelen hónap (01-12)'
  }
  
  const currentYear = new Date().getFullYear() % 100
  const currentMonth = new Date().getMonth() + 1
  
  if (year < currentYear || (year === currentYear && month < currentMonth)) {
    return 'A kártya lejárt'
  }
  
  return null
}

const validateCVV = (cvv) => {
  const expectedLength = activeTab.value === 'amex' ? 4 : 3
  const regex = activeTab.value === 'amex' ? /^\d{4}$/ : /^\d{3}$/
  
  if (!regex.test(cvv)) {
    return `A CVV ${expectedLength} számjegyből áll`
  }
  return null
}

const validateName = (name, field) => {
  if (!name || name.trim().length < 2) {
    return `A ${field} legalább 2 karakter hosszú legyen`
  }
  if (!/^[a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ\s\-]+$/.test(name)) {
    return `A ${field} csak betűket tartalmazhat`
  }
  return null
}

const validateCity = (city) => {
  if (!city || city.trim().length < 2) {
    return 'A városnév legalább 2 karakter hosszú legyen'
  }
  return null
}

const validateStreetAndNumber = (street) => {
  if (!street || street.trim().length < 3) {
    return 'Az utca és házszám legalább 3 karakter hosszú legyen'
  }
  return null
}

const validatePostalCode = (postalCode) => {
  if (!/^\d{4}$/.test(postalCode)) {
    return 'Az irányítószám 4 számjegyből áll'
  }
  return null
}

// Formázási függvények
const formatCardNumber = () => {
  let value = paymentForm.value.cardNumber.replace(/\s/g, '')
  value = value.replace(/\D/g, '')
  
  // Amex 15, mások 16 számjegy
  const maxLength = activeTab.value === 'amex' ? 15 : 16
  value = value.substring(0, maxLength)
  
  // Amex: 4-6-5, mások: 4-4-4-4
  let parts
  if (activeTab.value === 'amex') {
    parts = [
      value.substring(0, 4),
      value.substring(4, 10),
      value.substring(10, 15)
    ].filter(p => p)
  } else {
    parts = value.match(/.{1,4}/g)
  }
  
  paymentForm.value.cardNumber = parts ? parts.join(' ') : value
}

const formatExpiryDate = () => {
  let value = paymentForm.value.expiryDate.replace(/\D/g, '')
  
  if (value.length >= 2) {
    value = value.substring(0, 2) + '/' + value.substring(2, 4)
  }
  
  paymentForm.value.expiryDate = value
}

const formatCVV = () => {
  const maxLength = activeTab.value === 'amex' ? 4 : 3
  paymentForm.value.cvv = paymentForm.value.cvv.replace(/\D/g, '').substring(0, maxLength)
}

const formatPostalCode = () => {
  paymentForm.value.postalCode = paymentForm.value.postalCode.replace(/\D/g, '').substring(0, 4)
}

// Kártyatípus váltáskor töröljük a kártyaadatokat
const handleCardTypeChange = () => {
  paymentForm.value.cardNumber = ''
  paymentForm.value.cvv = ''
  errors.value = {}
}

const handlePayment = () => {
  errors.value = {}
  
  const cardNumberError = validateCardNumber(paymentForm.value.cardNumber)
  if (cardNumberError) errors.value.cardNumber = cardNumberError
  
  const expiryError = validateExpiryDate(paymentForm.value.expiryDate)
  if (expiryError) errors.value.expiryDate = expiryError
  
  const cvvError = validateCVV(paymentForm.value.cvv)
  if (cvvError) errors.value.cvv = cvvError
  
  const firstNameError = validateName(paymentForm.value.firstName, 'keresztnév')
  if (firstNameError) errors.value.firstName = firstNameError
  
  const lastNameError = validateName(paymentForm.value.lastName, 'vezetéknév')
  if (lastNameError) errors.value.lastName = lastNameError
  
  const cityError = validateCity(paymentForm.value.city)
  if (cityError) errors.value.city = cityError
  
  const streetError = validateStreetAndNumber(paymentForm.value.streetAndNumber)
  if (streetError) errors.value.streetAndNumber = streetError
  
  const postalCodeError = validatePostalCode(paymentForm.value.postalCode)
  if (postalCodeError) errors.value.postalCode = postalCodeError
  
  if (Object.keys(errors.value).length > 0) {
    return
  }
  
  // Nincs valódi fizetés — ha az adatok helyesek, a foglalás megerősítve
  paymentLoading.value = true
  setTimeout(() => {
    paymentLoading.value = false
    paymentSuccess.value = true
  }, 1500)
}
</script>

<template>
  <div class="page-payment">
    <div class="hero" role="banner">
      <div class="container">

        <!-- Sikeres foglalás -->
        <div v-if="paymentSuccess" class="success-card">
          <div class="success-icon">&#10004;</div>
          <h1 class="success-title">Foglalás megerősítve!</h1>
          <p class="success-text">A foglalásod sikeresen rögzítettük. Köszönjük!</p>

          <div class="success-details" v-if="bookingId">
            <div class="detail-row" v-if="campingName">
              <span class="detail-label">Kemping</span>
              <span class="detail-value">{{ campingName }}</span>
            </div>
            <div class="detail-row" v-if="spotName">
              <span class="detail-label">Hely</span>
              <span class="detail-value">{{ spotName }}</span>
            </div>
            <div class="detail-row" v-if="nightsCount > 0">
              <span class="detail-label">Éjszakák</span>
              <span class="detail-value">{{ nightsCount }} éjszaka</span>
            </div>
            <div class="detail-row total" v-if="totalAmount > 0">
              <span class="detail-label">Összesen</span>
              <span class="detail-value">{{ totalAmount.toLocaleString('hu-HU') }} Ft</span>
            </div>
            <div class="detail-row" v-if="bookingId">
              <span class="detail-label">Foglalás azonosító</span>
              <span class="detail-value">#{{ bookingId }}</span>
            </div>
          </div>

          <div class="success-actions">
            <button class="btn btn-primary" @click="router.push('/')">Vissza a főoldalra</button>
            <button class="btn btn-outline" @click="router.push('/kereses')">Új keresés</button>
          </div>
        </div>

        <!-- Fizetési űrlap -->
        <template v-else>
        <div class="title">
          <h1>Fizetési adatok</h1>
          <p class="lead">Add meg a fizetési és számlázási adataidat</p>
        </div>

        <!-- Foglalási összesítő -->
        <div v-if="bookingId" class="booking-summary">
          <h3>Foglalás összesítő</h3>
          <div class="summary-row" v-if="campingName">
            <span>Kemping:</span>
            <span class="summary-value">{{ campingName }}</span>
          </div>
          <div class="summary-row" v-if="spotName">
            <span>Hely:</span>
            <span class="summary-value">{{ spotName }}</span>
          </div>
          <div class="summary-row" v-if="nightsCount > 0">
            <span>Éjszakák:</span>
            <span class="summary-value">{{ nightsCount }} éjszaka</span>
          </div>
          <div class="summary-row total" v-if="totalAmount > 0">
            <span>Összesen:</span>
            <span class="summary-value">{{ totalAmount.toLocaleString('hu-HU') }} Ft</span>
          </div>
        </div>

        <div class="payment-card">
          <form class="grid" @submit.prevent="handlePayment">
            <!-- Kártyatípus választó -->
            <div class="full-width card-type-selector">
              <label for="cardType">💳 Kártyatípus</label>
              <div class="card-type-wrapper">
                <select 
                  id="cardType" 
                  v-model="activeTab" 
                  @change="handleCardTypeChange"
                  class="card-select"
                >
                  <option value="visa">Visa</option>
                  <option value="mastercard">Mastercard</option>
                  <option value="amex">American Express</option>
                </select>
                <div class="card-logo">
                  <img 
                    v-if="activeTab === 'visa'" 
                    src="/img/visalogo_20220804_111847.png" 
                    alt="Visa"
                    class="logo-img"
                  />
                  <img 
                    v-else-if="activeTab === 'mastercard'" 
                    src="/img/Mastercard_2019_logo.svg" 
                    alt="Mastercard"
                    class="logo-img"
                  />
                  <span v-else class="amex-text">AMEX</span>
                </div>
              </div>
            </div>

            <!-- Kártyaadatok -->
            <h3 class="section-header">Bankkártya adatok</h3>
            
            <div class="full-width">
              <label for="cardNumber">💳 Kártyaszám</label>
              <input 
                id="cardNumber" 
                v-model="paymentForm.cardNumber" 
                @input="formatCardNumber"
                type="text" 
                :placeholder="activeTab === 'amex' ? '3782 822463 10005' : '1234 5678 9012 3456'"
                :maxlength="activeTab === 'amex' ? 17 : 19"
                :class="{ 'error': errors.cardNumber }"
              />
              <span v-if="errors.cardNumber" class="error-message">{{ errors.cardNumber }}</span>
            </div>

            <div>
              <label for="expiryDate">📅 Lejárati dátum</label>
              <input 
                id="expiryDate" 
                v-model="paymentForm.expiryDate" 
                @input="formatExpiryDate"
                type="text" 
                placeholder="HH/ÉÉ"
                maxlength="5"
                :class="{ 'error': errors.expiryDate }"
              />
              <span v-if="errors.expiryDate" class="error-message">{{ errors.expiryDate }}</span>
            </div>

            <div>
              <label for="cvv">🔒 Biztonsági kód ({{ activeTab === 'amex' ? 'CID' : 'CVV' }})</label>
              <input 
                id="cvv" 
                v-model="paymentForm.cvv" 
                @input="formatCVV"
                type="text" 
                :placeholder="activeTab === 'amex' ? '1234' : '123'"
                :maxlength="activeTab === 'amex' ? 4 : 3"
                :class="{ 'error': errors.cvv }"
              />
              <span v-if="errors.cvv" class="error-message">{{ errors.cvv }}</span>
            </div>

            <!-- Személyes adatok -->
            <h3 class="section-header">Számlázási adatok</h3>

            <div>
              <label for="firstName">👤 Keresztnév</label>
              <input 
                id="firstName" 
                v-model="paymentForm.firstName" 
                type="text" 
                placeholder="Pl. János"
                :class="{ 'error': errors.firstName }"
              />
              <span v-if="errors.firstName" class="error-message">{{ errors.firstName }}</span>
            </div>

            <div>
              <label for="lastName">👤 Vezetéknév</label>
              <input 
                id="lastName" 
                v-model="paymentForm.lastName" 
                type="text" 
                placeholder="Pl. Kovács"
                :class="{ 'error': errors.lastName }"
              />
              <span v-if="errors.lastName" class="error-message">{{ errors.lastName }}</span>
            </div>

            <div>
              <label for="city">🏙️ Város</label>
              <input 
                id="city" 
                v-model="paymentForm.city" 
                type="text" 
                placeholder="Pl. Budapest"
                :class="{ 'error': errors.city }"
              />
              <span v-if="errors.city" class="error-message">{{ errors.city }}</span>
            </div>

            <div>
              <label for="streetAndNumber">🏠 Utca és házszám</label>
              <input 
                id="streetAndNumber" 
                v-model="paymentForm.streetAndNumber" 
                type="text" 
                placeholder="Pl. Fő utca 12."
                :class="{ 'error': errors.streetAndNumber }"
              />
              <span v-if="errors.streetAndNumber" class="error-message">{{ errors.streetAndNumber }}</span>
            </div>

            <div>
              <label for="postalCode">📮 Irányítószám</label>
              <input 
                id="postalCode" 
                v-model="paymentForm.postalCode" 
                @input="formatPostalCode"
                type="text" 
                placeholder="Pl. 1234"
                maxlength="4"
                :class="{ 'error': errors.postalCode }"
              />
              <span v-if="errors.postalCode" class="error-message">{{ errors.postalCode }}</span>
            </div>

            <div class="submit-col">
              <button class="btn" type="submit" :disabled="paymentLoading">
                {{ paymentLoading ? 'Feldolgozás...' : '✅ Foglalás megerősítése' }}
              </button>
            </div>
          </form>
        </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
  --accent: #4A7434;
  --cta: #F17E21;
  --bg-grad-start: #f7faf7;
  --bg-grad-end: #f1f5f7;
  --card-bg: #ffffff;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page-payment {
  background: #4A7434;
  min-height: 100vh;
  padding: 2rem 0;
}

.hero {
  padding: 2rem 0;
}

.container {
  width: min(1100px, 92%);
  margin: 0 auto;
}

.hero .title {
  text-align: center;
  margin-bottom: 2rem;
}

.hero h1 {
  font-size: clamp(1.6rem, 3.5vw, 2.5rem);
  font-weight: 700;
  margin-bottom: 0.5rem;
  line-height: 1.05;
  color: white;
}

.hero p.lead {
  font-size: 1.05rem;
  color: rgba(255, 255, 255, 0.9);
}

.payment-card {
  background-color: #fff;
  color: black;
  border-radius: 1rem;
  padding: 1.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
}

/* Kártyatípus választó */
.card-type-selector {
  margin-bottom: 1rem;
}

.card-type-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-select {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #829dd4;
  border-radius: 0.625rem;
  outline: none;
  font-size: 0.95rem;
  background-color: white;
  cursor: pointer;
  transition: box-shadow 0.15s, border-color 0.15s;
}

.card-select:focus {
  box-shadow: 0 0 0 4px rgba(74, 116, 52, 0.12);
  border-color: var(--accent);
}

.card-select:hover {
  border-color: var(--accent);
}

.card-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 80px;
  height: 50px;
  padding: 0.5rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
}

.logo-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.amex-text {
  font-weight: 700;
  font-size: 1.2rem;
  color: #006FCF;
}

form.grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 0.75rem;
  align-items: end;
}

.section-header {
  grid-column: 1 / -1;
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--accent);
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.section-header:first-of-type {
  margin-top: 0;
}

.full-width {
  grid-column: 1 / -1;
}

label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: #374151;
}

input[type="text"] {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #829dd4;
  border-radius: 0.625rem;
  outline: none;
  font-size: 0.95rem;
  transition: box-shadow 0.15s, border-color 0.15s;
}

input:focus {
  box-shadow: 0 0 0 4px rgba(74, 116, 52, 0.12);
  border-color: var(--accent);
}

input[type="text"].error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.btn {
  background-color: #4A7434;
  color: #fff;
  padding: 0.9rem 2rem;
  border-radius: 0.75rem;
  border: none;
  cursor: pointer;
  font-weight: 700;
  font-size: 1rem;
  box-shadow: 0 8px 18px rgba(241, 126, 33, 0.22);
  transition: background 0.25s, box-shadow 0.25s, transform 0.08s;
}

.btn:hover {
  background: #F17E21;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.18);
}

.btn:active {
  transform: translateY(1px);
}

.submit-col {
  grid-column: 1 / -1;
  display: flex;
  justify-content: center;
  margin-top: 1.5rem;
}

@media (min-width: 720px) {
  form.grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  form.grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .submit-col .btn {
    padding: 0.9rem 3.5rem;
    font-size: 1.05rem;
  }
}

.booking-summary {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 0.75rem;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}
.booking-summary h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #166534;
  margin-bottom: 0.75rem;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.35rem 0;
  font-size: 0.95rem;
  color: #555;
}
.summary-row.total {
  border-top: 1px solid #bbf7d0;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  font-weight: 700;
  color: #166534;
  font-size: 1.05rem;
}
.summary-value {
  font-weight: 600;
}

/* Sikeres foglalás */
.success-card {
  background: #fff;
  border-radius: 1rem;
  padding: 2.5rem 2rem;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  max-width: 560px;
  margin: 0 auto;
}

.success-icon {
  width: 80px;
  height: 80px;
  background: #22c55e;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  margin: 0 auto 1.5rem;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.success-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #166534;
  margin-bottom: 0.5rem;
}

.success-text {
  color: #555;
  font-size: 1rem;
  margin-bottom: 2rem;
}

.success-details {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 0.75rem;
  padding: 1.25rem 1.5rem;
  margin-bottom: 2rem;
  text-align: left;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.4rem 0;
  font-size: 0.95rem;
}

.detail-label {
  color: #6b7280;
}

.detail-value {
  font-weight: 600;
  color: #1f2937;
}

.detail-row.total {
  border-top: 1px solid #bbf7d0;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  font-weight: 700;
}

.detail-row.total .detail-label,
.detail-row.total .detail-value {
  color: #166534;
  font-size: 1.05rem;
}

.success-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary {
  background: #4A7434;
  color: #fff;
  padding: 0.75rem 1.75rem;
  border-radius: 0.625rem;
  border: none;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #3d6129;
}

.btn-outline {
  background: transparent;
  color: #4A7434;
  padding: 0.75rem 1.75rem;
  border-radius: 0.625rem;
  border: 2px solid #4A7434;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}

.btn-outline:hover {
  background: #4A7434;
  color: #fff;
}
</style>