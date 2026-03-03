<template>
<div class="container">
    <h1>Cookie / Süti Kezelés</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Mi az a süti?</h2>
    <p>A süti (cookie) egy kis szöveges adatfájl, amelyet a weboldal helyez el a felhasználó eszközén. Segít a weboldal működésében, a felhasználói élmény javításában és az oldal statisztikáinak gyűjtésében.</p>

    <h2>2. Milyen típusú sütiket használunk?</h2>
    <ul class="cookie-types">
      <li><span class="highlight">Szükséges sütik:</span> Az oldal alapvető működéséhez szükségesek, pl. foglalási folyamat, bejelentkezés.</li>
      <li><span class="highlight">Teljesítmény sütik:</span> Az oldal használatáról gyűjtenek statisztikát, pl. látogatottság mérés.</li>
      <li><span class="highlight">Funkcionális sütik:</span> Segítik a weboldal testreszabását, pl. nyelvi beállítások megjegyzése.</li>
      <li><span class="highlight">Marketing / Analitikai sütik:</span> Harmadik fél szolgáltatók által helyezett sütik, hirdetések és elemzések céljából.</li>
    </ul>

    <h2>3. Süti beállítások</h2>
    <p>A felhasználó bármikor módosíthatja süti-beállításait a böngészőjében. Lehetőség van bizonyos sütik blokkolására, de ez hatással lehet a weboldal működésére.</p>

    <h2>4. Süti elfogadása</h2>
    <p>Az oldal használatával Ön hozzájárul a sütik használatához, kivéve, ha a böngészőjén keresztül blokkolja azokat.</p>

    <h2>5. Kapcsolat</h2>
    <p>Ha kérdése van a sütik kezelésével kapcsolatban, kérjük, vegye fel velünk a kapcsolatot:</p>
    <p>Email: butty.mate@diak.szbi-pg.hu</p>
    <p>Telefon: +36 30 123 4567</p>
  </div>
</template>

<style scoped>
  :root {
    --text-color: #263238;
    --muted: #64748b;
    --accent: #2c3e50;
    --card-bg: #ffffff;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.65;
    background-color: #f5f5f5;
    color: var(--text-color);
    margin: 0;
    padding: 0;
    font-size: 16px;
  }

  .container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px 30px;
    background-color: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  }

  h1 {
    text-align: center;
    color: var(--accent);
    margin-bottom: 14px;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.2px;
  }

  /* Kiemelt hatályos bekezdés (lead) */
  .container > p:first-of-type {
    text-align: center;
    color: var(--muted);
    margin-top: 0;
    margin-bottom: 20px;
    font-weight: 600;
    background: linear-gradient(90deg, rgba(99,102,241,0.03), rgba(34,197,94,0.01));
    padding: 8px 12px;
    border-radius: 8px;
    display: inline-block;
  }

  h2 {
    color: #1f2937;
    margin-top: 28px;
    margin-bottom: 10px;
    font-size: 20px;
    font-weight: 700;
  }

  p {
    margin: 8px 0 12px 0;
    color: var(--text-color);
    font-size: 15px;
  }

  ul {
    margin: 10px 0 14px 20px;
    padding-left: 6px;
    color: var(--text-color);
  }

  li {
    margin-bottom: 8px;
    line-height: 1.5;
    font-size: 15px;
  }

  .highlight {
    font-weight: 700;
    color: #0f172a;
    background: rgba(99,102,241,0.05);
    padding: 2px 6px;
    border-radius: 6px;
  }

  .cookie-types {
    background-color: #ecf0f1;
    padding: 15px;
    border-radius: 8px;
  }

  .cookie-types li {
    margin-bottom: 6px;
  }

  /* Kapcsolati adatok kiemelése */
  .container p:nth-last-of-type(-n+3) {
    color: #0b1220;
    margin: 6px 0;
    font-weight: 600;
  }

  @media (max-width: 600px) {
    .container { padding: 16px 18px; margin: 20px; }
    h1 { font-size: 22px; }
    h2 { font-size: 18px; }
    p, li { font-size: 15px; }
    .container > p:first-of-type { display: block; width: 100%; }
    ul { margin-left: 18px; }
  }
</style>