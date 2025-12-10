<script setup>
import { ref, computed } from 'vue'

const today = new Date().toISOString().split('T')[0]

const searchForm = ref({
  location: '',
  checkIn: '',
  checkOut: '',
  adults: 0,
  children: 0
})

const minCheckOut = computed(() => {
  return searchForm.value.checkIn || today
})

const incrementAdults = () => {
  if (searchForm.value.adults < 10) searchForm.value.adults++
}

const decrementAdults = () => {
  if (searchForm.value.adults > 1) searchForm.value.adults--
}

const incrementChildren = () => {
  if (searchForm.value.children < 10) searchForm.value.children++
}

const decrementChildren = () => {
  if (searchForm.value.children > 0) searchForm.value.children--
}

const handleSearch = () => {
  console.log('Keresés:', searchForm.value)
  // TODO: Keresési logika implementálása
}
</script>

<template>
  <div class="bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- fő szöveg -->
    <div class="relative bg-[#4A7434] text-white py-20">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Találd meg a tökéletes kempinget</h1>
          <p class="text-xl text-gray-100">Fedezd fel a legjobb kempinghelyeket Magyarországon</p>
        </div>

        <!-- város kereső mező -->
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-2xl p-6">
          <form @submit.prevent="handleSearch" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <!-- hely -->
            <div class="lg:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                📍 Helyszín
              </label>
              <input
                v-model="searchForm.location"
                type="text"
                placeholder="Pl. Balaton, Tisza-tó..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#4A7434] focus:border-transparent outline-none transition"
              />
            </div>

            <!-- arrive date -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                📅 Érkezés
              </label>
              <input
                v-model="searchForm.checkIn"
                type="date"
                :min="today"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#4A7434] focus:border-transparent outline-none transition"
              />
            </div>

            <!-- departure date -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                📅 Távozás
              </label>
              <input
                v-model="searchForm.checkOut"
                type="date"
                :min="minCheckOut"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#4A7434] focus:border-transparent outline-none transition"
              />
            </div>

            <!-- guest mező -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                👥 Vendégek
              </label>
              <input
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#4A7434] focus:border-transparent outline-none transition"
              />
            </div>

            <!-- kereső gomb kinézet -->
            <div class="lg:col-span-5 flex justify-center mt-4">
              <button
                type="submit"
                class="px-12 py-3 bg-[#F17E21] text-white font-semibold rounded-lg hover:bg-[#4A7434] transition-all duration-300 shadow-lg hover:shadow-xl"
              >
                🔍 Keresés
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
</style>
