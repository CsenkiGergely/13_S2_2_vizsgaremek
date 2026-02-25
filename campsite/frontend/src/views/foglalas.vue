<template>
  <div class="container mx-auto px-4 py-6 space-y-6">

    <!-- Képgaléria (teljes szélesség) -->
    <div class="relative rounded-xl overflow-hidden shadow-lg">
      <img :src="images[currentImage]" alt="Helyszín képe" class="w-full h-96 md:h-[500px] object-cover"/>
      
      <button @click="prevImage" 
              class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition text-2xl">
        ‹
      </button>
      <button @click="nextImage" 
              class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition text-2xl">
        ›
      </button>
    </div>

    <!-- Tartalom rész (2 oszlopos layout) -->
    <div class="flex flex-col md:flex-row gap-6">
      <!-- Bal oldali rész - Információk -->
      <div class="md:w-2/3 space-y-6">
        
        <!-- Címsor és értékelés -->
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">Balatoni Tóparti Kemping</h1>
          <div class="flex items-center gap-4 text-sm text-gray-600">
            <div class="flex items-center gap-1">
              <span class="text-yellow-500">⭐</span>
              <span class="font-semibold text-gray-800">4.8</span>
              <span class="text-gray-500">(124 értékelés)</span>
            </div>
            <span class="text-gray-500">📍 8600 Siófok, Szabadstrand út 1.</span>
          </div>
        </div>

        <!-- Feature / szolgáltatások -->
        <div class="flex flex-wrap gap-3">
          <div v-for="feature in features" :key="feature.name" 
               class="flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg text-sm">
            <span>{{ feature.icon }}</span>
            <span class="font-medium text-gray-700">{{ feature.name }}</span>
          </div>
        </div>

        <!-- Szolgáltatások felsorolás -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Szolgáltatások</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">WiFi</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Strand</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Zuhanyzó</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Csónak kölcsönzés</span>
              </div>
            </div>
            <div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Ingyenes parkoló</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Játszótér</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Mosókonyha</span>
              </div>
              <div class="flex items-start gap-2 mb-2">
                <span class="text-green-600">✓</span>
                <span class="text-gray-600">Kerékpár kölcsönzés</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Házirend -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Házirend</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="flex items-center gap-2 mb-3">
                <span class="text-gray-700">⏰</span>
                <div>
                  <p class="font-medium text-gray-800">Bejelentkezés: 14:00-tól</p>
                </div>
              </div>
              <div class="flex items-center gap-2 mb-3">
                <span class="text-gray-700">🏠</span>
                <div>
                  <p class="font-medium text-gray-800">Háziállat engedélyezett (felár ellenében)</p>
                </div>
              </div>
              <div class="flex items-center gap-2 mb-3">
                <span class="text-gray-700">🚭</span>
                <div>
                  <p class="font-medium text-gray-800">Éjszakai csend: 22:00-06:00</p>
                </div>
              </div>
            </div>
            <div>
              <div class="flex items-center gap-2 mb-3">
                <span class="text-gray-700">⏰</span>
                <div>
                  <p class="font-medium text-gray-800">Kijelentkezés: 10:00-ig</p>
                </div>
              </div>
              <div class="flex items-center gap-2 mb-3">
                <span class="text-gray-700">🚬</span>
                <div>
                  <p class="font-medium text-gray-800">Dohányzás csak kijelölt helyen</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Helyszín leírása -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">A szállásról</h2>
          <p class="text-gray-600 leading-relaxed mb-4">
            A Balatoni Tóparti Kemping a Balaton partján, Siófok szívében található, közvetlen vízparti hozzáféréssel. A kemping több mint 50 éves múltra tekint vissza, és folyamatosan megújul a vendégek igényeinek megfelelően.
          </p>
          <p class="text-gray-600 leading-relaxed mb-4">
            A kemping területén megtalálható minden, amire egy tökéletes nyaraláshoz szükségét lehet: homokos strand, játszótér, étterem, büfé, mosdók zuhanyzókkal, mosókonyha és WiFi lefedettség.
          </p>
          <p class="text-gray-600 leading-relaxed">
            A környéken számos látnivaló és program várja a vendégeket: vízi sportok, kerékpározás, túrázás, borkóstolás és kulturális programok.
          </p>
        </div>

        <!-- Szolgáltatások részletesen -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Szolgáltatások</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">WiFi</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Ingyenes parkoló</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Étterem</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Strand</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Játszótér</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Mosókonyha</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Zuhanyzó</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-600">✓</span>
              <span class="text-gray-600">Kerékpár kölcsönzés</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-gray-400">✗</span>
              <span class="text-gray-400 line-through">Áruház</span>
            </div>
          </div>
        </div>

        <!-- Kemping térkép -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Kemping térkép</h2>
          <div class="flex gap-3 mb-3 text-sm">
            <span class="flex items-center gap-1">
              <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> 
              <span class="text-gray-600">Szabad</span>
            </span>
            <span class="flex items-center gap-1">
              <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> 
              <span class="text-gray-600">Foglalt</span>
            </span>
            <span class="flex items-center gap-1">
              <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> 
              <span class="text-gray-600">Kiválasztva</span>
            </span>
          </div>
          <div id="campingMap" class="w-full rounded-lg border border-gray-200" style="height: 400px; z-index: 0;"></div>
          <div v-if="selectedSpot" class="mt-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="font-semibold text-lg text-gray-800">{{ selectedSpot.name }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ selectedSpot.type }} · Max {{ selectedSpot.capacity }} fő · {{ selectedSpot.price }} Ft/éj</p>
          </div>
        </div>

        <!-- Vendég kommentek -->
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Vendég kommentek</h2>
          
          <!-- Értékelés összesítő -->
          <div class="flex items-center gap-2 mb-6">
            <span class="text-yellow-500 text-xl">⭐</span>
            <span class="text-2xl font-bold text-gray-800">4.8</span>
            <span class="text-gray-500">· 124 értékelés</span>
          </div>

          <div class="space-y-4">
            <div class="pb-4 border-b border-gray-200">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-semibold text-gray-600">N</div>
                <div>
                  <p class="font-medium text-gray-800">Nagy Anna</p>
                  <p class="text-xs text-gray-500">2024-08-15</p>
                </div>
              </div>
              <div class="flex items-center gap-1 mb-2">
                <span class="text-yellow-500 text-sm">⭐⭐⭐⭐⭐</span>
                <span class="text-sm font-semibold text-gray-700 ml-1">5</span>
              </div>
              <p class="text-gray-600">Fantasztikus hely! A gyerekek imádták a strandot, mi pedig a nyugodt környezetet.</p>
            </div>
            <div class="pb-4 border-b border-gray-200">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-semibold text-gray-600">S</div>
                <div>
                  <p class="font-medium text-gray-800">Szabó Béla</p>
                  <p class="text-xs text-gray-500">2024-07-22</p>
                </div>
              </div>
              <div class="flex items-center gap-1 mb-2">
                <span class="text-yellow-500 text-sm">⭐⭐⭐⭐</span>
                <span class="text-sm font-semibold text-gray-700 ml-1">4</span>
              </div>
              <p class="text-gray-600">Jó kemping, tiszta mosdók. Az étterem kissé drága volt.</p>
            </div>
            <div class="pb-4 border-b border-gray-200">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-semibold text-gray-600">T</div>
                <div>
                  <p class="font-medium text-gray-800">Tóth Éva</p>
                  <p class="text-xs text-gray-500">2024-06-10</p>
                </div>
              </div>
              <div class="flex items-center gap-1 mb-2">
                <span class="text-yellow-500 text-sm">⭐⭐⭐⭐⭐</span>
                <span class="text-sm font-semibold text-gray-700 ml-1">5</span>
              </div>
              <p class="text-gray-600">Minden tökéletes volt! Vissza fogunk jönni jövőre is.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Jobb oldali rész - Foglalási panel -->
      <div class="md:w-1/3">
        <div class="booking-panel bg-white p-5 rounded-xl shadow-md">
          <div class="mb-4">
            <div class="flex items-baseline gap-2 mb-1">
              <span class="text-2xl font-bold text-gray-800">12 000 Ft</span>
              <span class="text-gray-500">/ éjszaka</span>
            </div>
          </div>

          <!-- Dátum kiválasztó kártyák -->
          <div class="grid grid-cols-2 gap-2 mb-4">
            <div class="border border-gray-300 rounded-lg p-3 cursor-pointer hover:border-gray-400 transition">
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
            <!-- Hónap navigáció -->
            <div class="flex items-center justify-between mb-4">
              <button @click="previousMonth" type="button" class="p-1 hover:bg-gray-100 rounded text-base">
                ‹
              </button>
              <span class="font-semibold text-gray-800">{{ currentMonthName }} {{ currentYear }}</span>
              <button @click="nextMonth" type="button" class="p-1 hover:bg-gray-100 rounded text-base">
                ›
              </button>
            </div>

            <!-- Hét napjai -->
            <div class="grid grid-cols-7 gap-1 mb-2">
              <div v-for="day in ['V', 'H', 'K', 'Sz', 'Cs', 'P', 'Sz']" :key="day" 
                   class="text-center text-xs font-medium text-gray-500 py-1">
                {{ day }}
              </div>
            </div>

            <!-- Naptár napok -->
            <div class="grid grid-cols-7 gap-1">
              <div v-for="date in calendarDays" :key="date.key" 
                   @click="selectDate(date)"
                   :class="[
                     'text-center py-2 text-sm rounded-full cursor-pointer',
                     date.isCurrentMonth ? 'text-gray-700' : 'text-gray-300',
                     date.isDisabled ? 'cursor-not-allowed opacity-30' : '',
                     date.isSelected ? 'bg-[#4A7434] text-white' : '',
                     date.isInRange ? 'bg-[#E8F5E9]' : ''
                   ]"
              >
                {{ date.day }}
              </div>
            </div>
          </div>
            
          <!-- Vendégek -->
          <div class="border border-gray-300 rounded-lg p-3 mb-6">
            <label class="text-xs font-semibold text-gray-600 uppercase">Vendégek</label>
            <select v-model="bookingForm.guests" class="w-full text-sm mt-1 border-0 p-0 focus:ring-0 text-gray-700">
              <option value="1">1 vendég</option>
              <option value="2">2 vendég</option>
              <option value="3">3 vendég</option>
              <option value="4">4 vendég</option>
              <option value="5">5 vendég</option>
              <option value="6">6 vendég</option>
            </select>
          </div>

          <!-- Foglalás gomb -->
          <button 
            @click="handleBooking"
            class="w-full bg-[#8FA889] hover:bg-[#7a9175] text-white font-semibold py-4 rounded-lg transition-colors duration-200"
          >
            Foglalás
          </button>

          <p class="text-center text-xs text-gray-500 mt-3">Még nem kerül felszámításra</p>

          <!-- Összesítő (ha van kiválasztott dátum) -->
          <div v-if="bookingForm.checkIn && bookingForm.checkOut && nightCount > 0" class="mt-6 pt-6 border-t border-gray-200 space-y-2">
            <div class="flex justify-between text-sm text-gray-600">
              <span class="underline">12 000 Ft x {{ nightCount }} éjszaka</span>
              <span>{{ totalPrice.toLocaleString() }} Ft</span>
            </div>
            <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-200 text-gray-800">
              <span>Összesen</span>
              <span>{{ totalPrice.toLocaleString() }} Ft</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Galéria képei (itt lehet a saját képeket betenni)
const images = [
  '/img/Budapest_szallas1.jpg',
  '/img/Budapest_szallas2.jpg',
  '/img/Budapest_szallas3.jpg'
]
const currentImage = ref(0)

const nextImage = () => {
  currentImage.value = (currentImage.value + 1) % images.length
}

const prevImage = () => {
  currentImage.value = (currentImage.value - 1 + images.length) % images.length
}

// Feature / szolgáltatások
const features = [
  { name: 'Étterem', icon: '🍽️' },
  { name: 'Parkoló', icon: '🅿️' },
  { name: 'Wifi', icon: '📶' },
  { name: 'Medence', icon: '🏊‍♂️' },
]

// Foglalási form adatok
const bookingForm = ref({
  checkIn: '',
  checkOut: '',
  guests: '2'
})

// Naptár állapot
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

// Hónap neve
const currentMonthName = computed(() => {
  const months = ['Január', 'Február', 'Március', 'Április', 'Május', 'Június', 
                  'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December']
  return months[currentMonth.value]
})

// Naptár navigáció
const previousMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

// Naptár napok generálása
const calendarDays = computed(() => {
  const days = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  
  // Hétfő-vasárnap rendszer: 0=vasárnap -> 6=szombat átalakítása 0=hétfő -> 6=vasárnap
  let startDay = firstDay.getDay() - 1
  if (startDay < 0) startDay = 6
  
  // Előző hónap napjai
  const prevMonthLastDay = new Date(currentYear.value, currentMonth.value, 0).getDate()
  for (let i = startDay - 1; i >= 0; i--) {
    const day = prevMonthLastDay - i
    const date = new Date(currentYear.value, currentMonth.value - 1, day)
    days.push({
      day,
      date: date.toISOString().split('T')[0],
      isCurrentMonth: false,
      isDisabled: true,
      isSelected: false,
      isInRange: false,
      key: `prev-${day}`
    })
  }
  
  // Aktuális hónap napjai
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(currentYear.value, currentMonth.value, day)
    const dateStr = date.toISOString().split('T')[0]
    const isPast = date < today
    
    const isCheckIn = dateStr === bookingForm.value.checkIn
    const isCheckOut = dateStr === bookingForm.value.checkOut
    const isInRange = bookingForm.value.checkIn && bookingForm.value.checkOut && 
                      dateStr > bookingForm.value.checkIn && dateStr < bookingForm.value.checkOut
    
    days.push({
      day,
      date: dateStr,
      isCurrentMonth: true,
      isDisabled: isPast,
      isSelected: isCheckIn || isCheckOut,
      isInRange: isInRange,
      key: `current-${day}`
    })
  }
  
  // Következő hónap napjai (hogy 42 nap legyen, 6 sor)
  const remainingDays = 42 - days.length
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(currentYear.value, currentMonth.value + 1, day)
    days.push({
      day,
      date: date.toISOString().split('T')[0],
      isCurrentMonth: false,
      isDisabled: true,
      isSelected: false,
      isInRange: false,
      key: `next-${day}`
    })
  }
  
  return days
})

// Dátum kiválasztása
const selectDate = (dateObj) => {
  if (dateObj.isDisabled) return
  
  // Ha nincs check-in vagy már van mindkettő, új check-in
  if (!bookingForm.value.checkIn || (bookingForm.value.checkIn && bookingForm.value.checkOut)) {
    bookingForm.value.checkIn = dateObj.date
    bookingForm.value.checkOut = ''
  }
  // Ha van check-in de nincs check-out
  else if (bookingForm.value.checkIn && !bookingForm.value.checkOut) {
    // Ha a kiválasztott dátum korábbi mint a check-in, az legyen az új check-in
    if (dateObj.date < bookingForm.value.checkIn) {
      bookingForm.value.checkIn = dateObj.date
    } else {
      bookingForm.value.checkOut = dateObj.date
    }
  }
}

// Dátum formázása
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  const month = date.getMonth() + 1
  const day = date.getDate()
  return `${month}. ${day}.`
}

// Mai dátum minimum (nem lehet múltbeli dátumot választani)
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

// Éjszakák száma
const nightCount = computed(() => {
  if (!bookingForm.value.checkIn || !bookingForm.value.checkOut) return 0
  const checkIn = new Date(bookingForm.value.checkIn)
  const checkOut = new Date(bookingForm.value.checkOut)
  const diff = checkOut - checkIn
  const nights = Math.ceil(diff / (1000 * 60 * 60 * 24))
  return nights > 0 ? nights : 0
})

// Teljes ár
const totalPrice = computed(() => {
  return nightCount.value * 12000
})

// Foglalás kezelése
const handleBooking = () => {
  if (!bookingForm.value.checkIn || !bookingForm.value.checkOut) {
    alert('Kérlek válassz érkezési és távozási dátumot!')
    return
  }
  if (nightCount.value <= 0) {
    alert('A távozás dátumának az érkezés után kell lennie!')
    return
  }
  alert(`Foglalás:\n${nightCount.value} éjszaka\n${bookingForm.value.guests} vendég\nÖsszesen: ${totalPrice.value} Ft`)
}

// --- Leaflet térkép ---
const selectedSpot = ref(null)

// Teszt spot adatok (később API-ból jön)
const spots = [
  { id: 1, name: 'A1 - Sátornak', type: 'sátor', capacity: 4, price: 3500, booked: false, coords: [46.52247780, 19.74844711] },
  { id: 2, name: 'A2 - Sátornak', type: 'sátor', capacity: 4, price: 3500, booked: true,  coords: [46.52232919, 19.74841008] },
  { id: 3, name: 'B1 - Lakókocsi', type: 'lakókocsi', capacity: 6, price: 5500, booked: false, coords: [46.52246081, 19.74858287] },
  { id: 4, name: 'B2 - Lakókocsi', type: 'lakókocsi', capacity: 6, price: 5500, booked: false, coords: [46.52229946, 19.74858904] },
  { id: 5, name: 'C1 - Sátornak', type: 'sátor', capacity: 3, price: 3000, booked: false, coords: [46.52240986, 19.74873097] },
  { id: 6, name: 'C2 - Sátornak', type: 'sátor', capacity: 3, price: 3000, booked: true,  coords: [46.52226125, 19.74877417] },
  { id: 7, name: 'D1 - Faház',    type: 'faház',  capacity: 5, price: 12000, booked: false, coords: [46.52238014, 19.74897164] },
  { id: 8, name: 'D2 - Faház',    type: 'faház',  capacity: 5, price: 12000, booked: false, coords: [46.52221879, 19.74895930] },
  { id: 9, name: 'E1 - Ház',      type: 'ház',    capacity: 8, price: 18000, booked: false, coords: [46.52234617, 19.74908889] },
]

// Kemping terület határa (a te poligonoddal)
const campingBoundary = [
  [46.52279056, 19.74542221],
  [46.52266678, 19.74555315],
  [46.52253742, 19.74560845],
  [46.52233197, 19.74571904],
  [46.52157102, 19.74607293],
  [46.52010997, 19.74670329],
  [46.52171952, 19.75202612],
  [46.52345597, 19.75069474],
  [46.52279056, 19.74542221],
]

// Kempingezőhely terület (kisebb poligon)
const campingZone = [
  [46.52314947, 19.74967592],
  [46.52297933, 19.74978190],
  [46.52305127, 19.75005179],
  [46.52321170, 19.74990201],
  [46.52319128, 19.74982712],
  [46.52314947, 19.74967592],
]

onMounted(async () => {
  await nextTick()

  // Térkép létrehozása
  const map = L.map('campingMap', {
    zoomControl: true,
  }).setView([46.5223, 19.7490], 17)

  // OpenStreetMap alaptérkép
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 20,
  }).addTo(map)

  // Kemping terület határa (zöld vonal)
  L.polygon(campingBoundary, {
    color: '#16a34a',
    weight: 2,
    fillColor: '#bbf7d0',
    fillOpacity: 0.2,
  }).addTo(map).bindPopup('Kemping terület')

  // Kempingezőhely zóna (kék)
  L.polygon(campingZone, {
    color: '#2563eb',
    weight: 2,
    fillColor: '#bfdbfe',
    fillOpacity: 0.3,
    dashArray: '5, 5',
  }).addTo(map).bindPopup('Kempingezőhely zóna')

  // Spot markerek
  spots.forEach(spot => {
    const color = spot.booked ? '#ef4444' : '#22c55e'

    const marker = L.circleMarker(spot.coords, {
      radius: 10,
      fillColor: color,
      color: '#fff',
      weight: 2,
      fillOpacity: 0.9,
    }).addTo(map)

    // Tooltip (hoverre megjelenik)
    marker.bindTooltip(spot.name, { direction: 'top', offset: [0, -10] })

    // Kattintásra kiválasztás
    marker.on('click', () => {
      if (spot.booked) {
        selectedSpot.value = null
        return
      }
      selectedSpot.value = spot
    })
  })
})</script>

<style scoped>
/* Galéria gombok */
button {
  font-size: 2rem;
  background-color: rgba(255,255,255,0.7);
  transition: background-color 0.3s;
}
button:hover {
  background-color: rgba(255,255,255,1);
}

/* Container reszponzív elrendezés */
.container > div {
  gap: 1rem;
}

/* Képek és árnyékok */
img {
  border-radius: 0.5rem;
  object-fit: cover;
}

/* Foglalási panel sticky pozíció */
.booking-panel {
  position: sticky;
  top: 90px; /* Header magassága (80px) + kis margó (10px) */
  z-index: 10; /* Alacsonyabb mint a header z-50 */
  max-height: calc(100vh - 100px); /* Ne legyen magasabb mint a viewport */
  overflow-y: auto; /* Ha túl hosszú, scrollozható legyen */
}
</style>
