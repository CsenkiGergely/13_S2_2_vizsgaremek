<template>
 <div class="container">
    <h1>Adatvédelmi Nyilatkozat</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Adatkezelő</h2>
    <p>Név: Butty Máté</p>
    <p>Székhely: Bácskiskun vármegye</p>
    <p>Email: butty.mate@diak.szbi-pg.hu</p>
    <p>Telefonszám: +36 30 123 4567</p>

    <h2>2. Gyűjtött adatok köre</h2>
    <ul>
      <li><span class="highlight">Személyes adatok:</span> név, email cím, telefonszám</li>
      <li><span class="highlight">Foglalási adatok:</span> érkezés/távozás dátuma, sátor/lakókocsi típusa, foglalás összeg</li>
      <li><span class="highlight">Fizetési adatok:</span> bankkártya adatok (csak a fizetési szolgáltatónál tárolva), számlázási adatok</li>
      <li><span class="highlight">Technikai adatok:</span> IP-cím, böngésző típusa, eszközinformációk, süti adatok</li>
    </ul>

    <h2>3. Adatkezelés célja</h2>
    <ul>
      <li>Foglalások kezelése és visszaigazolása</li>
      <li>Fizetési folyamatok biztosítása</li>
      <li>Szolgáltatás minőségének javítása és ügyfélszolgálat</li>
      <li>Marketing és hírlevélküldés (amennyiben a felhasználó hozzájárult)</li>
      <li>Jogszabályi kötelezettségek teljesítése</li>
    </ul>

    <h2>4. Adatkezelés jogalapja</h2>
    <ul>
      <li>A szerződés teljesítése (foglalás kezelése)</li>
      <li>Felhasználó hozzájárulása (marketing)</li>
      <li>Jogszabályi kötelezettség teljesítése</li>
      <li>Jogos érdek (pl. csalás megelőzése, weboldal biztonsága)</li>
    </ul>

    <h2>5. Adatok tárolása és védelme</h2>
    <p>A Szolgáltató az adatokat biztonságos szervereken, megfelelő technikai és szervezési intézkedésekkel tárolja. Az adatokhoz hozzáférést kizárólag az arra jogosult munkatársak kapnak.</p>
    <p>Az adatokat a foglalás teljesítésétől számított 5 évig, illetve a jogszabályok által előírt ideig őrizzük.</p>

    <h2>6. Adatmegosztás</h2>
    <ul>
      <li>Fizetési szolgáltatóval a tranzakció lebonyolítása érdekében</li>
      <li>Szálláshelyekkel a foglalások teljesítése céljából</li>
      <li>Hatóságoknak jogszabályi kötelezettség esetén</li>
    </ul>

    <h2>7. Süti (cookie) kezelés</h2>
    <p>A weboldal sütiket használ a felhasználói élmény javítása és a látogatottság elemzése céljából. A felhasználó bármikor módosíthatja a süti-beállításait böngészőjén keresztül.</p>

    <h2>8. Felhasználói jogok</h2>
    <ul>
      <li>Hozzáférés a róla kezelt adatokhoz</li>
      <li>Adatok helyesbítése vagy törlése</li>
      <li>Adatkezelés korlátozása</li>
      <li>Adathordozhatósághoz való jog</li>
      <li>Tiltakozás az adatkezelés ellen</li>
      <li>Hozzájárulás visszavonása (marketing esetén)</li>
    </ul>
    <p>Jogorvoslati lehetőség: Amennyiben a felhasználó úgy érzi, hogy adatkezelése jogsértő, panaszt tehet az <span class="highlight">Nemzeti Adatvédelmi és Információszabadság Hatóság</span> felé.</p>

    <h2>9. Kapcsolat</h2>
    <p>Adatvédelmi kérdések, panaszok esetén kérjük, lépjen kapcsolatba velünk:</p>
    <p>Email: butty.mate@diak.szbi-pg.hu</p>
    <p>Telefon: +36 30 123 4567</p>
    <p>Postai cím: Butty Máté lakása</p>
  </div>
</template>

 <style scoped>
    /* Alap tipográfia */
    :root {
      --text-color: #263238;
      --muted: #64748b;
      --accent: #2c3e50;
      --card-bg: #ffffff;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.65;
      background-color: #f9f9f9;
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

    /* Listák: jobb olvashatóság és különbség */
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

    /* Kiemelések nagyobb hangsúllyal */
    .highlight {
      font-weight: 700;
      color: #0f172a;
      background: rgba(99,102,241,0.05);
      padding: 2px 6px;
      border-radius: 6px;
    }

    /* Kapcsolati adatok vizuálisan elkülönítve */
    .container p:nth-last-of-type(-n+4) {
      /* the last 4 paragraphs include contact details in this template; style them slightly differently */
      color: #0b1220;
      margin: 6px 0;
      font-weight: 600;
    }

    /* Kisebb képernyőn megtartjuk a hierarchiát és olvashatóságot */
    @media (max-width: 600px) {
      .container {
        padding: 16px 18px;
        margin: 20px;
      }

      h1 { font-size: 22px; }
      h2 { font-size: 18px; }
      p, li { font-size: 15px; }

      .container > p:first-of-type { display: block; width: 100%; }

      /* Listák legyenek kissé nagyobb távolsággal, hogy ne olvadjanak össze */
      ul { margin-left: 18px; }
    }
  </style>