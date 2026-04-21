<template>
 <div class="container">
    <h1>Felhasználási Feltételek</h1>
    <p><span class="highlight">Hatályos:</span> 2026. január 30-tól</p>

    <h2>1. Általános rendelkezések</h2>
    <p>Ez a weboldal CampSite használatával Ön elfogadja a jelen felhasználási feltételeket. Kérjük, olvassa el figyelmesen, mielőtt igénybe veszi szolgáltatásainkat.</p>

    <h2>2. Szolgáltatás leírása</h2>
    <p>Az oldal lehetővé teszi a camping helyek online foglalását, információk megtekintését és fizetési folyamatok lebonyolítását.</p>

    <h2>3. Regisztráció és felhasználói fiók</h2>
    <ul>
      <li>A fiók létrehozásához valós adatokat kell megadni.</li>
      <li>Felhasználónév és jelszó titkos kezelése a felhasználó felelőssége.</li>
      <li>Az oldal nem vállal felelősséget a felhasználói fiók jogosulatlan használatáért, ha a felhasználó adatai nem megfelelően védettek.</li>
    </ul>

    <h2>4. Foglalások és fizetés</h2>
    <ul>
      <li>A foglalás a weboldalon keresztül történik, a visszaigazolás után érvényes.</li>
      <li>Az esetleges lemondások vagy módosítások az oldal szabályzata szerint történnek.</li>
      <li>A fizetés biztonságos fizetési szolgáltató segítségével zajlik.</li>
    </ul>

    <h2>5. Felhasználói tartalom</h2>
    <p>Felhasználó felelős az általa megadott tartalomért (értékelések, üzenetek, fotók). Tilos jogsértő, sértő vagy spam tartalom közzététele.</p>

    <h2>6. Felelősség és korlátozás</h2>
    <p>Az oldal mindent megtesz a szolgáltatás zavartalan működéséért, de nem vállal felelősséget technikai hibák, adatvesztés vagy harmadik fél szolgáltatásai miatt bekövetkező károkért.</p>

    <h2>7. Szellemi tulajdon</h2>
    <p>A weboldal tartalma, logók, grafikák és szövegek szerzői jogvédelem alatt állnak. Másolás vagy kereskedelmi felhasználás csak a Szolgáltató engedélyével lehetséges.</p>

    <h2>8. Módosítások</h2>
    <p>A Szolgáltató fenntartja a jogot, hogy a felhasználási feltételeket időről időre módosítsa. A változtatások a weboldalon történő közzétételkor lépnek hatályba.</p>

    <h2>9. Kapcsolat</h2>
    <p>Felhasználási feltételekkel kapcsolatos kérdések esetén kérjük, lépjen kapcsolatba velünk:</p>
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
