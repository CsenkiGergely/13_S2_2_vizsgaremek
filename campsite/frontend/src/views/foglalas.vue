<script setup>
import { ref } from 'vue'

// Galéria képei
const images = [
  '/img/hely1.jpg',
  '/img/hely2.jpg',
  '/img/hely3.jpg'
]
const currentImage = ref(0)

const nextImage = () => {
  currentImage.value = (currentImage.value + 1) % images.length
}

const prevImage = () => {
  currentImage.value = (currentImage.value - 1 + images.length) % images.length
}

// Például szolgáltatások / tagok
const features = [
  { name: 'Étterem', icon: '🍽️' },
  { name: 'Parkoló', icon: '🅿️' },
  { name: 'Wifi', icon: '📶' },
  { name: 'Medence', icon: '🏊‍♂️' },
]
</script>

<template>
  <div class="container mx-auto px-4 py-6 grid md:grid-cols-3 gap-6">
    
    <!-- Bal / középső rész -->
    <div class="md:col-span-2 space-y-6">
      <!-- Képgaléria -->
      <div class="relative rounded-xl overflow-hidden shadow-lg">
        <img :src="images[currentImage]" alt="Helyszín képe" class="w-full h-80 object-cover"/>
        <button @click="prevImage" class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition">‹</button>
        <button @click="nextImage" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/70 rounded-full p-2 hover:bg-white transition">›</button>
      </div>

      <!-- Tulajdonságok / tagok -->
      <div class="flex flex-wrap gap-4">
        <div v-for="feature in features" :key="feature.name" class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-lg shadow-sm">
          <span class="text-xl">{{ feature.icon }}</span>
          <span class="font-medium">{{ feature.name }}</span>
        </div>
      </div>

      <!-- Leírás -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h2 class="text-2xl font-semibold mb-4">Helyszín leírása</h2>
        <p class="text-gray-700">
          Ez a kemping ideális családok és barátok számára. Központi elhelyezkedés, rengeteg zöld terület, medence, étterem és teljes felszereltség várja a vendégeket. Tökéletes hely a pihenéshez és szórakozáshoz.
        </p>
      </div>
    </div>

    <!-- Jobb oldali rész -->
    <div class="space-y-6">
      <!-- Helyszín részletes infó -->
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

      <!-- Kis térkép -->
      <div class="bg-white p-6 rounded-xl shadow border">
        <h3 class="text-xl font-semibold mb-2">Térkép</h3>
        <div class="w-full h-48 rounded-lg overflow-hidden">
          <iframe 
            class="w-full h-full"
            src="https://maps.google.com/maps?q=Budapest%20Példa%20utca%205&t=&z=15&ie=UTF8&iwloc=&output=embed" 
            frameborder="0" 
            allowfullscreen>
          </iframe>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Ha szeretnéd a gombokat a képgalérián nagyobbá és átlátszóbbá tenni */
button {
  font-size: 1.5rem;
}
</style>
