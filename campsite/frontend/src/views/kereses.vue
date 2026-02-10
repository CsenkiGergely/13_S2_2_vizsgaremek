<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const allCampsites = ref([
  { id: 1, name: 'Balatoni Tóparti Kemping', location: 'Balaton, Siófok', rating: 4.8, reviews: 124, price: 12000, image: 'https://picsum.photos/600/400?camp', tags: ['WiFi', 'Parkoló', 'Sátorhely', 'Étterem'], featured: true },
  { id: 2, name: 'Mátra Vista Lakókocsi Park', location: 'Mátra, Gyöngyös', rating: 4.9, reviews: 89, price: 18500, image: 'https://picsum.photos/600/400?mountain', tags: ['WiFi', 'Parkoló', 'Étterem'], featured: true },
  { id: 3, name: 'Őrségi Erdei Kemping', location: 'Őrség, Szalafő', rating: 4.7, reviews: 156, price: 8500, image: 'https://picsum.photos/600/400?forest', tags: ['Parkoló', 'Sátorhely'], featured: true },
  { id: 4, name: 'Budapesti Városi Kemping', location: 'Budapest, Zugló', rating: 4.5, reviews: 98, price: 15000, image: 'https://picsum.photos/600/400?city', tags: ['WiFi', 'Parkoló'], featured: false }
])

const searchQuery = ref('')

const filteredCampsites = computed(() => {
  if (!searchQuery.value) return allCampsites.value
  const q = searchQuery.value.toLowerCase()
  return allCampsites.value.filter(c => 
    c.name.toLowerCase().includes(q) || c.location.toLowerCase().includes(q)
  )
})

onMounted(() => {
  if (route.query.location) {
    searchQuery.value = route.query.location
  }
})
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
<label><input type="checkbox" name="helyszin"> 🌅Tóparti</label>
<label><input type="checkbox" name="helyszin"> 🏔️Hegyi</label>
<label><input type="checkbox" name="helyszin"> 🏕️Erdei</label>
<label><input type="checkbox" name="helyszin"> 🏜️Sivatagi</label>
<label><input type="checkbox" name="helyszin"> 🏞️Tengerparti</label>

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
    <div class="search-header" style="margin-bottom:20px">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Keresés (pl. Budapest, Balaton...)" 
        style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc"
      />
    </div>

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
      Nincs találat a keresésre: <strong>{{ searchQuery }}</strong>
    </div>

    <div class="view-all">
      <button>Összes kemping megtekintése</button>
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