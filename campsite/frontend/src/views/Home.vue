<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import 'vue3-carousel/carousel.css'
import { Carousel, Slide, Navigation, Pagination } from 'vue3-carousel'

// --- Slideshow: Legfelkapottabb kempingek ---
const topCampings = ref([])

const carouselConfig = {
  height: 460,
  itemsToShow: 1,
  gap: 12,
  autoplay: 10000,
  wrapAround: true,
  pauseAutoplayOnHover: true,
  snapAlign: 'center',
  breakpoints: {
    600: { itemsToShow: 2.0 },
    900: { itemsToShow: 2.5 },
    1200: { itemsToShow: 3.5 },
  },
}

const fetchTopCampings = async () => {
  try {
    const res = await api.get('/campings/top')
    const data = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    topCampings.value = data.map(c => ({
        id: c.id,
        name: c.camping_name || c.name,
        rating: parseFloat(c.average_rating) || 0,
        reviews: c.reviews_count || 0,
        location: c.location?.city || (typeof c.location === 'string' ? c.location : ''),
        image: c.photos?.[0]?.photo_url || c.photos?.[0]?.url || c.image || 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800',
        price: c.min_price || 0,
      }))
  } catch (e) {
    console.error('Top kempingek betöltési hiba:', e)
  }
}
// --- Slideshow vége ---

const renderStars = (rating) => {
  const full  = Math.floor(rating)
  const half  = rating - full >= 0.5 ? 1 : 0
  const empty = 5 - full - half
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty)
}

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
  fetchTopCampings()
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
<div>
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

    <h2 class="section-title" style="margin-top:2rem">🥇 Kiemelt kempingünk</h2>
    <p class="muted">A legjobb értékelésű kemping vendégeink szerint</p>

    <div v-if="topCampings.length > 0" class="featured-camping">
      <router-link :to="'/foglalas/' + topCampings[0].id" class="featured-link">
        <div class="featured-image-wrap">
          <img
            :src="topCampings[0].image"
            :alt="topCampings[0].name"
            @error="$event.target.src = '/img/night-1189929_1920.jpg'"
          />
          <div class="featured-overlay">
            <div class="featured-badge">
              <span class="slide-stars">{{ renderStars(topCampings[0].rating) }}</span>
              <span class="slide-rating-num">{{ topCampings[0].rating.toFixed(1) }}</span>
              <span class="slide-reviews" v-if="topCampings[0].reviews > 0">({{ topCampings[0].reviews }} értékelés)</span>
            </div>
            <h3 class="featured-name">{{ topCampings[0].name }}</h3>
            <p class="featured-location" v-if="topCampings[0].location">📍 {{ topCampings[0].location }}</p>
            <p class="featured-price" v-if="topCampings[0].price > 0">Már <strong>{{ topCampings[0].price.toLocaleString('hu-HU') }} Ft</strong> / éj</p>
            <span class="featured-btn">Foglalás →</span>
          </div>
        </div>
      </router-link>
    </div>
    <div v-else class="single-image" aria-hidden="false">
      <img src="/img/night-1189929_1920.jpg" alt="Kiemelt kemping nagy panorámakép"/>
    </div>

    <!-- Legfelkapottabb kempingek slideshow -->
    <h2 class="section-title" style="margin-top:2.5rem">🏆 Legfelkapottabb kempingek</h2>
    <p class="muted">A legjobb értékelésű kempingek vendégeink szerint</p>

    <div v-if="topCampings.length > 0" class="slideshow">
      <Carousel v-bind="carouselConfig">
        <Slide v-for="camping in topCampings" :key="camping.id">
          <div class="carousel-card">
            <img
              :src="camping.image"
              :alt="camping.name"
              @error="$event.target.src = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800'"
            />
            <div class="carousel-overlay">
              <div class="slide-badge">
                <span class="slide-stars">{{ renderStars(camping.rating) }}</span>
                <span class="slide-rating-num">{{ camping.rating.toFixed(1) }}</span>
                <span class="slide-reviews" v-if="camping.reviews > 0">({{ camping.reviews }})</span>
              </div>
              <h3 class="slide-name">{{ camping.name }}</h3>
              <p class="slide-location" v-if="camping.location">📍 {{ camping.location }}</p>
              <p class="slide-price" v-if="camping.price > 0">Már <strong>{{ camping.price.toLocaleString('hu-HU') }} Ft</strong> / éj</p>
              <router-link :to="'/foglalas/' + camping.id">
                <button class="slide-btn">Foglalás →</button>
              </router-link>
            </div>
          </div>
        </Slide>
        <template #addons>
          <Navigation />
          <Pagination />
        </template>
      </Carousel>
    </div>
    <div v-else class="slideshow-placeholder">
      <p class="muted">⏳ Betöltés...</p>
    </div>
  </main>
</div>
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

    /* Kiemelt kemping */
    .featured-camping { margin-top: 1.25rem; }
    .featured-link { text-decoration: none; display: block; }
    .featured-image-wrap {
      position: relative;
      width: 100%;
      height: 340px;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }
    .featured-image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.4s ease;
    }
    .featured-link:hover .featured-image-wrap img {
      transform: scale(1.04);
    }
    .featured-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.75rem 2rem;
      background: linear-gradient(to top, rgba(0,0,0,0.82) 0%, transparent 55%);
      color: #fff;
    }
    .featured-badge {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      margin-bottom: 0.4rem;
    }
    .featured-name {
      font-size: 1.5rem;
      font-weight: 800;
      margin-bottom: 0.25rem;
    }
    .featured-location {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.85);
      margin-bottom: 0.2rem;
    }
    .featured-price {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.8);
      margin-bottom: 0.8rem;
    }
    .featured-price strong { color: #fff; }
    .featured-btn {
      display: inline-block;
      background: #4A7434;
      color: #fff;
      padding: 0.5rem 1.4rem;
      border-radius: 0.6rem;
      font-size: 0.95rem;
      font-weight: 700;
      transition: background 0.2s;
    }
    .featured-link:hover .featured-btn { background: #F17E21; }

    @media(min-width:880px){
      .featured-image-wrap { height: 420px; }
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

    /* Slideshow */
    .slideshow {
      margin-top: 1.5rem;
      margin-bottom: 2.5rem;
      overflow: hidden;
    }

    @media (max-width: 599px) {
      :deep(.carousel__slide) {
        padding: 0 8px;
      }
      :deep(.carousel__prev),
      :deep(.carousel__next) {
        top: auto;
        bottom: 8px;
        transform: translateY(0);
        background: transparent;
        box-shadow: none;
        --vc-nav-color: #4A7434;
      }
      :deep(.carousel__prev:hover),
      :deep(.carousel__next:hover),
      :deep(.carousel__prev:active),
      :deep(.carousel__next:active) {
        background: transparent !important;
        --vc-nav-color: #4A7434;
      }
      :deep(.carousel__prev) {
        left: calc(50% - 72px);
      }
      :deep(.carousel__next) {
        right: calc(50% - 72px);
      }
    }

    :deep(.carousel) {
      --vc-nav-background: rgba(255, 255, 255, 0.85);
      --vc-nav-border-radius: 100%;
      --vc-nav-color: #4A7434;
      --vc-nav-color-hover: #fff;
      padding-bottom: 50px;
    }

    :deep(.carousel__prev:hover),
    :deep(.carousel__next:hover) {
      background: #4A7434;
    }

    /* 3D fókusz effekt */
    :deep(.carousel__viewport) {
      perspective: 2000px;
      overflow: visible;
    }
    :deep(.carousel__track) {
      transform-style: preserve-3d;
    }
    :deep(.carousel__slide--sliding) {
      transition: opacity 350ms, transform 350ms;
    }
    :deep(.carousel__slide) {
      opacity: 0.55;
      transform: rotateY(-8deg) scale(0.85);
      transition: opacity 350ms, transform 350ms;
      padding: 0 6px;
    }
    :deep(.carousel__slide--prev) {
      opacity: 0.8;
      transform: rotateY(-5deg) scale(0.92);
    }
    :deep(.carousel__slide--active) {
      opacity: 1;
      transform: rotateY(0deg) scale(1.08);
      z-index: 2;
    }
    :deep(.carousel__slide--next) {
      opacity: 0.8;
      transform: rotateY(5deg) scale(0.92);
    }

    /* Kártya */
    .carousel-card {
      position: relative;
      width: 100%;
      height: 380px;
      border-radius: 16px;
      overflow: hidden;
    }
    .carousel-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .carousel-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.25rem 1.5rem;
      background: linear-gradient(to top, rgba(0,0,0,0.78) 0%, transparent 60%);
      color: #fff;
    }

    .slide-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      margin-bottom: 0.3rem;
    }
    .slide-stars {
      color: #ffe066;
      font-size: 0.9rem;
      letter-spacing: 0.05em;
    }
    .slide-rating-num {
      color: #ffe066;
      font-weight: 700;
      font-size: 0.85rem;
    }
    .slide-reviews {
      color: rgba(255,255,255,0.7);
      font-size: 0.78rem;
    }
    .slide-name {
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 0.2rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .slide-location {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.85);
      margin-bottom: 0.15rem;
    }
    .slide-price {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.8);
      margin-bottom: 0.6rem;
    }
    .slide-price strong { color: #fff; }
    .slide-btn {
      background: #4A7434;
      color: #fff;
      border: none;
      padding: 0.4rem 1rem;
      border-radius: 0.5rem;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
    }
    .slide-btn:hover { background: #F17E21; }

    .slideshow-placeholder {
      height: 420px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f3f4f6;
      border-radius: 10px;
      margin-top: 1.25rem;
    }

    /* Pagination pontok */
    :deep(.carousel__pagination) {
      display: flex;
      justify-content: center;
      gap: 6px;
      padding: 8px 0 4px;
      margin: 0;
      list-style: none;
    }
    :deep(.carousel__pagination-button) {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #ccc;
      border: none;
      cursor: pointer;
      padding: 0;
      transition: background 0.2s, width 0.2s, border-radius 0.2s;
    }
    :deep(.carousel__pagination-button--active) {
      background: #4A7434;
      width: 24px;
      border-radius: 4px;
    }
    :deep(.carousel__pagination-button:hover) {
      background: #6a9a54;
    }
</style>
