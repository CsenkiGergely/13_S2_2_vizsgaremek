<script setup>
import { ref, onMounted } from 'vue'
import { useBooking } from '../composables/useBooking'
import dayjs from 'dayjs';
import "dayjs/locale/hu";
dayjs.locale("hu");

const { bookings, getAllBookings } = useBooking()

const activeTab = ref('dashboard')

onMounted(() => {
  getAllBookings()
})
</script>

<template>
  <div class="container">
    <h1>Kemping Tulajdonos</h1>
    <div class="subtitle">Kezelje a foglalásokat és monitorizálja a kemping működését</div>

    <!-- Tabs -->
    <div class="tabs">
      <div class="tab" :class="{ active: activeTab === 'dashboard' }" @click="activeTab = 'dashboard'">Dashboard</div>
      <div class="tab" :class="{ active: activeTab === 'foglalasok' }" @click="activeTab = 'foglalasok'">Foglalások</div>
      <div class="tab" :class="{ active: activeTab === 'terkep' }" @click="activeTab = 'terkep'">Térkép</div>
      <div class="tab" :class="{ active: activeTab === 'bevetelek' }" @click="activeTab = 'bevetelek'">Bevételek</div>
    </div>

    <!-- DASHBOARD -->
    <div v-if="activeTab === 'dashboard'">
      <div class="stats">
        <div class="card">
          <small>Összes foglalás</small>
          <h2>127</h2>
          <div class="trend">E hónapban +12%</div>
        </div>

        <div class="card">
          <small>Aktív vendégek</small>
          <h2>34</h2>
          <div class="trend">Jelenleg bent +8%</div>
        </div>

        <div class="card">
          <small>Foglalt helyek</small>
          <h2>28 / 45</h2>
          <div class="trend">62% foglaltság +15%</div>
        </div>

        <div class="card">
          <small>Havi bevétel</small>
          <h2>450 000 Ft</h2>
          <div class="trend">Aktuális hónap +25%</div>
        </div>
      </div>

      <div class="section">
        <h3>Legutóbbi foglalások</h3>
        <p>Az elmúlt hét foglalásai</p>

        <div class="booking">
          <div>
            <div class="name">Kovács János</div>
            <div class="place">Hely: A-15</div>
          </div>
          <div class="right">
            <div class="price">45 000 Ft</div>
            <span class="badge active">Aktív</span>
          </div>
        </div>

        <div class="booking">
          <div>
            <div class="name">Nagy Anna</div>
            <div class="place">Hely: B-8</div>
          </div>
          <div class="right">
            <div class="price">32 000 Ft</div>
            <span class="badge confirmed">Megerősített</span>
          </div>
        </div>

        <div class="booking">
          <div>
            <div class="name">Szabó Péter</div>
            <div class="place">Hely: C-3</div>
          </div>
          <div class="right">
            <div class="price">38 000 Ft</div>
            <span class="badge done">Befejezett</span>
          </div>
        </div>
      </div>
    </div>

    <!-- FOGLALÁSOK -->
    <div v-if="activeTab === 'foglalasok'">
      <div class="card">
        <h2>Összes foglalás</h2>
        <p>Kezelje a jelenlegi és múltbeli foglalásokat</p>
        <table>
          <thead>
            <tr>
              <th>Azonosító</th>
              <th>Vendég neve</th>
              <th>Hely</th>
              <th>Érkezés</th>
              <th>Távozás</th>
              <th>Vendégek</th>
              <th>Státusz</th>
              <th>Ár</th>
              <th>Műveletek</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in bookings" :key="booking.id">
              <td><strong>{{ booking.id }}</strong></td>
              <td>{{ booking.guestName }}</td>
              <td>{{ booking.spot }}</td>
              <td>{{ dayjs(booking.checkIn).format("YYYY. MMMM D.") }}</td>
              <td>{{ dayjs(booking.checkOut).format("YYYY. MMMM D.") }}</td>
              <td>{{ booking.guests }}</td>
              <td><span :class="['badge', booking.status === 'pending' ? 'pending' : booking.status === 'confirmed' ? 'confirmed' : booking.status === 'checked_in' ? 'checked_in' : booking.status === 'finished' ? 'finished' : booking.status === 'cancelled' ? 'cancelled' : '']">
                {{ booking.status === 'pending' ? 'Függőben van' : booking.status === 'confirmed' ? 'Megerősített' : booking.status === 'checked_in' ? 'Bejelentkezett' : booking.status === 'finished' ? 'Befejezett' : booking.status === 'cancelled' ? 'Lemondott' : ''}}
              </span></td>
              <td><strong>{{ booking.price }}</strong></td>
              <td><button class="btn">👁 Részletek</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TÉRKÉP -->
    <div v-if="activeTab === 'terkep'">
      <div class="card">
        <h2>Térkép</h2>
        <p>Kemping térképes nézete.</p>
      </div>
    </div>

    <!-- BEVÉTELEK -->
    <div v-if="activeTab === 'bevetelek'">
      <div class="stats">
        <div class="card">
          <small>Havi bevétel</small>
          <h2>450 000 Ft</h2>
          <div class="trend">+25% az előző hónaphoz képest</div>
        </div>
        <div class="card">
          <small>Átlagos foglalási érték</small>
          <h2>38 500 Ft</h2>
          <div class="trend">+12% az előző hónaphoz képest</div>
        </div>
      </div>

      <div class="section">
        <h3>Bevétel típusok szerint</h3>
        <p>Helyek típusainak bevétel megoszlása</p>

        <div class="booking">
          <div>
            <div class="name">Tóparti premium helyek</div>
            <div class="place">12 foglalás</div>
          </div>
          <div class="right">
            <div class="price">180 000 Ft</div>
            <p>40%</p>
          </div>
        </div>

        <div class="booking">
          <div>
            <div class="name">Erdei helyek</div>
            <div class="place">18 foglalás</div>
          </div>
          <div class="right">
            <div class="price">162 000 Ft</div>
            <p>36%</p>
          </div>
        </div>

        <div class="booking">
          <div>
            <div class="name">Standard helyek</div>
            <div class="place">22 foglalás</div>
          </div>
          <div class="right">
            <div class="price">108 000 Ft</div>
            <p>24%</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
  * {
    box-sizing: border-box;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }

  body {
    margin: 0;
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
    background: #f6f7fb;
    padding: 30px;
    color: #1f2937;
  }

  .container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 20px 60px;
  }

  h1 {
    margin: 0;
    font-size: 28px;
    color: #3f6212;
  }

  .subtitle {
    color: #6b7280;
    margin-bottom: 20px;
    margin-top: 4px;
  }

  .tabs {
    margin-top: 20px;
    display: inline-flex;
    background: #eef0f4;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 25px;
  }

  .tab {
    padding: 8px 14px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 8px;
    color: #374151;
  }

  .tab.active {
    background: white;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
  }

  .stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-top: 28px;
  }

  .card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, .06);
  }

  .card small { color: #64748b; }

  .card h2 {
    margin: 6px 0 5px;
    font-size: 26px;
  }

  .card p {
    margin-bottom: 20px;
    color: #6b7280;
    font-size: 14px;
  }

  .trend {
    font-size: 13px;
    color: #16a34a;
  }

  .section {
    margin-top: 28px;
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 22px;
  }

  .section h3 { margin: 0;}

  .section p {
    margin: 4px 0 18px;
    color: #64748b;
  }

  @media (max-width: 640px) {
    .booking {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
      display: flex;
      justify-content: space-between;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 12px;
    }
  }

  .booking:last-child { margin-bottom: 0; }

  .name { font-weight: 600; }

  .place { color: #64748b; font-size: 14px; }

  .right { text-align: right; }

  .price { font-weight: 600; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }

  th {
    text-align: left;
    color: #6b7280;
    font-weight: 500;
    padding-bottom: 10px;
  }

  td {
    padding: 14px 0;
    border-top: 1px solid #dbeafe;
  }

  .pending {
    background: #f3f4f6;
    color: #82a2d4;
  }

  .confirmed {
    background: #dbeafe;
    color: #1e40af;
  }

  .checked_in {
    background: #dbeafe;
    color: #30b965;
  }

  .finished {
    background: #f3f4f6;
    color: #374151;
  }

  .cancelled {
    background: #f3f4f6;
    color: #ff0000;
  }


  .btn {
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
  }

  .btn:hover {
    background: #f9fafb;
  }
</style>