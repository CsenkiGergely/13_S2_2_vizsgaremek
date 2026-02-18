<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const searchQuery = ref('')
const priceRange = ref(100)
const selectedLocationTypes = ref([])
const selectedServices = ref([])
const minRating = ref(null)

const searchResults = ref([])
const loading = ref(false)
const error = ref(null)

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
    
    const response = await axios.get('http://localhost:8000/api/campings', { params })
    
    // Backend adatok transzformálása a frontend formátumra
    const rawData = response.data.data || response.data
    searchResults.value = rawData.map(camping => ({
      id: camping.id,
      name: camping.camping_name,
      image: camping.photos && camping.photos.length > 0 ? camping.photos[0].photo_url : 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800',
      rating: camping.average_rating || 0,
      reviews: camping.reviews_count || 0,
      location: camping.location ? camping.location.city : 'Ismeretlen',
      tags: camping.tags ? camping.tags.map(t => ({ id: t.id, name: t.tag })) : [],
      price: camping.min_price || 0,
      photos: camping.photos || [],
      is_featured: false,
      available_capacity: camping.spots ? camping.spots.reduce((sum, spot) => sum + spot.capacity, 0) : 0,
      available_spots_count: camping.spots ? camping.spots.length : 0
    }))
  } catch (err) {
    console.error('Hiba a kempingek betöltésekor:', err)
    error.value = 'Nem sikerült betölteni a kempingeket. Ellenőrizd, hogy a backend fut-e!'
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Betöltjük a kempingeket az oldal betöltésekor
  fetchCampsites()
  
  // Query paraméterek beolvasása (ha vannak)
  if (route.query.location) {
    searchQuery.value = route.query.location
  }
})

const minCheckOut = computed(() => {
  return new Date()
})

const incrementAdults = () => {
  // Ha van searchForm.value.adults, növeljük
}

const decrementAdults = () => {
  // Ha van searchForm.value.adults, csökkentjük
}

const incrementChildren = () => {
  // Ha van searchForm.value.children, növeljük
}

const decrementChildren = () => {
  // Ha van searchForm.value.children, csökkentjük
}
</script>

<script>
export default {
  methods: {
    goToSearch() {
      this.$router.push('/fizetes')
    }
  }
}
</script>
<template>


<div class="container">

    <aside class="sidebar">
        <h2>Ár éjszakánként</h2>
<input type="range" min="0" max="100" value="50" id="slider">
<p>5 000 Ft</p> <p class="line">25 000 Ft</p>


<h3>Helyszín típusa</h3>
<label><input type="radio" name="helyszin"> 🌅Tóparti</label>
<label><input type="radio" name="helyszin"> 🏔️Hegyi</label>
<label><input type="radio" name="helyszin"> 🏕️Erdei</label>
<label><input type="radio" name="helyszin"> 🏜️Sivatagi</label>
<label><input type="radio" name="helyszin"> 🏞️Tengerparti</label>

        <h3>Szolgáltatások</h3>
        <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/wifi-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Wifi</label>
</div>
        <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/tent-9-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Sátorhelyek</label>
</div>
        <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/mountain-outlined-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Túraútvonalak</label>
</div>
       <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/fork-knife-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Étterem</label>
</div>
       <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/car-side-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Lakókocsi csatlakozó</label>
</div>

       <div class="sor">
  <input type="checkbox" id="opcio1">
  <img src="/img/car-side-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1">Parkoló</label>
</div>

<h3>Minimum értékelés</h3>
<label><input type="radio" name="ertekeles"> 4.5+⭐</label>
<label><input type="radio" name="ertekeles"> 4.0+⭐</label>
<label><input type="radio" name="ertekeles"> 3.5+⭐</label>
<label><input type="radio" name="ertekeles"> 3.0+⭐</label>
<label><input type="radio" name="ertekeles"> 2.5+⭐</label>
<label><input type="radio" name="ertekeles"> 2.0+⭐</label>

        <button class="reset">Szűrők törlése</button>
        <button class="apply">Szűrők alkalmazása</button>
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
        </div>
        
        <!-- Találatok -->
        <div v-else class="cards">
            <div class="card" v-for="camping in searchResults" :key="camping.id">
                <img 
                    :src="camping.photos && camping.photos.length > 0 
                        ? camping.photos[0].url 
                        : 'https://picsum.photos/600/400?camp'" 
                    :alt="camping.name"
                />
                <div class="card-body">
                    <span class="badge" v-if="camping.is_featured">Kiemelt</span>
                    <h4>{{ camping.name }}</h4>
                    <div class="rating" v-if="camping.average_rating">
                        ⭐ {{ camping.average_rating }} ({{ camping.reviews_count || 0 }})
                    </div>
                    <div class="location" v-if="camping.location">
                        📍 {{ camping.location.city }}
                    </div>
                    <div class="tags" v-if="camping.tags && camping.tags.length > 0">
                        <span v-for="tag in camping.tags.slice(0, 4)" :key="tag.id">
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
                            {{ camping.min_price ? camping.min_price.toLocaleString() : '0' }} Ft / éjszaka
                        </div>
                        <router-link to="/foglalas">
                            <button class="book">Foglalás</button>
                        </router-link>
                    </div>
                </div>
            </div>
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
    /* Opció: ha a sidebar-t jobbra szeretnéd tolni a bal üres tér miatt */
    margin-left: 0; 
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