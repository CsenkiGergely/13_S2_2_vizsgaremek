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

  const tabs = document.querySelectorAll('.tab');
  const pages = document.querySelectorAll('.page');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      const target = tab.dataset.page;
      pages.forEach(p => {
        p.style.display = p.id === target ? 'block' : 'none';
      });
    });
  });

</script>

<template>
<body>
  <div class="container">
    <h1>Kemping Admin</h1>
    <div class="subtitle">Kezelje a foglalásokat és monitorizálja a kemping működését</div>

    <div class="tabs">
      <div class="tab active" data-page="dashboard">Dashboard</div>
      <div class="tab" data-page="bookings">Foglalások</div>
      <div class="tab" data-page="map">Térkép</div>
      <div class="tab" data-page="revenue">Bevételek</div>
    </div>

    <!-- DASHBOARD -->
    <div class="page" id="dashboard">
      <div class="stats">
        <div class="card">
          <small>Összes foglalás</small>
          <h2>127</h2>
          <div class="trend">Ez hónapban +12%</div>
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

    <!-- FOGLALASOK -->
    <div class="page" id="bookings" style="display:none">
      <div class="section">
        <h3>Összes foglalás</h3>
        <p>Itt később lista + szerkesztés.</p>
      </div>
    </div>

    <!-- TERKEP -->
    <div class="page" id="map" style="display:none">
      <div class="section">
        <h3>Térkép</h3>
        <p>Itt jelenhet meg majd a kemping térképe.</p>
      </div>
    </div>

    <!-- BEVETELEK -->
    <div class="page" id="revenue" style="display:none">
      <div class="section">
        <h3>Bevételek</h3>
        <p>Később diagramok és statisztikák.</p>
      </div>
    </div>

  </div>


</body>
</template>

<style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      background: #fafafa;
      color: #0f172a;
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
      color: #64748b;
      margin-top: 4px;
    }

    /* Tabs */
    .tabs {
      margin-top: 20px;
      background: #f1f5f9;
      display: inline-flex;
      border-radius: 999px;
      padding: 4px;
      gap: 4px;
    }

    .tab {
      padding: 8px 14px;
      border-radius: 999px;
      font-size: 14px;
      cursor: pointer;
      color: #334155;
    }

    .tab.active {
      background: white;
      box-shadow: 0 1px 2px rgba(0,0,0,.05);
      font-weight: 600;
    }

    /* Stats */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-top: 28px;
    }

    .card {
      background: white;
      border-radius: 14px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,.05);
      border: 1px solid #e5e7eb;
    }

    .card small { color: #64748b; }

    .card h2 {
      margin: 6px 0 2px;
      font-size: 26px;
    }

    .trend {
      font-size: 13px;
      color: #16a34a;
    }

    /* Recent bookings */
    .section {
      margin-top: 28px;
      background: white;
      border-radius: 16px;
      border: 1px solid #e5e7eb;
      padding: 22px;
    }

    .section h3 { margin: 0; }
    .section p { margin: 4px 0 18px; color: #64748b; }

    .booking {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 12px;
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

    .active { background: #dcfce7; color: #166534; }
    .confirmed { background: #dbeafe; color: #1e40af; }
    .done { background: #f1f5f9; color: #334155; }

    @media (max-width: 640px) {
      .booking { flex-direction: column; align-items: flex-start; gap: 10px; }
      .right { text-align: left; }
    }
  </style>