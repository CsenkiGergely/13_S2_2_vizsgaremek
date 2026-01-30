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
    <h1>Felhasználási Feltételek</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Általános rendelkezések</h2>
    <p>Ez a weboldal CampSite használatával Ön elfogadja a jelen felhasználási feltételeket. Kérjük, olvassa el figyelmesen, mielőtt igénybe veszi szolgáltatásainkat.</p>

    <h2>2. Szolgáltatás leírása</h2>
    <p>Az oldal lehetővé teszi a camping helyek online foglalását, információk megtekintését és fizetési folyamatok lebonyolítását.</p>

    <h2>3. Regisztráció és felhasználói fiók</h2>
    <ul>
      <li>A fiók létrehozásához valós adatokat kell megadni.</li>
      <li>Felhasználónév és jelszó titkos kezelése a felhasználó felelőssége.</li>
      <li>Az oldal nem vállal felelősséget a felhasználói fiók jogosulatlan használatáért, ha a felhasználó adatai nem megfelelően védettek.</li>
    </ul>

    <h2>4. Foglalások és fizetés</h2>
    <ul>
      <li>A foglalás a weboldalon keresztül történik, a visszaigazolás után érvényes.</li>
      <li>Az esetleges lemondások vagy módosítások az oldal szabályzata szerint történnek.</li>
      <li>A fizetés biztonságos fizetési szolgáltató segítségével zajlik.</li>
    </ul>

    <h2>5. Felhasználói tartalom</h2>
    <p>Felhasználó felelős az általa megadott tartalomért (értékelések, üzenetek, fotók). Tilos jogsértő, sértő vagy spam tartalom közzététele.</p>

    <h2>6. Felelősség és korlátozás</h2>
    <p>Az oldal mindent megtesz a szolgáltatás zavartalan működéséért, de nem vállal felelősséget technikai hibák, adatvesztés vagy harmadik fél szolgáltatásai miatt bekövetkező károkért.</p>

    <h2>7. Szellemi tulajdon</h2>
    <p>A weboldal tartalma, logók, grafikák és szövegek szerzői jogvédelem alatt állnak. Másolás vagy kereskedelmi felhasználás csak a Szolgáltató engedélyével lehetséges.</p>

    <h2>8. Módosítások</h2>
    <p>A Szolgáltató fenntartja a jogot, hogy a felhasználási feltételeket időről időre módosítsa. A változtatások a weboldalon történő közzétételkor lépnek hatályba.</p>

    <h2>9. Kapcsolat</h2>
    <p>Felhasználási feltételekkel kapcsolatos kérdések esetén kérjük, lépjen kapcsolatba velünk:</p>
    <p>Email: butty.mate@diak.szbi-pg.hu</p>
    <p>Telefon: +36 30 123 4567</p>
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

    @media (max-width: 600px) {
      .container {
        padding: 15px 20px;
      }
    }
  </style>
