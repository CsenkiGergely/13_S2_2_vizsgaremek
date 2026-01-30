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
 
}
</script>

<template>
<div class="container">
    <h1>Cookie / Süti Kezelés</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Mi az a süti?</h2>
    <p>A süti (cookie) egy kis szöveges adatfájl, amelyet a weboldal helyez el a felhasználó eszközén. Segít a weboldal működésében, a felhasználói élmény javításában és az oldal statisztikáinak gyűjtésében.</p>

    <h2>2. Milyen típusú sütiket használunk?</h2>
    <ul class="cookie-types">
      <li><span class="highlight">Szükséges sütik:</span> Az oldal alapvető működéséhez szükségesek, pl. foglalási folyamat, bejelentkezés.</li>
      <li><span class="highlight">Teljesítmény sütik:</span> Az oldal használatáról gyűjtenek statisztikát, pl. látogatottság mérés.</li>
      <li><span class="highlight">Funkcionális sütik:</span> Segítik a weboldal testreszabását, pl. nyelvi beállítások megjegyzése.</li>
      <li><span class="highlight">Marketing / Analitikai sütik:</span> Harmadik fél szolgáltatók által helyezett sütik, hirdetések és elemzések céljából.</li>
    </ul>

    <h2>3. Süti beállítások</h2>
    <p>A felhasználó bármikor módosíthatja süti-beállításait a böngészőjében. Lehetőség van bizonyos sütik blokkolására, de ez hatással lehet a weboldal működésére.</p>

    <h2>4. Süti elfogadása</h2>
    <p>Az oldal használatával Ön hozzájárul a sütik használatához, kivéve, ha a böngészőjén keresztül blokkolja azokat.</p>

    <h2>5. Kapcsolat</h2>
    <p>Ha kérdése van a sütik kezelésével kapcsolatban, kérjük, vegye fel velünk a kapcsolatot:</p>
    <p>Email: [email@example.com]</p>
    <p>Telefon: [Telefonszám]</p>
  </div>
</template>

<style scoped>
body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      background-color: #f5f5f5;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 20px 30px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      color: #2c3e50;
      margin-bottom: 20px;
    }

    h2 {
      color: #34495e;
      margin-top: 30px;
    }

    p {
      margin: 10px 0;
    }

    ul {
      margin: 10px 0 10px 20px;
    }

    li {
      margin-bottom: 8px;
    }

    .highlight {
      font-weight: bold;
      color: #2c3e50;
    }

    .cookie-types {
      background-color: #ecf0f1;
      padding: 15px;
      border-radius: 8px;
    }

    .cookie-types li {
      margin-bottom: 6px;
    }

    @media (max-width: 600px) {
      .container {
        padding: 15px 20px;
      }
    }
</style>