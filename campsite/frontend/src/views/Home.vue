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

<script>
export default {
  methods: {
    goToSearch() {
      this.$router.push('/kereses')
    }
  }
}
</script>
<template>
 

  <div class="hero" role="banner">
    <div class="container">
      <div class="title">
        <h1>Találd meg a tökéletes kempinget</h1>
        <p class="lead">Fedezd fel a legjobb kempinghelyeket Magyarországon</p>
      </div>

      <div class="search-card" aria-labelledby="search-heading">
        <form class="grid" id="searchForm" onsubmit="event.preventDefault(); alert('Keresés indítva (demo)')">
          <div class="location-col">
            <label for="location">📍 Helyszín</label>
            <input id="location" name="location" type="text" placeholder="Pl. Balaton, Tisza-tó..." />
          </div>

          <div>
            <label for="checkIn">📅 Érkezés</label>
            <input id="checkIn" name="checkIn" type="date" />
          </div>

          <div>
            <label for="checkOut">📅 Távozás</label>
            <input id="checkOut" name="checkOut" type="date" />
          </div>

          <div>
            <label for="guests">👥 Vendégek</label>
            <input id="guests" name="guests" type="number" min="1" value="2" />
          </div>

          <div class="submit-col" style="margin-top:.5rem">
           
            <button class="btn" @click="goToSearch">🔍 Keresés</button>

          </div>
        </form>
      </div>
    </div>
  </div>

  
  <main class="content" role="main">
    
    <h2 class="section-title">Népszerű régiók</h2>
    <p class="muted">Válogatásunk a legkedveltebb kempingek közül — kattints a képekre a részletekért.</p>

   
    <div class="gallery" aria-label="Népszerű régiók képei">
      <a href="#"><img src="/img/spring-4891823_1920.jpg" alt="Naplemente a Balaton felett"/></a>
      <a href="#"><img src="/img/camp-2650359_1920.jpg" alt="Tisza-tó partja és csónakok"/></a>
      <a href="#"><img src="/img/camping-4806279_1920.jpg" alt="Erdő és kempinghely természetes környezetben"/></a>
      <a href="#"><img src="/img/people-4817872_1920.jpg" alt="Tanyasi horizont és csillagos égbolt"/></a>
    </div>


    <h2 class="section-title" style="margin-top:2rem">Kiemelt kempingünk</h2>
    <p class="muted">Különlegesen ajánlott hely — családbarát szolgáltatásokkal és gyönyörű panorámával.</p>

   
    <div class="single-image" aria-hidden="false">
      <img src="/img/night-1189929_1920.jpg" alt="Kiemelt kemping nagy panorámakép"/>
    </div>
  </main>
</template>

<style scoped>

    :root{
      --accent:#4A7434;
      --cta:#F17E21;
      --bg-grad-start:#f7faf7; 
      --bg-grad-end:#f1f5f7;
      --card-bg:#ffffff;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    .hero{
      position: relative;
      overflow: hidden;
      padding:3.5rem 0;
      color: #fff;
      /* félátlátszó zöld háttér (állítsd az alfa értéket tetszés szerint 0.0 - 1.0 között) */
      background-color: rgba(74,116,52,1);
    }
    .hero::before{
      content: "";
      position: absolute;
      inset: 0;
      /* a kép URL-jét cseréld le, ha másik képet akarsz */
      background-image: url('/img/ground-camping-8260968_1280.jpg');
      background-size: cover;
      background-position: center;
      /* állítsd az opacity-t a kívánt átlátszóságra (0 = láthatatlan, 1 = teljesen fed) */
      opacity: 0.06;
      pointer-events: none;
      z-index: 0;
    }
    .hero .container{
      position: relative;
      z-index: 1;
    }
    .container{
      width: min(1100px, 92%);
      margin: 0 auto;
    }

    .hero .title{
      text-align:center;
      margin-bottom:2rem;
    }
    .hero h1{
      font-size: clamp(1.6rem, 3.5vw, 2.5rem);
      font-weight:700;
      margin-bottom:0.5rem;
      line-height:1.05;
    }
    .hero p.lead{
      font-size:1.05rem;
      color: rgba(255,255,255,0.95);
    }
    
    .search-card{
      background-color: #fff;
      color: black;
      border-radius:1rem;
      padding:1.25rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.12);
      transform: translateY(40px);
    }
    form.grid{
      display:grid;
      grid-template-columns: repeat(1,1fr);
      gap:0.75rem;
      align-items:end;
    }
    label{
      display:block;
      font-size:.85rem;
      font-weight:600;
      margin-bottom:.35rem;
      color:#374151;
    }
    input[type="text"], input[type="date"], input[type="number"]{
      width:100%;
      padding:.75rem 1rem;
      border:1px solid #829dd4;
      border-radius:.625rem;
      outline:none;
      font-size:0.95rem;
      transition: box-shadow .15s, border-color .15s;
    }
    input:focus{
      box-shadow: 0 0 0 4px rgba(74,116,52,0.12);
    }
    .btn{
      background-color: #4A7434;
      color:#fff;
      padding:.7rem 1.5rem;
      border-radius:.75rem;
      border:none;
      cursor:pointer;
      font-weight:700;
      box-shadow: 0 8px 18px rgba(241,126,33,0.22);
      transition: background .25s, box-shadow .25s, transform .08s;
    }
    .btn:hover{ background: #F17E21; box-shadow: 0 14px 30px rgba(0,0,0,0.18)}
    .btn:active{ transform: translateY(1px) }

    
    @media(min-width:720px){
      form.grid{ grid-template-columns: repeat(2,1fr); }
      .location-col{ grid-column: span 2; }
    }
    @media(min-width:1024px){
      form.grid{ grid-template-columns: repeat(5,1fr); }
      .location-col{ grid-column: span 2; }
      .submit-col{ grid-column: 1 / -1; display:flex; justify-content:center; margin-top:.5rem; }
      .submit-col .btn{ padding: .8rem 3rem; font-size:1rem; }
    }

   
    .content {
      width: min(1100px, 92%);
      margin: 4rem auto;
    }
    .section-title{
      font-size:1.4rem;
      font-weight:700;
      color:var(--accent);
      margin: 1rem 0 0.75rem;
      text-align:center;
    }

   
    .gallery{
      display:grid;
      grid-template-columns: repeat(2, 1fr);
      gap:0.75rem;
      margin-top:1rem;
    }
    .gallery img{
      width:100%;
      height:220px;
      object-fit:cover;
      display:block;
      border-radius:.75rem;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

  
    .single-image{
      margin-top:1.25rem;
      display:flex;
      justify-content:center;
    }
    .single-image img{
      width:100%;
      max-width:980px;
      height:360px;
      object-fit:cover;
      border-radius:1rem;
      box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    }

    @media(min-width:880px){
      .gallery{ grid-template-columns: repeat(4, 1fr); }
      .gallery img{ height:160px; }
      .single-image img{ height:420px; }
    }

   
    .muted{
      color:#6b7280;
      font-size:.95rem;
      text-align:center;
      margin-top:.5rem;
    }

    .modal {
      display: none; /* alapból rejtett */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5); /* félátlátszó fekete */
      justify-content: center;
      align-items: center;
    }

    /* A modális tartalom */
    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      width: 300px;
    }

    .close-btn {
      margin-top: 10px;
      padding: 5px 10px;
    }
</style>
