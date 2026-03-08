<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import 'vue3-carousel/carousel.css'
import { Carousel, Slide, Navigation, Pagination } from 'vue3-carousel'

// --- Top kempingek ---
const topCampings = ref([])

const carouselConfig = {
  height: 420,
  itemsToShow: 1,
  gap: 16,
  autoplay: 8000,
  wrapAround: true,
  pauseAutoplayOnHover: true,
  snapAlign: 'center',
  breakpoints: {
    640: { itemsToShow: 2 },
    1024: { itemsToShow: 3 },
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

const renderStars = (rating) => {
  const full = Math.floor(rating)
  const half = rating - full >= 0.5 ? 1 : 0
  const empty = 5 - full - half
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty)
}

const router = useRouter()
const today = new Date().toISOString().split('T')[0]

const searchForm = ref({
  location: '',
  checkIn: '',
  checkOut: '',
  guests: 2,
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
    suggestions.value = []
    showSuggestions.value = false
  }
}

const onLocationInput = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchSuggestions(searchForm.value.location), 250)
}

const selectSuggestion = (s) => {
  searchForm.value.location = s.label
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
  const wrapper = document.querySelector('.search-location-col')
  if (wrapper && !wrapper.contains(e.target)) showSuggestions.value = false
}

onMounted(async () => {
  document.addEventListener('click', onClickOutside)
  await fetchTopCampings()
  fetchStats()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  clearTimeout(debounceTimer)
})

const highlightMatch = (text) => {
  const q = searchForm.value.location
  if (!q) return text
  const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return text.replace(regex, '<strong>$1</strong>')
}

const minCheckOut = computed(() => searchForm.value.checkIn || today)

const handleSearch = () => {
  showSuggestions.value = false
  const query = {
    location: searchForm.value.location || undefined,
    guests: searchForm.value.guests || undefined,
  }
  if (searchForm.value.checkIn && searchForm.value.checkOut) {
    query.checkIn = searchForm.value.checkIn
    query.checkOut = searchForm.value.checkOut
  }
  router.push({ path: '/kereses', query })
}

// --- "Miért a CampSite?" szekció: valós adatok ---
const campingCount = ref(0)
const averageRating = ref(0)

const fetchStats = async () => {
  try {
    // Lekérjük az összes kemping számát (per_page=1, csak a total kell)
    const res = await api.get('/campings', { params: { per_page: 1 } })
    campingCount.value = res.data?.total || 0

    // Átlagos értékelés a top kempingekből
    if (topCampings.value.length > 0) {
      const rated = topCampings.value.filter(c => c.rating > 0)
      if (rated.length > 0) {
        const sum = rated.reduce((acc, c) => acc + c.rating, 0)
        averageRating.value = Math.round((sum / rated.length) * 10) / 10
      }
    }
  } catch (e) {
    console.error('Statisztika betöltési hiba:', e)
  }
}

const stats = computed(() => [
  { icon: '🏕️', value: campingCount.value > 0 ? `${campingCount.value}+` : '...', label: 'helyszín országszerte' },
  { icon: '⭐', value: averageRating.value > 0 ? `${averageRating.value}/5` : '...', label: 'átlagos élményértékelés' },
  { icon: '🕐', value: '24/7', label: 'támogatás, ha kell' },
])

const features = [
  {
    icon: '📍',
    color: '#4A7434',
    title: 'Valódi helyek, jó érzékkel válogatva',
    desc: 'Nem elveszni akarsz a listákban, hanem gyorsan rátalálni arra a helyre, ami passzol a hangulatodhoz.',
  },
  {
    icon: '🛡️',
    color: '#F17E21',
    title: 'Nyugodt foglalás',
    desc: 'Átlátható adatok, gyors visszaigazolás és egy olyan folyamat, ami nem fáraszt le a végére.',
  },
  {
    icon: '⚡',
    color: '#F17E21',
    title: 'Pár kattintásból megvan',
    desc: 'Keresés, döntés, foglalás — röviden ennyi. Nem kell hozzá külön expedíciót szervezni.',
  },
  {
    icon: '⭐',
    color: '#4A7434',
    title: 'Őszinte vendégvélemények',
    desc: 'A jó helyeknél ezt mások is kimondják. Könnyebb dönteni, ha látod, mire számíthatsz.',
  },
  {
    icon: '💳',
    color: '#F17E21',
    title: 'Rugalmas fizetés',
    desc: 'Bankkártya, átutalás vagy helyszíni opciók — ott fizetsz úgy, ahogy neked kényelmes.',
  },
  {
    icon: '💬',
    color: '#F17E21',
    title: 'Van kit kérdezni',
    desc: 'Ha elakadsz, nem maradsz egyedül. Segítünk, hogy a szervezés ne vegye el a kedved.',
  },
]
</script>

<template>
<div class="home-page">

  <!-- ===== HERO + KERESÉS ===== -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
      <h1>Találd meg a tökéletes kempinget</h1>
      <p class="hero-sub">Fedezd fel a legjobb kempinghelyeket Magyarországon</p>

      <!-- Kereső — 1 sorban desktopra -->
      <form class="search-bar" @submit.prevent="handleSearch">
        <div class="search-location-col">
          <div class="search-field">
            <span class="search-icon">📍</span>
            <input
              ref="locationInputRef"
              type="text"
              placeholder="Helyszín (pl. Balaton)"
              v-model="searchForm.location"
              @input="onLocationInput"
              @keydown="onLocationKeydown"
              @focus="searchForm.location.length >= 1 && suggestions.length > 0 && (showSuggestions = true)"
              autocomplete="off"
            />
          </div>
          <!-- Autocomplete lista -->
          <ul v-if="showSuggestions" class="suggestions-list">
            <li
              v-for="(s, idx) in suggestions"
              :key="idx"
              :class="{ active: idx === activeSuggestionIndex }"
              @mousedown.prevent="selectSuggestion(s)"
              @mouseenter="activeSuggestionIndex = idx"
            >
              <span class="sug-icon">{{ s.type === 'camping' ? '🏕️' : '📍' }}</span>
              <span class="sug-label" v-html="highlightMatch(s.label)"></span>
              <span class="sug-type">{{ s.type === 'camping' ? 'Kemping' : 'Helyszín' }}</span>
            </li>
          </ul>
        </div>

        <div class="search-field">
          <span class="search-icon">📅</span>
          <input type="date" v-model="searchForm.checkIn" :min="today" placeholder="Érkezés" />
        </div>

        <div class="search-field">
          <span class="search-icon">📅</span>
          <input type="date" v-model="searchForm.checkOut" :min="minCheckOut" placeholder="Távozás" />
        </div>

        <div class="search-field search-field--narrow">
          <span class="search-icon">👥</span>
          <input type="number" min="1" max="20" v-model.number="searchForm.guests" />
        </div>

        <button type="submit" class="search-btn">Keresés</button>
      </form>
    </div>
  </section>

  <!-- ===== 1. LEGFELKAPOTTABB KEMPINGEK ===== -->
  <section class="section section--gray">
    <div class="section-header">
      <span class="section-badge">Népszerű kempingek</span>
      <h2>Legfelkapottabb kempingek</h2>
      <p class="section-sub">A legjobb értékelésű kempingek vendégeink szerint</p>
    </div>

    <div v-if="topCampings.length > 0" class="carousel-wrap">
      <Carousel v-bind="carouselConfig">
        <Slide v-for="camping in topCampings" :key="camping.id">
          <router-link :to="'/foglalas/' + camping.id" class="card-link">
            <div class="card">
              <img
                :src="camping.image"
                :alt="camping.name"
                @error="$event.target.src = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800'"
              />
              <div class="card-overlay">
                <div class="card-badge">
                  <span class="stars-text">{{ renderStars(camping.rating) }}</span>
                  <span class="rating-num">{{ camping.rating.toFixed(1) }}</span>
                </div>
                <h3>{{ camping.name }}</h3>
                <p v-if="camping.location" class="card-loc">📍 {{ camping.location }}</p>
                <p v-if="camping.price > 0" class="card-price">
                  Már <strong>{{ camping.price.toLocaleString('hu-HU') }} Ft</strong> / éj
                </p>
                <span class="card-btn">Foglalás →</span>
              </div>
            </div>
          </router-link>
        </Slide>
        <template #addons>
          <Navigation />
          <Pagination />
        </template>
      </Carousel>
    </div>
    <div v-else class="carousel-placeholder">
      <p>Kempingek betöltése...</p>
    </div>
  </section>

  <!-- ===== 2. MIÉRT A CAMPSITE? ===== -->
  <section class="section why-section">
    <div class="section-header">
      <span class="section-badge">Miért működik jól?</span>
      <h2>Olyan foglalós élmény, ami végre<br>nem túl merev és nem túl zajos.</h2>
      <p class="section-sub">Modern, gyors és emberi. Pont annyi segítséget ad, amennyi kell a jó döntéshez.</p>
    </div>

    <!-- Statisztikák -->
    <div class="stats-row">
      <div v-for="(s, i) in stats" :key="i" class="stat-card">
        <span class="stat-icon">{{ s.icon }}</span>
        <div>
          <div class="stat-value">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- Feature kártyák -->
    <div class="features-grid">
      <div v-for="(f, i) in features" :key="i" class="feature-card">
        <span class="feature-icon" :style="{ backgroundColor: f.color + '18', color: f.color }">
          {{ f.icon }}
        </span>
        <h4>{{ f.title }}</h4>
        <p>{{ f.desc }}</p>
      </div>
    </div>
  </section>

  <!-- ===== 3. KIEMELT KEMPING ===== -->
  <section class="section">
    <div class="section-header">
      <span class="section-badge">Kiemelt kempingünk</span>
      <h2>A legjobban értékelt kemping</h2>
      <p class="section-sub">Vendégeink abszolút kedvence</p>
    </div>

    <div v-if="topCampings.length > 0" class="featured">
      <router-link :to="'/foglalas/' + topCampings[0].id" class="featured-link">
        <div class="featured-img">
          <img
            :src="topCampings[0].image"
            :alt="topCampings[0].name"
            @error="$event.target.src = '/img/night-1189929_1920.jpg'"
          />
          <div class="featured-overlay">
            <div class="featured-stars">
              <span class="stars-text">{{ renderStars(topCampings[0].rating) }}</span>
              <span class="rating-num">{{ topCampings[0].rating.toFixed(1) }}</span>
              <span v-if="topCampings[0].reviews" class="review-count">({{ topCampings[0].reviews }} értékelés)</span>
            </div>
            <h3>{{ topCampings[0].name }}</h3>
            <p v-if="topCampings[0].location" class="featured-loc">📍 {{ topCampings[0].location }}</p>
            <p v-if="topCampings[0].price > 0" class="featured-price">
              Már <strong>{{ topCampings[0].price.toLocaleString('hu-HU') }} Ft</strong> / éj
            </p>
            <span class="featured-cta">Részletek és foglalás →</span>
          </div>
        </div>
      </router-link>
    </div>
    <div v-else class="featured-placeholder">
      <img src="/img/night-1189929_1920.jpg" alt="Kemping" />
    </div>
  </section>

</div>
</template>

<style scoped>
/* ===== VÁLTOZÓK ===== */
.home-page {
  --accent: #4A7434;
  --cta: #F17E21;
  --radius: 1rem;
  --max-w: 1140px;
}

/* ===== HERO ===== */
.hero {
  position: relative;
  padding: 3rem 1.25rem 3.5rem;
  display: flex;
  justify-content: center;
  overflow: visible;
  z-index: 10;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: var(--accent);
  z-index: 0;
}
.hero-bg::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('/img/ground-camping-8260968_1280.jpg');
  background-size: cover;
  background-position: center;
  opacity: 0.07;
}
.hero-content {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: var(--max-w);
  text-align: center;
  color: #fff;
}
.hero h1 {
  font-size: clamp(1.5rem, 4vw, 2.6rem);
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 0.4rem;
}
.hero-sub {
  font-size: 1.05rem;
  color: rgba(255,255,255,0.9);
  margin-bottom: 2rem;
}

/* ===== KERESŐSOR ===== */
.search-bar {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: #fff;
  border-radius: var(--radius);
  padding: 0.75rem;
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}
.search-location-col {
  position: relative;
  flex: 2;
}
.search-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f7f8fa;
  border-radius: 0.625rem;
  padding: 0 0.75rem;
  border: 1px solid transparent;
  transition: border-color 0.15s;
}
.search-field:focus-within {
  border-color: var(--accent);
  background: #fff;
}
.search-field--narrow {
  max-width: 100%;
}
.search-icon {
  font-size: 1rem;
  flex-shrink: 0;
}
.search-field input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 0.7rem 0;
  font-size: 0.92rem;
  outline: none;
  color: #1f2937;
  min-width: 0;
}
.search-field input::placeholder {
  color: #9ca3af;
}
.search-btn {
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 0.625rem;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
  white-space: nowrap;
}
.search-btn:hover {
  background: var(--cta);
}

/* Desktop: 1 sor */
@media (min-width: 860px) {
  .search-bar {
    flex-direction: row;
    align-items: stretch;
    padding: 0.5rem;
    gap: 0.35rem;
  }
  .search-field--narrow {
    max-width: 100px;
  }
  .search-btn {
    padding: 0 2rem;
  }
}

/* ===== AUTOCOMPLETE ===== */
.suggestions-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-top: none;
  border-radius: 0 0 0.625rem 0.625rem;
  list-style: none;
  margin: 0;
  padding: 0;
  z-index: 200;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  max-height: 260px;
  overflow-y: auto;
}
.suggestions-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 0.85rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #374151;
  transition: background 0.1s;
}
.suggestions-list li:hover,
.suggestions-list li.active {
  background: #f0f7ed;
}
.sug-icon { flex-shrink: 0; }
.sug-label { flex: 1; }
.sug-label :deep(strong) { color: var(--accent); font-weight: 700; }
.sug-type {
  font-size: 0.72rem;
  color: #9ca3af;
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 8px;
  flex-shrink: 0;
}

/* ===== SZEKCIÓK ===== */
.section {
  padding: 3rem 1.25rem;
}
.section--gray {
  background: #f8faf8;
}
.section-header {
  text-align: center;
  max-width: 640px;
  margin: 0 auto 2rem;
}
.section-badge {
  display: inline-block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--accent);
  border: 1px solid #c5dbb8;
  border-radius: 999px;
  padding: 0.25rem 1rem;
  margin-bottom: 0.75rem;
}
.section-header h2 {
  font-size: clamp(1.25rem, 3vw, 1.75rem);
  font-weight: 800;
  color: #1f2937;
  line-height: 1.25;
  margin-bottom: 0.5rem;
}
.section-sub {
  color: #6b7280;
  font-size: 0.95rem;
}

/* ===== KIEMELT KEMPING ===== */
.featured {
  max-width: var(--max-w);
  margin: 0 auto;
}
.featured-link {
  text-decoration: none;
  display: block;
}
.featured-img {
  position: relative;
  width: 100%;
  height: 320px;
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}
.featured-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}
.featured-link:hover .featured-img img {
  transform: scale(1.03);
}
.featured-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 1.5rem 1.75rem;
  background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 55%);
  color: #fff;
}
.featured-stars {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.3rem;
}
.featured-overlay h3 {
  font-size: 1.4rem;
  font-weight: 800;
  margin-bottom: 0.2rem;
}
.featured-loc {
  font-size: 0.88rem;
  color: rgba(255,255,255,0.85);
}
.featured-price {
  font-size: 0.88rem;
  color: rgba(255,255,255,0.8);
  margin-bottom: 0.65rem;
}
.featured-price strong { color: #fff; }
.featured-cta {
  display: inline-block;
  background: var(--accent);
  color: #fff;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
  font-weight: 700;
  font-size: 0.9rem;
  transition: background 0.2s;
  width: fit-content;
}
.featured-link:hover .featured-cta { background: var(--cta); }
.featured-placeholder {
  max-width: var(--max-w);
  margin: 0 auto;
  border-radius: var(--radius);
  overflow: hidden;
  height: 320px;
}
.featured-placeholder img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (min-width: 768px) {
  .featured-img { height: 400px; }
  .featured-placeholder { height: 400px; }
}

/* ===== CAROUSEL ===== */
.carousel-wrap {
  max-width: var(--max-w);
  margin: 0 auto;
  overflow: hidden;
}
.card-link {
  text-decoration: none;
  display: block;
  width: 100%;
}
.card {
  position: relative;
  width: 100%;
  height: 360px;
  border-radius: var(--radius);
  overflow: hidden;
}
.card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.card-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 1.25rem;
  background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 55%);
  color: #fff;
}
.card-badge {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-bottom: 0.25rem;
}
.stars-text {
  color: #ffe066;
  font-size: 0.88rem;
  letter-spacing: 0.03em;
}
.rating-num {
  color: #ffe066;
  font-weight: 700;
  font-size: 0.85rem;
}
.review-count {
  color: rgba(255,255,255,0.7);
  font-size: 0.78rem;
}
.card-overlay h3 {
  font-size: 1rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.15rem;
}
.card-loc {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.85);
}
.card-price {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.8);
  margin-bottom: 0.5rem;
}
.card-price strong { color: #fff; }
.card-btn {
  display: inline-block;
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 0.4rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.82rem;
  font-weight: 700;
  width: fit-content;
  transition: background 0.2s;
}
.card-link:hover .card-btn { background: var(--cta); }

.carousel-placeholder {
  height: 360px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: var(--radius);
  max-width: var(--max-w);
  margin: 0 auto;
  color: #9ca3af;
}

/* Carousel stílusok */
:deep(.carousel) {
  --vc-nav-background: rgba(255,255,255,0.9);
  --vc-nav-border-radius: 100%;
  --vc-nav-color: var(--accent);
  --vc-nav-color-hover: #fff;
  padding-bottom: 48px;
}
:deep(.carousel__prev:hover),
:deep(.carousel__next:hover) {
  background: var(--accent);
}
:deep(.carousel__slide) {
  padding: 0 8px;
  opacity: 0.7;
  transform: scale(0.92);
  transition: opacity 300ms, transform 300ms;
}
:deep(.carousel__slide--active) {
  opacity: 1;
  transform: scale(1);
  z-index: 2;
}
:deep(.carousel__slide--prev),
:deep(.carousel__slide--next) {
  opacity: 0.85;
  transform: scale(0.95);
}
:deep(.carousel__pagination) {
  display: flex;
  justify-content: center;
  gap: 6px;
  padding: 8px 0 0;
  margin: 0;
  list-style: none;
}
:deep(.carousel__pagination-button) {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d1d5db;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background 0.2s, width 0.2s, border-radius 0.2s;
}
:deep(.carousel__pagination-button--active) {
  background: var(--accent);
  width: 24px;
  border-radius: 4px;
}

/* ===== MIÉRT A CAMPSITE? ===== */
.why-section {
  background: #f8faf8;
}
.stats-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  max-width: var(--max-w);
  margin: 0 auto 2rem;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: #fff;
  border: 1px solid #e8ede5;
  border-radius: var(--radius);
  padding: 1.1rem 1.25rem;
}
.stat-icon {
  font-size: 1.5rem;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef4eb;
  border-radius: 50%;
  flex-shrink: 0;
}
.stat-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: #1f2937;
  line-height: 1.2;
}
.stat-label {
  font-size: 0.82rem;
  color: #6b7280;
}

@media (min-width: 640px) {
  .stats-row { grid-template-columns: repeat(3, 1fr); }
}

.features-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  max-width: var(--max-w);
  margin: 0 auto;
}
.feature-card {
  background: #fff;
  border: 1px solid #e8ede5;
  border-radius: var(--radius);
  padding: 1.25rem;
}
.feature-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 0.625rem;
  font-size: 1.2rem;
  margin-bottom: 0.75rem;
}
.feature-card h4 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.35rem;
}
.feature-card p {
  font-size: 0.85rem;
  color: #6b7280;
  line-height: 1.5;
}

@media (min-width: 640px) {
  .features-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 960px) {
  .features-grid { grid-template-columns: repeat(3, 1fr); }
}

/* ===== MOBIL FINOMHANGOLÁSOK ===== */
@media (max-width: 639px) {
  .hero { padding: 2rem 1rem 2.5rem; }
  .hero h1 { font-size: 1.5rem; }
  .hero-sub { font-size: 0.92rem; margin-bottom: 1.25rem; }
  .section { padding: 2rem 1rem; }
  .featured-img { height: 260px; }
  .featured-placeholder { height: 260px; }
  .featured-overlay { padding: 1rem 1.25rem; }
  .featured-overlay h3 { font-size: 1.15rem; }
  .card { height: 300px; }

  :deep(.carousel__prev),
  :deep(.carousel__next) {
    display: none;
  }
}
</style>
