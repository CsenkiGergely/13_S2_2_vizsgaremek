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
        <h3>Helyszín típusa</h3>
        <label><input type="radio"> Tóparti</label>
        <label><input type="radio"> Hegyi</label>
        <label><input type="radio"> Erdei</label>
        <label><input type="radio"> Sivatagi</label>
        <label><input type="radio"> Tengerparti</label>

        <h3>Szolgáltatások</h3>
        <label><input type="checkbox"> WiFi</label>
        <label><input type="checkbox"> Parkoló</label>
        <label><input type="checkbox"> Étterem</label>
        <label><input type="checkbox"> Sátorhely</label>
        <label><input type="checkbox"> Lakókocsi csatlakozó</label>

        <h3>Minimum értékelés</h3>
        <label><input type="radio"> 4.5+</label>
        <label><input type="radio"> 4.0+</label>
        <label><input type="radio"> 3.5+</label>

        <button class="reset">Szűrők törlése</button>
        <button class="apply">Szűrők alkalmazása</button>
    </aside>

    <main class="content">
        <div class="cards">

            <div class="card">
                <img src="https://picsum.photos/600/400?camp" alt="">
                <div class="card-body">
                    <span class="badge">Kiemelt</span>
                    <h4>Balatoni Tóparti Kemping</h4>
                    <div class="rating">⭐ 4.8 (124)</div>
                    <div class="location">📍 Balaton, Siófok</div>
                    <div class="tags">
                        <span>WiFi</span>
                        <span>Parkoló</span>
                        <span>Sátorhely</span>
                        <span>Étterem</span>
                    </div>
                    <div class="price-row">
                        <div class="price">12 000 Ft / éjszaka</div>
                        <button class="book" @click="goToSearch">Foglalás</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="https://picsum.photos/600/400?mountain" alt="">
                <div class="card-body">
                    <span class="badge">Kiemelt</span>
                    <h4>Mátra Vista Lakókocsi Park</h4>
                    <div class="rating">⭐ 4.9 (89)</div>
                    <div class="location">📍 Mátra, Gyöngyös</div>
                    <div class="tags">
                        <span>WiFi</span>
                        <span>Parkoló</span>
                        <span>Étterem</span>
                    </div>
                    <div class="price-row">
                        <div class="price">18 500 Ft / éjszaka</div>
                        <button class="book" @click="goToSearch">Foglalás</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <img src="https://picsum.photos/600/400?forest" alt="">
                <div class="card-body">
                    <span class="badge">Kiemelt</span>
                    <h4>Őrségi Erdei Kemping</h4>
                    <div class="rating">⭐ 4.7 (156)</div>
                    <div class="location">📍 Őrség, Szalafő</div>
                    <div class="tags">
                        <span>Parkoló</span>
                        <span>Sátorhely</span>
                    </div>
                    <div class="price-row">
                        <div class="price">8 500 Ft / éjszaka</div>
                        <button class="book">Foglalás</button>
                    </div>
                </div>
            </div>

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
            padding: 20px;
            gap: 20px;
        }

        
        .sidebar {
            width: 260px;
            background: white;
            padding: 20px;
            border-radius: 10px;
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
</style>