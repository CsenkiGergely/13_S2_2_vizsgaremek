<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'
import api from '../api/axios'

const { user, logout, isAuthenticated } = useAuth()
const router = useRouter()

// --- Foglalások statisztikákhoz ---
const bookings = ref([])

const fetchBookings = async () => {
  try {
    const res = await api.get('/bookings')
    bookings.value = res.data.data || res.data || []
  } catch (err) {
    console.error('Foglalások betöltési hiba:', err)
  }
}

const activeBookings = computed(() => {
  return bookings.value.filter(b => ['pending', 'confirmed', 'checked_in'].includes(b.status))
})

const totalBookings = computed(() => bookings.value.length)

const handleLogout = async () => {
  await logout()
  router.push('/')
}

onMounted(() => {
  if (isAuthenticated.value) {
    fetchBookings()
  }
})
</script>

<template>
<div class="container">

    <div class="top-bar">
        <div class="profile-header">
            <div class="avatar">{{ user?.owner_first_name?.charAt(0).toUpperCase() || 'U' }}</div>
            <div class="profile-info">
                <h1>{{ user?.owner_first_name }} {{ user?.owner_last_name }}</h1>
                <p>{{ user?.email }}</p>
            </div>
        </div>
        <button @click="handleLogout" class="logout-btn">
          🚪 Kijelentkezés
        </button>
    </div>

    <div class="cards">
        <div class="card">
            <h2>Profil adatok</h2>

            <div class="form-group">
                <label>Név</label>
                <input type="text" :value="(user?.owner_last_name || '') + ' ' + (user?.owner_first_name || '')" readonly>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" :value="user?.email" readonly>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="text" :value="user?.phone || 'Nincs megadva'" readonly>
            </div>
        </div>

        <div class="card">
            <h2>Foglalási statisztikák</h2>
            <div class="stats">
                <div>
                    <div class="stat-number">{{ activeBookings.length }}</div>
                    <div class="stat-label">Aktív foglalás</div>
                </div>
                <div>
                    <div class="stat-number">{{ totalBookings }}</div>
                    <div class="stat-label">Összes foglalás</div>
                </div>
            </div>
            <router-link to="/foglalasaim" class="bookings-link">
              📅 Foglalásaim megtekintése →
            </router-link>
        </div>
    </div>

</div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

.container {
    max-width: 1140px;
    margin: auto;
    padding: 30px 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #4A7434;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 22px;
}

.profile-info h1 {
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
}

.profile-info p {
    color: #6b7280;
    font-size: 14px;
}

.logout-btn {
    padding: 10px 18px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: white;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}
.logout-btn:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}

/* Cards */
.cards {
    display: flex;
    gap: 25px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    flex: 1;
}

.card h2 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #1f2937;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
}

input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    color: #374151;
    background: #f9fafb;
}

.stats {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #4A7434;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

.bookings-link {
    display: block;
    margin-top: 20px;
    text-align: center;
    padding: 10px 16px;
    background: #f0fdf4;
    color: #4A7434;
    border: 1px solid #4A7434;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}

.bookings-link:hover {
    background: #dcfce7;
    border-color: #3d6129;
}

@media (max-width: 900px) {
    .cards {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .profile-info h1 {
        font-size: 20px;
    }
}
</style>