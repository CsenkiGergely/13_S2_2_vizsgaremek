<script setup>
</script>

<template>
  <!-- Lábléc -->
  <footer class="beckgroundcolor">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row items-center justify-between">
        <!--Logo -->


      </div>
      
<div class="footer">
  <!-- 1. oszlop -->
  <div class="footer-column">
    <h3>KempingFoglaló</h3>
    <p>A legjobb kempinghelyek Magyarországon. Találd meg a tökéletes helyet a természetben.</p>
  </div>

  <!-- 2. oszlop -->
  <div class="footer-column">
    <h3>Gyors linkek</h3>
    <a href="#">Főoldal</a>
    <a href="#">Keresés</a>
    <a href="#">Profil</a>
    <a href="#">Szolgáltatások</a>
    <a href="#">Online foglalás</a>
    <a href="#">24/7 ügyfélszolgálat</a>
    <a href="#">Ingyenes lemondás</a>
    <a href="#">Értékelések</a>
    <a href="#">Kapcsolat</a>
  </div>

  <!-- 3. oszlop -->
  <div class="footer-column">
    <h3>Elérhetőség</h3>
           <div class="sor">
  <img src="/img/map-point.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1" class="color">Budapest, Magyarország</label>
</div>
           <div class="sor">
  <img src="/img/telephone-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1" class="color">+36 1 234 5678</label>
</div>
           <div class="sor">
  <img src="/img/mail-svgrepo-com.svg" alt="Példa kép" class="kicsi-kep">
  <label for="opcio1" class="color">info@kempingfoglalo.hu</label>
</div>

  </div>

  <!-- 4. oszlop -->
  <div class="footer-column">
    <h3>Extra információ</h3>
    <p>Itt adhatsz hozzá további információkat, például közösségi média ikonokat, nyitvatartást, vagy promóciós linkeket.</p>
  </div>
</div>
<hr style="border: 0; height: 2px; background-color: white; margin-top: 20px; margin-bottom: 20px;">
      <!-- copyright -->
<footer class="footerr">
  <div class="footer-left">
    © 2025 CampSite. Minden jog fenntartva.
  </div>
  <div class="footer-right">
    <a href="#">Adatvédelem</a>
    <a href="#">Felhasználási feltételek</a>
    <a href="#">Cookie-k</a>
  </div>
</footer>

    </div>
  </footer>
</template>

<style scoped>


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
    font-family: Arial, sans-serif;
    
  }

  .footer {
    display: flex;
    flex-wrap: wrap;      /* Mobilon lecsorogjon */
    gap: 20px;
    background-color: #4A7434;
    padding: 40px;
  }

  .footer-column {
    flex: 1 1 200px;      /* Minden oszlop rugalmas, minimum 200px */
  }

  .footer-column h3 {
    margin-bottom: 10px;
    color: white;
  }

  .footer-column p,
  .footer-column a {
    display: block;
    margin-bottom: 5px;
    color: white;
    text-decoration: none;
  }

  .footer-column a:hover {
    text-decoration: underline;
    
  }

  .h3 {
    color: white;
  }

  .beckgroundcolor {
  background-color: #4A7434;
}

.color {
  color: white;
}

.footerr {
  display: flex;
  justify-content: space-between; /* Bal-jobb oldal */
  align-items: center;
  flex-wrap: wrap;               /* Mobilon lecsorogjon */
  padding: 20px;
  background-color: #4A7434;     /* Sötét háttér */
  color: white;                  /* Fehér szöveg */
  font-size: 14px;
}

.footer-right a {
  color: white;
  text-decoration: none;
  margin-left: 15px;             /* Linkek közötti távolság */
}

.footer-right a:hover {
  text-decoration: underline;
}

/* Reszponzív mobilon */
@media (max-width: 600px) {
  .footer {
    flex-direction: column;      /* Egymás alá kerül minden */
    align-items: flex-start;
  }

  .footer-right a {
    margin-left: 0;
    margin-right: 15px;
  }

  .footer-right {
    margin-top: 10px;
  }
}

</style>
