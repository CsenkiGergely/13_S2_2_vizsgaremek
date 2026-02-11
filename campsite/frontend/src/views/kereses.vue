<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const allCampsites = ref([])
const loading = ref(false)
const error = ref(null)

const searchQuery = ref('')
const priceRange = ref(100)
const selectedLocationTypes = ref([])
const selectedServices = ref([])
const minRating = ref(null)

// Debounce timer a kereséshez
let debounceTimer = null

// API hívás a kempingek betöltéséhez
const fetchCampsites = async () => {
  loading.value = true
  error.value = null
  
  try {
    const params = {}
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    const maxPrice = priceRange.value * 300
    params.max_price = maxPrice
    
    if (minRating.value !== null) {
      params.min_rating = minRating.value
    }
    
    if (selectedLocationTypes.value.length > 0) {
      params.location_types = selectedLocationTypes.value.join(',')
    }
    
    if (selectedServices.value.length > 0) {
      params.services = selectedServices.value.join(',')
    }
    
    const response = await axios.get('http://localhost:8000/api/campsites', { params })
    allCampsites.value = response.data
  } catch (err) {
    console.error('Hiba a kempingek betöltésekor:', err)
    error.value = 'Nem sikerült betölteni a kempingeket. Próbáld újra később.'
  } finally {
    loading.value = false
  }
}

const filteredCampsites = computed(() => allCampsites.value)

const resetFilters = () => {
  searchQuery.value = ''
  priceRange.value = 100
  selectedLocationTypes.value = []
  selectedServices.value = []
  minRating.value = null
  fetchCampsites()
}

// Debounced keresés - csak a searchQuery-nél
watch(searchQuery, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchCampsites()
  }, 500) // 500ms késleltetés
})

// Többi szűrő azonnal hívja az API-t
watch([priceRange, selectedLocationTypes, selectedServices, minRating], () => {
  fetchCampsites()
}, { deep: true })

onMounted(() => {
  if (route.query.location) {
    searchQuery.value = route.query.location
  }
  fetchCampsites()
})
</script>
<template>
<div class="page-container">
  <!-- Hero keresési blokk (ugyanaz mint a Home oldalon) -->
  <div class="hero-search">
    <div class="search-container">
      <div class="search-title">
        <h1>Találd meg a tökéletes kempinget</h1>
        <p class="lead">Fedezd fel a legjobb kempinghelyeket Magyarországon</p>
      </div>

      <div class="search-card">
        <form class="grid" @submit.prevent="fetchCampsites">
          <div class="location-col">
            <label for="location">📍 Helyszín</label>
            <input id="location" v-model="searchQuery" type="text" placeholder="Pl. Balaton, Budapest..." />
          </div>
          <div>
            <label for="checkIn">📅 Érkezés</label>
            <input id="checkIn" type="date" />
          </div>
          <div>
            <label for="checkOut">📅 Távozás</label>
            <input id="checkOut" type="date" />
          </div>
          <div>
            <label for="guests">👥 Vendégek</label>
            <input id="guests" type="number" min="1" value="1" />
          </div>
          <div class="submit-col" style="margin-top:.5rem">
            <button class="btn" type="submit">🔍 Keresés</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Szűrők és eredmények -->
<div class="container">
  <aside class="sidebar">
    <h2>Ár éjszakánként</h2>
    <input type="range" min="0" max="100" v-model="priceRange" id="slider">
    <p>0 Ft</p> <p class="line">{{ (priceRange * 300).toLocaleString() }} Ft</p>

    <h3>Helyszín típusa</h3>
    <label><input type="checkbox" value="Tóparti" v-model="selectedLocationTypes"> 🌅 Tóparti</label>
    <label><input type="checkbox" value="Hegyi" v-model="selectedLocationTypes"> 🏔️ Hegyi</label>
    <label><input type="checkbox" value="Erdei" v-model="selectedLocationTypes"> 🏕️ Erdei</label>
    <label><input type="checkbox" value="Sivatagi" v-model="selectedLocationTypes"> 🏜️ Sivatagi</label>
    <label><input type="checkbox" value="Tengerparti" v-model="selectedLocationTypes"> 🏞️ Tengerparti</label>

    <h3>Szolgáltatások</h3>
    <div class="sor">
      <input type="checkbox" id="wifi" value="WiFi" v-model="selectedServices">
      <img src="/img/wifi-svgrepo-com.svg" alt="WiFi" class="kicsi-kep">
      <label for="wifi">WiFi</label>
    </div>
    <div class="sor">
      <input type="checkbox" id="sator" value="Sátorhely" v-model="selectedServices">
      <img src="/img/tent-9-svgrepo-com.svg" alt="Sátorhely" class="kicsi-kep">
      <label for="sator">Sátorhely</label>
    </div>
    <div class="sor">
      <input type="checkbox" id="tura" value="Túraútvonalak" v-model="selectedServices">
      <img src="/img/mountain-outlined-svgrepo-com.svg" alt="Túraútvonalak" class="kicsi-kep">
      <label for="tura">Túraútvonalak</label>
    </div>
    <div class="sor">
      <input type="checkbox" id="etterem" value="Étterem" v-model="selectedServices">
      <img src="/img/fork-knife-svgrepo-com.svg" alt="Étterem" class="kicsi-kep">
      <label for="etterem">Étterem</label>
    </div>
    <div class="sor">
      <input type="checkbox" id="lakokocsi" value="Lakókocsi csatlakozó" v-model="selectedServices">
      <img src="/img/car-side-svgrepo-com.svg" alt="Lakókocsi" class="kicsi-kep">
      <label for="lakokocsi">Lakókocsi csatlakozó</label>
    </div>
    <div class="sor">
      <input type="checkbox" id="parkolo" value="Parkoló" v-model="selectedServices">
      <img src="/img/car-side-svgrepo-com.svg" alt="Parkoló" class="kicsi-kep">
      <label for="parkolo">Parkoló</label>
    </div>

    <h3>Minimum értékelés</h3>
    <label><input type="radio" name="ertekeles" :value="4.5" v-model="minRating"> 4.5+⭐</label>
    <label><input type="radio" name="ertekeles" :value="4.0" v-model="minRating"> 4.0+⭐</label>
    <label><input type="radio" name="ertekeles" :value="3.5" v-model="minRating"> 3.5+⭐</label>
    <label><input type="radio" name="ertekeles" :value="3.0" v-model="minRating"> 3.0+⭐</label>
    <label><input type="radio" name="ertekeles" :value="2.5" v-model="minRating"> 2.5+⭐</label>
    <label><input type="radio" name="ertekeles" :value="2.0" v-model="minRating"> 2.0+⭐</label>

    <button class="reset" @click="resetFilters">Szűrők törlése</button>
  </aside>

<main class="content">
    <div v-if="loading" style="text-align:center;margin-top:40px;color:#666">
      Töltés...
    </div>

    <div v-else-if="error" style="text-align:center;margin-top:40px;color:#d32f2f">
      {{ error }}
    </div>

    <template v-else>
      <div class="cards">
        <div v-for="camp in filteredCampsites" :key="camp.id" class="card">
          <img :src="camp.image" :alt="camp.name">
          <div class="card-body">
            <span v-if="camp.featured" class="badge">Kiemelt</span>
            <h4>{{ camp.name }}</h4>
            <div class="rating">⭐ {{ camp.rating }} ({{ camp.reviews }})</div>
            <div class="location">📍 {{ camp.location }}</div>
            <div class="tags">
              <span v-for="tag in camp.tags" :key="tag">{{ tag }}</span>
            </div>
            <div class="price-row">
              <div class="price">{{ camp.price.toLocaleString() }} Ft / éjszaka</div>
              <router-link to="/foglalas">
                <button class="book">Foglalás</button>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredCampsites.length === 0" style="text-align:center;margin-top:40px;color:#999">
        Nincs találat a keresésre
      </div>

      <div class="view-all">
        <button>Összes kemping megtekintése</button>
      </div>
    </template>
  </main>
</div>
</div>
</template>

<style scoped>
 * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

/* Hero keresési blokk stílusok */
.page-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.hero-search {
  background: linear-gradient(135deg, #4A7434 0%, #2f7d32 100%);
  padding: 60px 20px;
  color: white;
}

.search-container {
  max-width: 1200px;
  margin: 0 auto;
}

.search-title h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  font-weight: 700;
}

.search-title .lead {
  font-size: 1.1rem;
  opacity: 0.95;
  margin-bottom: 2rem;
}

.search-card {
  background: white;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.search-card .grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  align-items: end;
}

.search-card label {
  display: block;
  color: #333;
  font-weight: 600;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.search-card input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
  color: #333;
  background: white;
}

.search-card input::placeholder {
  color: #999;
}

.search-card input:focus {
  outline: none;
  border-color: #4A7434;
}

.search-card .btn {
  background: #4A7434;
  color: white;
  padding: 12px 32px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
  width: 100%;
}

.search-card .btn:hover {
  background: #F17E21;
}

.sor {
  display: flex;          /* elemek egy sorban */
  align-items: center;    /* függőlegesen középre igazítja */
  gap: 10px;              /* távolság az elemek között */
}

.kicsi-kep {
  width: 15px;   /* kicsinyített kép */
  height: auto;  /* arány megtartása */
}


        body {
            margin: 0;
            background: #f6f7f8;
        }

        header {
            background: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        header h1 {
            color: #2f7d32;
            font-size: 22px;
        }

        header .actions button {
            margin-left: 10px;
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .login {
            background: #e0e0e0;
        }

        .register {
            background: #2f7d32;
            color: white;
        }

.container {
  display: flex;
  flex-direction: column; /* mobil: szűrő felül, tartalom alatta */
  gap: 20px;
  padding: 20px;
  padding-left: 20px; /* mobilon kis bal tér */
  padding-right: 20px;
}

@media (min-width: 768px) { /* tablet-től nagyobb képernyő */
  .container {
    flex-direction: row; /* desktop: szűrő balra, tartalom jobbra */
    padding-left: 150px; /* nagyobb bal tér */
    padding-right: 20px;
  }
}

@media (min-width: 1200px) { /* nagy monitor */
  .container {
    padding-left: 250px; /* extra nagy bal tér */
  }
}



        
.sidebar {
    width: 260px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-left: 0;
    position: sticky;
    top: 20px;
    align-self: flex-start;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

        .sidebar h3 {
            margin-top: 20px;
            font-size: 15px;
        }

        .sidebar label {
            display: block;
            margin: 6px 0;
            font-size: 14px;
        }

        .sidebar button {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .apply {
            background: #2f7d32;
            color: white;
        }

        .reset {
            background: #eee;
        }

        

.content {
    flex: 1;
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
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .card-body {
            padding: 15px;
        }

        .badge {
            background: orange;
            color: white;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .card h4 {
            margin: 5px 0;
        }

        .rating {
            color: #f9a825;
            font-size: 14px;
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
        }

        .book {
            background: #2f7d32;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .view-all {
            margin-top: 30px;
            text-align: center;
        }

        .view-all button {
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
        }

        .line{
            margin-left: 150px;
            margin-top: -24px;
        }

          input[type=range] {
    width: 200px;
    accent-color: #4CAF50;
  }
</style>