<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'

const router = useRouter()
const today = new Date().toISOString().split('T')[0]

const searchForm = ref({
  location: '',
  checkIn: '',
  checkOut: '',
  adults: 2,
  children: 0
})

// --- Autocomplete ---
const suggestions = ref([])
const showSuggestions = ref(false)
const activeSuggestionIndex = ref(-1)
const locationInputRef = ref(null)
let debounceTimer = null

const fetchSuggestions = async (query) => {
  if (!query || query.length < 1) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  try {
    const response = await api.get('/locations/suggest', { params: { q: query } })
    suggestions.value = response.data || []
    showSuggestions.value = suggestions.value.length > 0
    activeSuggestionIndex.value = -1
  } catch (err) {
    console.error('Autocomplete hiba:', err)
    suggestions.value = []
    showSuggestions.value = false
  }
}

const onLocationInput = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchSuggestions(searchForm.value.location)
  }, 250)
}

const selectSuggestion = (suggestion) => {
  searchForm.value.location = suggestion.label
  showSuggestions.value = false
  activeSuggestionIndex.value = -1
}

const onLocationKeydown = (e) => {
  if (!showSuggestions.value || suggestions.value.length === 0) return

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeSuggestionIndex.value = Math.min(activeSuggestionIndex.value + 1, suggestions.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeSuggestionIndex.value = Math.max(activeSuggestionIndex.value - 1, 0)
  } else if (e.key === 'Enter' && activeSuggestionIndex.value >= 0) {
    e.preventDefault()
    selectSuggestion(suggestions.value[activeSuggestionIndex.value])
  } else if (e.key === 'Escape') {
    showSuggestions.value = false
  }
}

const onClickOutside = (e) => {
  const wrapper = document.querySelector('.location-col')
  if (wrapper && !wrapper.contains(e.target)) {
    showSuggestions.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  clearTimeout(debounceTimer)
})

// Beírt szöveg kiemelése a javaslatban
const highlightMatch = (text) => {
  const query = searchForm.value.location
  if (!query) return text
  const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return text.replace(regex, '<strong>$1</strong>')
}
// --- Autocomplete vége ---

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

const handleSearch = async () => {
  showSuggestions.value = false

  const query = {
    location: searchForm.value.location || undefined,
    guests: (searchForm.value.adults + searchForm.value.children) || undefined
  }

  // Dátumokat csak akkor küldjük, ha mindkettő meg van adva
  if (searchForm.value.checkIn && searchForm.value.checkOut) {
    query.checkIn = searchForm.value.checkIn
    query.checkOut = searchForm.value.checkOut
  }

  router.push({
    path: '/kereses',
    query
  })
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
        <form class="grid" id="searchForm" @submit.prevent="handleSearch">
          <div class="location-col">
            <label for="location">📍 Helyszín</label>
            <div class="autocomplete-wrapper">
              <input 
                id="location" 
                ref="locationInputRef"
                name="location" 
                type="text" 
                placeholder="Pl. Balaton, Tisza-tó..." 
                v-model="searchForm.location"
                @input="onLocationInput"
                @keydown="onLocationKeydown"
                @focus="searchForm.location.length >= 1 && suggestions.length > 0 && (showSuggestions = true)"
                autocomplete="off"
              />
              <ul v-if="showSuggestions" class="suggestions-list">
                <li 
                  v-for="(s, idx) in suggestions" 
                  :key="idx"
                  :class="{ active: idx === activeSuggestionIndex }"
                  @mousedown.prevent="selectSuggestion(s)"
                  @mouseenter="activeSuggestionIndex = idx"
                >
                  <span class="suggestion-icon">{{ s.type === 'camping' ? '🏕️' : '📍' }}</span>
                  <span class="suggestion-label" v-html="highlightMatch(s.label)"></span>
                  <span class="suggestion-type">{{ s.type === 'camping' ? 'Kemping' : 'Helyszín' }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div>
            <label for="checkIn">📅 Érkezés</label>
            <input 
              id="checkIn" 
              name="checkIn" 
              type="date" 
              v-model="searchForm.checkIn"
              :min="today"
            />
          </div>

          <div>
            <label for="checkOut">📅 Távozás</label>
            <input 
              id="checkOut" 
              name="checkOut" 
              type="date" 
              v-model="searchForm.checkOut"
              :min="minCheckOut"
            />
          </div>

          <div>
            <label for="adults">👥 Vendégek</label>
            <input 
              id="adults" 
              name="adults" 
              type="number" 
              min="1" 
              max="10"
              v-model.number="searchForm.adults"
            />
          </div>

          <div class="submit-col" style="margin-top:.5rem">
            <button type="submit" class="btn">🔍 Keresés</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <main class="content" role="main">
    
    <h2 class="section-title">Népszerű régiók</h2>
    <p class="muted">Válogatásunk a legkedveltebb kempingek közül — kattints a képekre a részletekért.</p>

   
    <div class="gallery" aria-label="Népszerű régiók képei">
      <router-link to="/foglalas">
        <a href="#"><img src="/img/spring-4891823_1920.jpg" alt="Naplemente a Balaton felett"/></a>
      </router-link>
      <router-link to="/foglalas">
        <a href="#"><img src="/img/camp-2650359_1920.jpg" alt="Tisza-tó partja és csónakok"/></a>
      </router-link>
      <router-link to="/foglalas">
        <a href="#"><img src="/img/camping-4806279_1920.jpg" alt="Erdő és kempinghely természetes környezetben"/></a>
      </router-link>
      <router-link to="/foglalas">
        <a href="#"><img src="/img/people-4817872_1920.jpg" alt="Tanyasi horizont és csillagos égbolt"/></a>
      </router-link>
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

      background-color: rgba(74,116,52,1);
    }
    .hero::before{
      content: "";
      position: absolute;
      inset: 0;

      background-image: url('/img/ground-camping-8260968_1280.jpg');
      background-size: cover;
      background-position: center;

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
      color: white;
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
      form.grid{ grid-template-columns: repeat(3,1fr); }
      .location-col{ grid-column: span 3; }
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

    /* Autocomplete */
    .autocomplete-wrapper {
      position: relative;
    }
    .suggestions-list {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: #fff;
      border: 1px solid #ddd;
      border-top: none;
      border-radius: 0 0 .625rem .625rem;
      list-style: none;
      margin: 0;
      padding: 0;
      z-index: 100;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      max-height: 280px;
      overflow-y: auto;
    }
    .suggestions-list li {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.65rem 1rem;
      cursor: pointer;
      font-size: 0.92rem;
      color: #333;
      transition: background 0.12s;
    }
    .suggestions-list li:hover,
    .suggestions-list li.active {
      background: #f0f7ed;
    }
    .suggestion-icon {
      font-size: 1rem;
      flex-shrink: 0;
    }
    .suggestion-label {
      flex: 1;
    }
    .suggestion-label strong {
      color: #4A7434;
      font-weight: 700;
    }
    .suggestion-type {
      font-size: 0.75rem;
      color: #999;
      flex-shrink: 0;
      background: #f3f4f6;
      padding: 2px 8px;
      border-radius: 10px;
    }
</style>
