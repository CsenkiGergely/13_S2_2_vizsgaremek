<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'
import { searchCampings } from '../api/searchService'
import Slider from 'primevue/slider'

const route = useRoute()
const router = useRouter()

// --- Keresési paraméterek (Home.vue-ból érkezhetnek) ---
const searchQuery = ref('')
const checkIn = ref('')
const checkOut = ref('')
const guests = ref(1)

// --- Szűrők ---
const priceMin = ref(0)
const priceMax = ref(50000)
const actualPriceMin = ref(0)   // az eredményekből kiszámolt tényleges min
const actualPriceMax = ref(50000) // az eredményekből kiszámolt tényleges max

// PrimeVue Slider range tömb: [min, max]
const priceRange = computed({
  get: () => [priceMin.value, priceMax.value],
  set: (val) => {
    const newMin = val[0]
    const newMax = val[1]
    // Max nem lehet kisebb mint min
    priceMin.value = Math.min(newMin, newMax)
    priceMax.value = Math.max(newMin, newMax)
  }
})
const selectedTags = ref([])
const minRating = ref(null)
const showAllTags = ref(false)

const TAG_LIMIT = 5
const visibleTags = computed(() =>
  showAllTags.value ? availableTags.value : availableTags.value.slice(0, TAG_LIMIT)
)

// --- Mobile filter drawer ---
const showMobileFilters = ref(false)
const activeFilterCount = computed(() => {
  let count = 0
  if (priceMin.value > actualPriceMin.value || priceMax.value < actualPriceMax.value) count++
  count += selectedTags.value.length
  if (minRating.value) count++
  return count
})

// --- Adatok ---
const allResults = ref([])       // backend-ről jövő nyers eredmények
const searchResults = ref([])    // szűrt + megjelenített eredmények
const availableTags = ref([])    // dinamikus tag lista az eredményekből
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const hasAvailabilitySearch = ref(false)

// Értékelés opciók
const ratingOptions = [4.5, 4.0, 3.0, 2.0]

// --- API hívás ---
const fetchCampsites = async () => {
  loading.value = true
  error.value = null
  
  try {
    let rawData = []

    // Ha van dátum + vendégszám → elérhetőségi keresés (BookingSearchController)
    if (checkIn.value && checkOut.value && guests.value) {
      hasAvailabilitySearch.value = true
      const response = await searchCampings({
        location: searchQuery.value || undefined,
        checkIn: checkIn.value,
        checkOut: checkOut.value,
        guests: guests.value,
        page: currentPage.value
      })
      rawData = response.data || response
      if (response.last_page) {
        totalPages.value = response.last_page
      }
    } else {
      // Egyszerű keresés (SearchController vagy CampingController)
      hasAvailabilitySearch.value = false
      const params = {}
      if (searchQuery.value) {
        params.q = searchQuery.value
      }
      const response = await api.get('/search', { params })
      rawData = response.data.data || response.data
    }

    // Adatok normalizálása egységes frontend formátumra
    allResults.value = (Array.isArray(rawData) ? rawData : []).map(camping => ({
      id: camping.id,
      name: camping.camping_name || camping.name,
      image: getFirstPhoto(camping),
      rating: parseFloat(camping.average_rating) || 0,
      reviews: camping.reviews_count || 0,
      location: camping.location?.city || (typeof camping.location === 'string' ? camping.location : 'Ismeretlen'),
      locationFull: camping.location,
      description: camping.description || '',
      tags: normalizeTags(camping.tags),
      price: camping.min_price || 0,
      maxPrice: camping.max_price || 0,
      photos: camping.photos || [],
      available_capacity: camping.available_capacity || (camping.spots ? camping.spots.reduce((s, sp) => s + sp.capacity, 0) : 0),
      available_spots_count: camping.available_spots_count || (camping.spots ? camping.spots.length : 0)
    }))

    // Dinamikus tag-ek és ár határok kiszámolása az eredményekből
    updateAvailableTags()
    updatePriceBounds()

    applyClientFilters()
  } catch (err) {
    console.error('Hiba a kempingek betöltésekor:', err)
    error.value = 'Nem sikerült betölteni a kempingeket. Ellenőrizd, hogy a backend fut-e!'
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

// Kép URL kinyerése
const getFirstPhoto = (camping) => {
  if (camping.photos && camping.photos.length > 0) {
    return camping.photos[0].photo_url || camping.photos[0].url || camping.photos[0]
  }
  if (camping.image) return camping.image
  return 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800'
}

// Tag-ek normalizálása
const normalizeTags = (tags) => {
  if (!tags) return []
  if (Array.isArray(tags)) {
    return tags.map(t => typeof t === 'string' ? { id: t, name: t } : { id: t.id, name: t.tag || t.name || t })
  }
  return []
}

// Dinamikus tag-ek összegyűjtése
const updateAvailableTags = () => {
  const tagMap = new Map()
  allResults.value.forEach(c => {
    c.tags.forEach(t => {
      const name = t.name
      if (name && !tagMap.has(name)) {
        tagMap.set(name, { name, count: 0 })
      }
      if (name) tagMap.get(name).count++
    })
  })
  availableTags.value = Array.from(tagMap.values()).sort((a, b) => b.count - a.count)
}

// Ár határok kiszámolása az eredményekből
const updatePriceBounds = () => {
  if (allResults.value.length === 0) return
  const prices = allResults.value.map(c => c.price).filter(p => p > 0)
  const maxPrices = allResults.value.map(c => c.maxPrice || c.price).filter(p => p > 0)
  if (prices.length > 0) {
    actualPriceMin.value = Math.min(...prices)
    actualPriceMax.value = Math.max(...maxPrices, ...prices)
    // Slider értékek inicializálása a tényleges tartományra
    priceMin.value = actualPriceMin.value
    priceMax.value = actualPriceMax.value
  }
}

// --- Kliens oldali szűrés ---
const applyClientFilters = () => {
  let filtered = [...allResults.value]

  // Ár szűrés — a kemping min ára (price) összehasonlítása a slider tartománnyal
  filtered = filtered.filter(c => {
    const campMin = c.price || 0
    const campMax = c.maxPrice || campMin
    // Van átfedés a kemping ártartománya és a szűrő tartománya között?
    return campMin <= priceMax.value && campMax >= priceMin.value
  })

  // Tag szűrés
  if (selectedTags.value.length > 0) {
    filtered = filtered.filter(c => {
      const campTagNames = c.tags.map(t => t.name)
      return selectedTags.value.every(tag => campTagNames.includes(tag))
    })
  }

  // Minimum értékelés
  if (minRating.value !== null) {
    filtered = filtered.filter(c => c.rating >= minRating.value)
  }

  searchResults.value = filtered
}

// --- Szűrők alkalmazása ---
const applyFilters = () => {
  applyClientFilters()
}

// --- Szűrők törlése ---
const resetFilters = () => {
  priceMin.value = actualPriceMin.value
  priceMax.value = actualPriceMax.value
  selectedTags.value = []
  minRating.value = null
  applyClientFilters()
}

// --- Lapozás ---
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchCampsites()
  }
}

// --- Tag checkbox toggle ---
const toggleTag = (tagName) => {
  const idx = selectedTags.value.indexOf(tagName)
  if (idx >= 0) {
    selectedTags.value.splice(idx, 1)
  } else {
    selectedTags.value.push(tagName)
  }
}

// --- Ár formázás ---
const formatPrice = (val) => {
  return val.toLocaleString('hu-HU') + ' Ft'
}

// --- Manuális ár input kezelők ---
const onMinInput = (e) => {
  let val = parseInt(e.target.value) || actualPriceMin.value
  val = Math.max(actualPriceMin.value, Math.min(val, priceMax.value))
  // Legközelebbi step-re kerekítés
  val = Math.round(val / 500) * 500
  priceMin.value = val
  e.target.value = val
}
const onMaxInput = (e) => {
  let val = parseInt(e.target.value) || actualPriceMax.value
  val = Math.min(actualPriceMax.value, Math.max(val, priceMin.value))
  val = Math.round(val / 500) * 500
  priceMax.value = val
  e.target.value = val
}

// --- Inicializálás ---
onMounted(() => {
  // Query paraméterek beolvasása a Home.vue-ból
  if (route.query.location) searchQuery.value = route.query.location
  if (route.query.checkIn) checkIn.value = route.query.checkIn
  if (route.query.checkOut) checkOut.value = route.query.checkOut
  if (route.query.guests) guests.value = parseInt(route.query.guests) || 1

  fetchCampsites()
})

// URL query változás figyelése (pl. navigáció ugyanerre az oldalra más paraméterekkel)
watch(() => route.query, (newQuery) => {
  if (newQuery.location !== undefined) searchQuery.value = newQuery.location || ''
  if (newQuery.checkIn) checkIn.value = newQuery.checkIn
  if (newQuery.checkOut) checkOut.value = newQuery.checkOut
  if (newQuery.guests) guests.value = parseInt(newQuery.guests) || 1
  fetchCampsites()
}, { deep: true })
</script>
<template>
<div class="container">
    <!-- Mobile szűrő gomb -->
    <button class="mobile-filter-btn" @click="showMobileFilters = true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
        Szűrők
        <span v-if="activeFilterCount" class="filter-badge">{{ activeFilterCount }}</span>
    </button>

    <!-- Mobile overlay -->
    <div class="filter-overlay" :class="{ active: showMobileFilters }" @click="showMobileFilters = false"></div>

    <aside class="sidebar" :class="{ open: showMobileFilters }">
        <!-- Mobile bezárás gomb -->
        <div class="mobile-filter-header">
            <span>Szűrők</span>
            <button class="close-filters" @click="showMobileFilters = false">&times;</button>
        </div>

        <div class="price-filter-section">
            <h3 class="filter-heading">Költségkeret (éjszakánként)</h3>
            <div class="price-range-labels">
                <div class="price-input-group">
                    <span class="price-label-tag">Min</span>
                    <div class="price-input-wrap">
                        <input
                            type="number"
                            class="price-input"
                            :value="priceMin"
                            :min="actualPriceMin"
                            :max="priceMax"
                            step="500"
                            @change="onMinInput"
                        />
                        <span class="price-input-suffix">Ft</span>
                    </div>
                </div>
                <span class="price-dash">–</span>
                <div class="price-input-group">
                    <span class="price-label-tag">Max</span>
                    <div class="price-input-wrap">
                        <input
                            type="number"
                            class="price-input"
                            :value="priceMax"
                            :min="priceMin"
                            :max="actualPriceMax"
                            step="500"
                            @change="onMaxInput"
                        />
                        <span class="price-input-suffix">Ft</span>
                    </div>
                </div>
            </div>
            <Slider
                v-model="priceRange"
                range
                :min="actualPriceMin"
                :max="actualPriceMax"
                :step="500"
                class="price-slider"
            />
        </div>

        <h3>Szolgáltatások</h3>
        <div v-if="availableTags.length === 0" class="no-tags-hint">
            Nincs elérhető szűrő.
        </div>
        <div :class="['tag-list', { 'tag-list-expanded': showAllTags }]">
            <div class="tag-filter" v-for="tag in visibleTags" :key="tag.name">
                <label class="tag-label">
                    <input 
                        type="checkbox" 
                        :checked="selectedTags.includes(tag.name)"
                        @change="toggleTag(tag.name)"
                    />
                    <span class="tag-name">{{ tag.name }}</span>
                    <span class="tag-count">({{ tag.count }})</span>
                </label>
            </div>
        </div>
        <button
            v-if="availableTags.length > TAG_LIMIT"
            class="show-more-tags"
            @click="showAllTags = !showAllTags"
        >
            {{ showAllTags ? '▲ Kevesebb mutatása' : `▼ Több mutatása (${availableTags.length - TAG_LIMIT})` }}
        </button>

        <h3 class="rating-heading">Minimum értékelés</h3>
        <label v-for="rating in ratingOptions" :key="rating">
            <input 
                type="radio" 
                name="ertekeles" 
                :value="rating" 
                v-model="minRating"
            /> {{ rating }}+ ⭐
        </label>
        <label>
            <input type="radio" name="ertekeles" :value="null" v-model="minRating" /> Bármilyen
        </label>

        <button class="reset" @click="resetFilters">Szűrők törlése</button>
        <button class="apply" @click="applyFilters(); showMobileFilters = false">Szűrők alkalmazása</button>
    </aside>

    <main class="content">
        <!-- Betöltés -->
        <div v-if="loading" class="loading">
            <p>⏳ Keresés folyamatban...</p>
        </div>
        
        <!-- Hiba -->
        <div v-else-if="error" class="error-message">
            <p>{{ error }}</p>
        </div>
        
        <!-- Nincs találat -->
        <div v-else-if="searchResults.length === 0 && !loading" class="no-results">
            <p>😔 Nincs találat a keresési feltételeknek megfelelően.</p>
            <button class="reset" @click="resetFilters" style="margin-top: 1rem;">Szűrők törlése</button>
        </div>
        
        <!-- Eredmények száma + keresési feltételek -->
        <div v-if="!loading && !error && searchResults.length > 0" class="results-header">
            <p class="results-count">{{ searchResults.length }} kemping található</p>
            <p class="results-params-row" v-if="searchQuery || checkIn || checkOut || guests > 1">
                <span class="results-param" v-if="searchQuery">📍 {{ searchQuery }}</span>
                <span class="results-param" v-if="checkIn && checkOut">📅 {{ checkIn }} – {{ checkOut }}</span>
                <span class="results-param" v-if="guests > 1">👥 {{ guests }} vendég</span>
            </p>
        </div>

        <!-- Találatok -->
        <div v-if="!loading && !error" class="cards">
            <div class="card" v-for="camping in searchResults" :key="camping.id">
                <img 
                    :src="camping.image" 
                    :alt="camping.name"
                    @error="$event.target.src = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800'"
                />
                <div class="card-body">
                    <div class="card-header-row">
                        <h4>{{ camping.name }}</h4>
                        <div class="rating" v-if="camping.rating > 0">
                            ⭐ {{ camping.rating.toFixed(1) }} <span class="review-count">({{ camping.reviews }} értékelés)</span>
                        </div>
                    </div>
                    <div class="location" v-if="camping.location">
                        📍 {{ camping.location }}
                    </div>
                    <div class="tags" v-if="camping.tags && camping.tags.length > 0">
                        <span v-for="tag in camping.tags.slice(0, 4)" :key="tag.id || tag.name">
                            {{ tag.name }}
                        </span>
                    </div>
                    <div class="info-row">
                        <div class="capacity">
                            👥 {{ camping.available_capacity }} fő
                        </div>
                        <div class="spots">
                            🏕️ {{ camping.available_spots_count }} hely
                        </div>
                    </div>
                    <div class="price-row">
                        <div class="price">
                            <span class="price-from">Már</span>
                            {{ camping.price ? camping.price.toLocaleString('hu-HU') : '0' }} Ft<span class="price-unit">-tól / éjszaka</span>
                        </div>
                        <router-link :to="'/foglalas/' + camping.id">
                            <button class="book">Foglalás</button>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lapozás (ha van) -->
        <div v-if="totalPages > 1 && hasAvailabilitySearch" class="pagination">
            <button 
                :disabled="currentPage <= 1" 
                @click="goToPage(currentPage - 1)"
            >← Előző</button>
            <span>{{ currentPage }} / {{ totalPages }}</span>
            <button 
                :disabled="currentPage >= totalPages" 
                @click="goToPage(currentPage + 1)"
            >Következő →</button>
        </div>
    </main>
</div>
</template>

<style scoped>
 * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

.sor {
  display: flex;
  align-items: center;
  gap: 10px;
}

.kicsi-kep {
  width: 15px;
  height: auto;
}

        body {
            margin: 0;
            background: #f6f7f8;
        }

.container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
}

@media (min-width: 768px) {
  .container {
    flex-direction: row;
    align-items: flex-start;
    padding-left: 150px;
    padding-right: 20px;
  }
}

@media (min-width: 1200px) {
  .container {
    padding-left: 250px;
  }
}
        
/* Mobile filter button */
.mobile-filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #fff;
    border: 1px solid #2f7d32;
    border-radius: 24px;
    color: #2f7d32;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    position: sticky;
    top: 10px;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    width: fit-content;
}
.mobile-filter-btn:active {
    background: #e8f5e9;
}
.filter-badge {
    background: #2f7d32;
    color: #fff;
    font-size: 12px;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

/* Mobile overlay */
.filter-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 39;
}
.filter-overlay.active {
    display: block;
}

/* Mobile filter header (hidden on desktop) */
.mobile-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 14px;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 14px;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
}
.close-filters {
    background: none;
    border: none;
    font-size: 28px;
    color: #555;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    width: auto;
    margin: 0;
}
.close-filters:hover {
    color: #1a1a1a;
}

/* Sidebar - mobile first: hidden drawer */
.sidebar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 300px;
    max-width: 85vw;
    background: #fff;
    padding: 20px;
    z-index: 40;
    overflow-y: auto;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    box-shadow: 2px 0 12px rgba(0,0,0,0.15);
}
.sidebar.open {
    display: block;
    transform: translateX(0);
}

/* Desktop: sidebar visible normally */
@media (min-width: 768px) {
    .mobile-filter-btn {
        display: none;
    }
    .filter-overlay {
        display: none !important;
    }
    .mobile-filter-header {
        display: none;
    }
    .sidebar {
        display: block;
        position: sticky;
        top: 68px;
        transform: none;
        width: 260px;
        min-width: 260px;
        border-radius: 10px;
        align-self: flex-start;
        box-shadow: none;
        transition: none;
        max-height: calc(100vh - 88px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }
    .sidebar:hover {
        scrollbar-color: #c1c1c1 transparent;
    }
    /* Chromium alapú böngészők */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background-color: transparent;
        border-radius: 10px;
        transition: background-color 0.3s;
    }
    .sidebar:hover::-webkit-scrollbar-thumb {
        background-color: #c1c1c1;
    }
    .sidebar:hover::-webkit-scrollbar-thumb:hover {
        background-color: #a0a0a0;
    }
}

        .sidebar h2 {
            font-size: 17px;
            margin-bottom: 10px;
            color: #2f7d32;
        }

        .sidebar h3 {
            margin-top: 20px;
            font-size: 15px;
        }

        .sidebar label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 6px 0;
            font-size: 14px;
            cursor: pointer;
        }

        .sidebar button.apply,
        .sidebar button.reset {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .apply {
            background: #2f7d32;
            color: white;
        }

        .apply:hover {
            background: #256428;
        }

        .reset {
            background: #eee;
            padding-left: 5px;
            padding-right: 5px;
            border-radius: 6px;
        }

        .reset:hover {
            background: #ddd;
        }

.content {
    flex: 1;
}

        .results-header {
            margin-bottom: 15px;
        }
        .results-count {
            font-size: 15px;
            color: #555;
            font-weight: 600;
            margin: 0 0 4px 0;
        }
        .results-params-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
        }
        .results-param {
            font-weight: 400;
            color: #888;
            font-size: 13px;
        }
        @media (min-width: 768px) {
            .results-header {
                display: flex;
                align-items: baseline;
                gap: 6px;
                margin-bottom: 15px;
            }
            .results-count {
                margin: 0;
            }
            .results-params-row {
                display: contents;
            }
            .results-param {
                margin-left: 2px;
            }
            .results-param::before {
                content: '· ';
            }
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .card-body {
            padding: 15px;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .card h4 {
            margin: 5px 0;
            flex: 1;
            min-width: 0;
        }

        .rating {
            color: #f9a825;
            font-size: 14px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .review-count {
            color: #888;
            font-size: 12px;
        }

        .location {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .tags span {
            display: inline-block;
            background: #eef3ee;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            margin: 3px 3px 0 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #2f7d32;
            line-height: 1.3;
        }
        .price-from {
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-right: 2px;
        }
        .price-unit {
            font-size: 13px;
            font-weight: 400;
            color: #666;
        }

        .loading, .error-message, .no-results {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }

        .error-message {
            color: #d32f2f;
            background: #ffebee;
            border-radius: 8px;
            padding: 20px;
        }

        .info-row {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
            margin: 10px 0;
        }

        .capacity, .spots {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .book {
            background: #2f7d32;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .book:hover {
            background: #256428;
        }

        /* Price filter – Booking.com style */
        .price-filter-section {
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 16px;
        }
        .filter-heading {
            font-size: 14px;
            font-weight: 400;
            color: #1a1a1a;
            margin: 0 0 8px 0;
        }
        .price-range-labels {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 6px;
        }
        .price-dash {
            font-size: 16px;
            color: #888;
            padding-bottom: 6px;
            flex-shrink: 0;
        }
        .price-input-group {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .price-label-tag {
            font-size: 10px;
            font-weight: 400;
            color: #4A7434;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .price-input-wrap {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
            transition: border-color 0.15s;
        }
        .price-input-wrap:focus-within {
            border-color: #4A7434;
            box-shadow: 0 0 0 2px rgba(74,116,52,0.15);
        }
        .price-input {
            width: 100%;
            border: none;
            outline: none;
            padding: 5px 4px 5px 8px;
            font-size: 13px;
            font-weight: 400;
            color: #1a1a1a;
            background: transparent;
            /* Firefox: nyilak elrejtése */
            -moz-appearance: textfield;
            appearance: textfield;
        }
        /* Chrome/Safari: nyilak elrejtése */
        .price-input::-webkit-outer-spin-button,
        .price-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .price-input-suffix {
            font-size: 11px;
            color: #888;
            padding: 0 6px 0 2px;
            white-space: nowrap;
        }
        .price-slider {
            width: 100%;
            margin-top: 8px;
        }
        /* PrimeVue Slider – footer zöld szín, minden állapotban */
        :deep(.p-slider .p-slider-range) {
            background: #4A7434;
        }
        :deep(.p-slider .p-slider-handle),
        :deep(.p-slider .p-slider-handle:hover),
        :deep(.p-slider .p-slider-handle:active),
        :deep(.p-slider .p-slider-handle-active),
        :deep(.p-slider:not(.p-disabled) .p-slider-handle:hover),
        :deep(.p-slider:not(.p-disabled) .p-slider-handle:active),
        :deep(.p-slider:not(.p-disabled) .p-slider-handle-active) {
            background: #4A7434 !important;
            border-color: #4A7434 !important;
            transform: none !important;
            box-shadow: none !important;
        }
        :deep(.p-slider:not(.p-disabled) .p-slider-handle:focus-visible) {
            box-shadow: 0 0 0 0.2rem rgba(74, 116, 52, 0.25) !important;
        }

        /* Tag filters */
        .tag-list {
            overflow: visible;
        }
        .tag-list-expanded {
            overflow: visible;
        }
        .tag-filter {
            margin: 4px 0;
        }
        .tag-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            margin: 0;
        }
        .tag-label input[type=checkbox] {
            accent-color: #2f7d32;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .tag-label input[type=radio] {
            accent-color: #2f7d32;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .tag-name {
            flex: 1;
        }
        .tag-count {
            color: #999;
            font-size: 12px;
        }
        .no-tags-hint {
            font-size: 13px;
            color: #999;
            padding: 4px 0;
        }
        .show-more-tags {
            background: none;
            border: none;
            color: #4A7434;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 0 2px;
            margin-top: 0;
            margin-bottom: 0;
            width: auto;
        }
        .show-more-tags:hover {
            text-decoration: underline;
        }
        /* Minimum értékelés h3 kisebb marginja */
        .sidebar h3.rating-heading {
            margin-top: 10px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
            padding: 15px;
        }
        .pagination button {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            font-weight: 600;
        }
        .pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pagination button:not(:disabled):hover {
            background: #2f7d32;
            color: white;
            border-color: #2f7d32;
        }
        .pagination span {
            font-size: 14px;
            color: #555;
        }
</style>