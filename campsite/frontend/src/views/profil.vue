<script setup>
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'

const { user, logout } = useAuth()
const router = useRouter()

const handleLogout = async () => {
  await logout()
  router.push('/') // Átirányítás a főoldalra kijelentkezés után
}
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
                    <button 
              @click="handleLogout"
              class="logout-btn"
            >
              🚪 Kijelentkezés
            </button>
    </div>

    <div class="cards">
        <div class="card">
            <h2>Profil adatok</h2>

            <div class="form-group">
                <label>Név</label>
                <input type="text" :value="user?.owner_first_name + ' ' + user?.owner_last_name" readonly>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" :value="user?.email" readonly>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="text" :value="user?.phone || 'Nincs megadva'" readonly>
            </div>

            <button class="save-btn">Profil mentése</button>
        </div>

        <div class="card">
            <h2>Foglalási statisztikák</h2>
            <div class="stats">
                <div>
                    <div class="stat-number">0</div>
                    <div class="stat-label">Aktív foglalás</div>
                </div>
                <div>
                    <div class="stat-number">0</div>
                    <div class="stat-label">Összes foglalás</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bookings">
        <h2>Foglalásaim</h2>
        <div class="empty-text">Még nincs foglalása.</div>
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

body {
    background-color: #f4f6f8;
    padding: 40px;
    color: #1f2937;
}

.container {
    max-width: 1100px;
    margin: auto;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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
    background-color: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 22px;
}

.profile-info h1 {
    font-size: 26px;
    font-weight: 700;
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
}

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
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 500;
}

input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 14px;
}

.save-btn {
    width: 100%;
    margin-top: 15px;
    padding: 12px;
    background-color: #4f6f3c;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
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
    color: #4f6f3c;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

.bookings {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.bookings h2 {
    margin-bottom: 20px;
}

.empty-text {
    text-align: center;
    color: #6b7280;
    padding: 30px 0;
}

@media (max-width: 900px) {
    .cards {
        flex-direction: column;
    }
}
</style>