<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'


const router = useRouter()

const route = useRoute()
const today = new Date().toISOString().split('T')[0]

const searchForm = ref({
  location: '',
  checkIn: '',
  checkOut: '',
  adults: 2,
  children: 0
})

const campings = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const totalResults = ref(0)

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

const searchCampings = async (page = 1) => {
  loading.value = true
  error.value = null
  
  try {
    const params = {
      page: page
    }
    
    if (searchForm.value.location) params.location = searchForm.value.location
    if (searchForm.value.checkIn) params.arrival_date = searchForm.value.checkIn
    if (searchForm.value.checkOut) params.departure_date = searchForm.value.checkOut
    
    const totalGuests = searchForm.value.adults + searchForm.value.children
    if (totalGuests > 0) params.guests = totalGuests
    
    const response = await api.get('/booking/search', { params })
    
    campings.value = response.data.data || []
    currentPage.value = response.data.current_page || 1
    totalPages.value = response.data.last_page || 1
    totalResults.value = response.data.total || 0
    
    console.log('Kempingek:', campings.value)
  } catch (err) {
    error.value = err.response?.data?.message || 'Hiba történt a keresés során'
    console.error('Keresési hiba:', err)
  } finally {
    loading.value = false
  }
}

const goToBooking = (campingId) => {
  router.push({
    path: '/fizetes',
    query: {
      camping_id: campingId,
      arrival_date: searchForm.value.checkIn,
      departure_date: searchForm.value.checkOut,
      guests: searchForm.value.adults + searchForm.value.children
    }
  })
}

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    searchCampings(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

onMounted(() => {
  // Query paraméterek beolvasása
  if (route.query.location) searchForm.value.location = route.query.location
  if (route.query.arrival_date) searchForm.value.checkIn = route.query.arrival_date
  if (route.query.departure_date) searchForm.value.checkOut = route.query.departure_date
  if (route.query.guests) {
    const guests = parseInt(route.query.guests)
    searchForm.value.adults = guests || 2
  }
  
  // Ha vannak keresési paraméterek, keresés indítása
  if (searchForm.value.checkIn && searchForm.value.checkOut) {
    searchCampings()
  }
})
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

        <button class="reset">Szűrők törlése</button>
        <button class="apply">Szűrők alkalmazása</button>
    </aside>

    <main class="content">
        <!-- Loading state -->
        <div v-if="loading" class="loading-state">
            <p>⏳ Kempingek keresése...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="error-state">
            <p>❌ {{ error }}</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="campings.length === 0" class="empty-state">
            <p>🏕️ Nincs találat a megadott keresési feltételekkel</p>
            <p class="muted-text">Próbáld meg módosítani a dátumokat vagy helyszínt</p>
        </div>

        <!-- Results -->
        <div v-else>
            <div class="results-info">
                <p>{{ totalResults }} kemping találat</p>
            </div>

            <div class="cards">
                <div v-for="camping in campings" :key="camping.id" class="card">
                    <img 
                        :src="camping.photos && camping.photos[0] ? camping.photos[0].photo_url : 'https://picsum.photos/600/400?camping'"
                        :alt="camping.name"
                    >
                    <div class="card-body">
                        <span v-if="camping.is_featured" class="badge">Kiemelt</span>
                        <h4>{{ camping.name }}</h4>
                        <div class="rating">
                            ⭐ {{ camping.average_rating ? camping.average_rating.toFixed(1) : 'N/A' }}
                            <span v-if="camping.reviews_count">({{ camping.reviews_count }})</span>
                        </div>
                        <div class="location">
                            📍 {{ camping.location?.city || '' }}, {{ camping.location?.county || '' }}
                        </div>
                        <div class="tags">
                            <span v-for="tag in camping.tags?.slice(0, 4)" :key="tag.id">
                                {{ tag.name }}
                            </span>
                        </div>
                        <div class="capacity-info">
                            <span>👥 {{ camping.available_capacity }} fő</span>
                            <span>🏕️ {{ camping.available_spots_count }} hely</span>
                        </div>
                        <div class="price-row">
                            <div class="price">
                                {{ camping.min_price?.toLocaleString() }} - {{ camping.max_price?.toLocaleString() }} Ft / éjszaka
                            </div>
                            <button class="book" @click="goToBooking(camping.id)">Foglalás</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination">
                <button 
                    @click="changePage(currentPage - 1)" 
                    :disabled="currentPage === 1"
                    class="page-btn"
                >
                    ← Előző
                </button>
                <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
                <button 
                    @click="changePage(currentPage + 1)" 
                    :disabled="currentPage === totalPages"
                    class="page-btn"
                >
                    Következő →
                </button>
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

        /* Loading, Error, Empty states */
        .loading-state, .error-state, .empty-state {
            text-align: center;
            padding: 60px 20px;
            font-size: 18px;
        }

        .error-state {
            color: #d32f2f;
        }

        .muted-text {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .results-info {
            margin-bottom: 20px;
            font-weight: 600;
            color: #2f7d32;
        }

        .capacity-info {
            font-size: 13px;
            color: #666;
            margin: 10px 0;
            display: flex;
            gap: 15px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 40px;
            padding: 20px;
        }

        .page-btn {
            padding: 10px 20px;
            background: #2f7d32;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .page-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .page-info {
            font-weight: 600;
            font-size: 16px;
        }
</style>