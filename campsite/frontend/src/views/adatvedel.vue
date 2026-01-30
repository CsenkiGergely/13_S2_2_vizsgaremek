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
    <h1>Adatvédelmi Nyilatkozat</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Adatkezelő</h2>
    <p>Név: [Cég / Magánszemély neve]</p>
    <p>Székhely: [Cím]</p>
    <p>Email: [Email cím]</p>
    <p>Telefonszám: [Telefonszám]</p>

    <h2>2. Gyűjtött adatok köre</h2>
    <ul>
      <li><span class="highlight">Személyes adatok:</span> név, email cím, telefonszám</li>
      <li><span class="highlight">Foglalási adatok:</span> érkezés/távozás dátuma, sátor/lakókocsi típusa, foglalás összeg</li>
      <li><span class="highlight">Fizetési adatok:</span> bankkártya adatok (csak a fizetési szolgáltatónál tárolva), számlázási adatok</li>
      <li><span class="highlight">Technikai adatok:</span> IP-cím, böngésző típusa, eszközinformációk, süti adatok</li>
    </ul>

    <h2>3. Adatkezelés célja</h2>
    <ul>
      <li>Foglalások kezelése és visszaigazolása</li>
      <li>Fizetési folyamatok biztosítása</li>
      <li>Szolgáltatás minőségének javítása és ügyfélszolgálat</li>
      <li>Marketing és hírlevélküldés (amennyiben a felhasználó hozzájárult)</li>
      <li>Jogszabályi kötelezettségek teljesítése</li>
    </ul>

    <h2>4. Adatkezelés jogalapja</h2>
    <ul>
      <li>A szerződés teljesítése (foglalás kezelése)</li>
      <li>Felhasználó hozzájárulása (marketing)</li>
      <li>Jogszabályi kötelezettség teljesítése</li>
      <li>Jogos érdek (pl. csalás megelőzése, weboldal biztonsága)</li>
    </ul>

    <h2>5. Adatok tárolása és védelme</h2>
    <p>A Szolgáltató az adatokat biztonságos szervereken, megfelelő technikai és szervezési intézkedésekkel tárolja. Az adatokhoz hozzáférést kizárólag az arra jogosult munkatársak kapnak.</p>
    <p>Az adatokat a foglalás teljesítésétől számított [X] évig, illetve a jogszabályok által előírt ideig őrizzük.</p>

    <h2>6. Adatmegosztás</h2>
    <ul>
      <li>Fizetési szolgáltatóval a tranzakció lebonyolítása érdekében</li>
      <li>Szálláshelyekkel a foglalások teljesítése céljából</li>
      <li>Hatóságoknak jogszabályi kötelezettség esetén</li>
    </ul>

    <h2>7. Süti (cookie) kezelés</h2>
    <p>A weboldal sütiket használ a felhasználói élmény javítása és a látogatottság elemzése céljából. A felhasználó bármikor módosíthatja a süti-beállításait böngészőjén keresztül.</p>

    <h2>8. Felhasználói jogok</h2>
    <ul>
      <li>Hozzáférés a róla kezelt adatokhoz</li>
      <li>Adatok helyesbítése vagy törlése</li>
      <li>Adatkezelés korlátozása</li>
      <li>Adathordozhatósághoz való jog</li>
      <li>Tiltakozás az adatkezelés ellen</li>
      <li>Hozzájárulás visszavonása (marketing esetén)</li>
    </ul>
    <p>Jogorvoslati lehetőség: Amennyiben a felhasználó úgy érzi, hogy adatkezelése jogsértő, panaszt tehet az <span class="highlight">Nemzeti Adatvédelmi és Információszabadság Hatóság</span> felé.</p>

    <h2>9. Kapcsolat</h2>
    <p>Adatvédelmi kérdések, panaszok esetén kérjük, lépjen kapcsolatba velünk:</p>
    <p>Email: [email@example.com]</p>
    <p>Telefon: [Telefonszám]</p>
    <p>Postai cím: [Cím]</p>
  </div>
</template>

 <style scoped>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      background-color: #f9f9f9;
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