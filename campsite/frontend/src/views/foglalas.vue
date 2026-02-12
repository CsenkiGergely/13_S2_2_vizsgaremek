<template>
  <div class="container mx-auto px-4 py-6 flex flex-col md:flex-row gap-6">

    <!-- Bal/középső rész -->
    <div class="md:flex-2 space-y-6">
      <!-- Képgaléria -->
      <div class="relative rounded-xl overflow-hidden shadow-lg">
        <img :src="images[currentImage]" alt="Helyszín képe" class="w-full h-64 md:h-80 object-cover"/>
        
        <button @click="prevImage" 
                class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition text-2xl">
          ‹
        </button>
        <button @click="nextImage" 
                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition text-2xl">
          ›
        </button>
      </div>

      <!-- Feature / tagok -->
      <div class="flex flex-wrap gap-4">
        <div v-for="feature in features" :key="feature.name" 
             class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-lg shadow-sm text-lg">
          <span>{{ feature.icon }}</span>
          <span class="font-medium">{{ feature.name }}</span>
        </div>
      </div>

      <!-- Helyszín leírása -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h2 class="text-2xl font-semibold mb-4">Helyszín leírása</h2>
        <p class="text-gray-700">
          Ez a kemping ideális családok és barátok számára. Központi elhelyezkedés, rengeteg zöld terület, medence, étterem és teljes felszereltség várja a vendégeket. Tökéletes hely a pihenéshez és szórakozáshoz.
        </p>
      </div>
    </div>

    <!-- Jobb oldali rész -->
    <div class="md:flex-1 space-y-6">
      <!-- Részletes információk -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h3 class="text-xl font-semibold mb-2">Részletes információk</h3>
        <ul class="text-gray-700 space-y-1">
          <li><strong>Cím:</strong> 1234 Budapest, Példa utca 5.</li>
          <li><strong>Kapacitás:</strong> 50 fő</li>
          <li><strong>Nyitvatartás:</strong> 08:00 - 22:00</li>
        </ul>
      </div>

      <!-- Kommentek -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h3 class="text-xl font-semibold mb-2">Vendég kommentek</h3>
        <div class="space-y-4">
          <div class="border-b pb-2">
            <p class="text-gray-800 font-medium">Kovács János</p>
            <p class="text-gray-600 text-sm">Nagyon szép a hely és tiszta minden.</p>
          </div>
          <div class="border-b pb-2">
            <p class="text-gray-800 font-medium">Nagy Anna</p>
            <p class="text-gray-600 text-sm">A medence fantasztikus, és a személyzet nagyon kedves.</p>
          </div>
        </div>
      </div>

      <!-- Kemping térkép Leaflet-tel -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h3 class="text-xl font-semibold mb-2">Kemping térkép</h3>
        <div class="flex gap-2 mb-3 text-sm">
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Szabad</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Foglalt</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Kiválasztva</span>
        </div>
        <div id="campingMap" class="w-full h-80 rounded-lg overflow-hidden border"></div>
        <div v-if="selectedSpot" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
          <p class="font-semibold">{{ selectedSpot.name }}</p>
          <p class="text-sm text-gray-600">{{ selectedSpot.type }} · Max {{ selectedSpot.capacity }} fő · {{ selectedSpot.price }} Ft/éj</p>
          <button class="mt-2 bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 text-sm">
            Foglalás
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
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
</style>
