# 🎨 Csenki Gergely - Frontend Fejlesztő

## Szerepkör
Te vagy a projekt frontend fejlesztője. A feladatod a Vue.js alapú felhasználói felület elkészítése, ami kommunikál a Máté által készített backend API-val.

**Technológiák amiket használnod kell:**
- Vue.js 3 (Composition API)
- Vue Router (oldalak közti navigáció)
- Axios (API hívások a backendhez)
- CSS vagy SCSS (stílusok)

---

## 📁 Mappastruktúra amit követned kell

```
frontend/src/
├── views/           → Teljes oldalak (pl. Home.vue, Login.vue)
├── components/      → Kisebb, újrahasználható elemek (pl. CampingCard.vue)
├── composables/     → API hívások és közös logika
├── router/          → URL útvonalak beállítása
└── assets/          → Képek, ikonok, stílusfájlok
```

---

# 🔴 1. HÉT - Alapok és Főoldal

## 1. Router (Útvonalak) Beállítása

**Mi ez?**
A router határozza meg, hogy melyik URL-en melyik oldal jelenjen meg.

**Feladat:**
Hozd létre a `src/router/index.js` fájlt és állítsd be az alábbi útvonalakat:

| URL | Oldal | Leírás |
|-----|-------|--------|
| `/` | Home.vue | Főoldal, camping lista |
| `/camping/:id` | CampingDetail.vue | Egy camping részletei |
| `/booking/:spotId` | Booking.vue | Foglalási folyamat |
| `/profile` | Profile.vue | Felhasználói profil |
| `/login` | Login.vue | Bejelentkezés |
| `/register` | Register.vue | Regisztráció |
| `/admin` | admin/Dashboard.vue | Admin felület |

**Plusz feladat:**
- Ha valaki nincs bejelentkezve és `/profile`-ra megy, irányítsd át `/login`-ra
- Ezt "navigation guard"-nak hívják Vue Router-ben

---

## 2. API Kommunikáció Beállítása

**Mi ez?**
Létre kell hozni egy központi helyet, ahonnan az összes API hívást intézed a backend felé.

**Feladat:**
Hozz létre egy `src/composables/useApi.js` fájlt:

- Állítsd be az alap URL-t (pl. `http://localhost:8000/api`)
- Minden kérésnél automatikusan küldd el a bejelentkezési tokent (ha van)
- A token a `localStorage`-ban van `token` néven

---

## 3. Camping Lista Lekérdezése

**Feladat:**
Hozz létre egy `src/composables/useCampings.js` fájlt ami:

- Le tudja kérni az összes campinget: `GET /api/campings`
- Le tudja kérni egy camping részleteit: `GET /api/campings/{id}`
- Kezelje a töltés állapotot (loading)
- Kezelje a hibákat (error)
- Támogassa a keresést és szűrést (query paraméterekkel)

---

## 4. Főoldal (Home.vue)

**Mit kell tartalmaznia:**

### Fejléc szekció
- Nagy, figyelemfelkeltő cím (pl. "Találd meg a tökéletes kempinget!")
- Keresőmező ahol név vagy helyszín alapján lehet keresni
- Szűrők:
  - Ár (minimum - maximum)
  - Címkék (WiFi, Strand, Kutyabarát, stb.) - checkboxok
  - Értékelés szerinti szűrés

### Camping lista
- Rácsos elrendezés (grid) - 3 oszlop asztali gépen, 1 oszlop mobilon
- Minden camping egy kártya formájában jelenik meg
- Ha töltődik, mutass "Betöltés..." feliratot
- Ha hiba van, mutass hibaüzenetet és "Újrapróbálás" gombot
- Ha nincs találat, írd ki hogy "Nincs találat"

### Keresés működése
- Ne küldjön kérést minden betűnél, várjon 300ms-ot (debounce)
- A szűrők változásakor automatikusan frissüljön a lista

---

## 5. Camping Kártya Komponens (CampingCard.vue)

**Mit kell megjelenítenie:**
- Camping főképe (ha nincs kép, mutass placeholder-t)
- Camping neve
- Helyszín (város, megye)
- Értékelés csillagokkal (1-5)
- Vélemények száma
- Legalacsonyabb ár ("X Ft/éj"-től)
- 2-3 címke (pl. WiFi, Strand)

**Működés:**
- Kattintásra navigáljon a camping részletes oldalára
- Hover effekt (pl. kicsit felemelkedik, árnyék nő)

---

## 6. Csillag Értékelés Komponens (StarRating.vue)

**Egyszerű komponens ami:**
- Kap egy számot (pl. 4.2)
- Megjelenít 5 csillagot, ebből annyi legyen színes ahány az értékelés
- Újrahasználható lesz több helyen

---

# 🔴 2. HÉT - Camping Részletek

## 7. Camping Részletes Oldal (CampingDetail.vue)

**Ez az oldal jelenik meg ha valaki rákattint egy campingre.**

### Képgaléria
- Több kép megjelenítése
- Kattintásra nagyobb nézet (lightbox)
- Navigálás a képek között (nyilak vagy pontok)

### Alap információk
- Camping neve (nagy címként)
- Helyszín ikonnal (📍 Budapest, Pest megye)
- Értékelés csillagokkal és vélemények száma
- Összes címke megjelenítése

### Leírás szekció
- "A kempingről" cím
- Teljes leírás szöveg

### Elérhető helyek szekció
- "Elérhető helyek" cím
- Lista az összes helyről (CampingSpot)
- Minden helynél:
  - Hely típusa (sátor, lakókocsi, faház)
  - Ár/éjszaka
  - Kapacitás (hány fő)
  - "Foglalás" gomb

### Vélemények szekció
- "Vélemények" cím
- Vélemények listája (CommentList komponens)
- Ha be van jelentkezve: vélemény írása form
- Ha nincs bejelentkezve: "Jelentkezz be vélemény írásához" link

### Oldalsó foglalás widget (jobb oldalt, ragadós)
- "Foglalás" cím
- Legalacsonyabb ár megjelenítése
- "Foglalás" gomb ami a foglalási oldalra visz

---

## 8. Képgaléria Komponens (ImageGallery.vue)

**Funkciók:**
- Főkép nagy méretben
- Alatta kis előnézeti képek (thumbnails)
- Kattintásra a kis kép lesz a nagy
- Lightbox: kattintásra teljes képernyős nézet
- Balra/jobbra navigáció

---

## 9. Hely Kártya Komponens (SpotCard.vue)

**Egy camping hely (sátor/lakókocsi/faház hely) megjelenítése:**
- Hely neve és típusa
- Ár/éjszaka
- Kapacitás
- Rövid leírás
- "Foglalás" gomb

---

## 10. Vélemény Lista Komponens (CommentList.vue)

**Vélemények megjelenítése:**
- Felhasználó neve
- Dátum
- Értékelés (csillagok)
- Vélemény szövege
- Ha saját vélemény: szerkesztés/törlés gombok

---

## 11. Vélemény Form Komponens (CommentForm.vue)

**Új vélemény írása:**
- Értékelés választó (1-5 csillag, kattintható)
- Szöveges mező a véleménynek
- "Küldés" gomb
- Beküldés után frissüljön a lista

---

# 🔴 3. HÉT - Foglalási Folyamat

## 12. Foglalás Composable (useBookings.js)

**API hívások foglalásokhoz:**
- Új foglalás létrehozása: `POST /api/bookings`
- Saját foglalások lekérdezése: `GET /api/bookings`
- Foglalás lemondása: `DELETE /api/bookings/{id}`
- Elérhetőség ellenőrzése: `GET /api/spots/{id}/availability`

---

## 13. Foglalás Oldal (Booking.vue)

**Lépésekre bontott foglalási folyamat:**

### Lépés jelző
- Vizuális jelzés hogy hányadik lépésnél tart (1. Dátum → 2. Vendégek → 3. Összegzés)
- Aktív lépés kiemelve

### 1. Lépés: Dátum választás
- Érkezés dátum mező (date picker)
- Távozás dátum mező
- Minimum dátum: mai nap
- Távozás minimum: érkezés + 1 nap
- Elérhetőség ellenőrzése: ha foglalt, hibaüzenet
- "Tovább" gomb

### 2. Lépés: Vendégek
- Felnőttek száma (+/- gombokkal)
- Gyerekek száma (+/- gombokkal)
- Megjegyzés mező (opcionális)
- "Vissza" és "Tovább" gombok

### 3. Lépés: Összegzés
- Kiválasztott hely neve
- Dátumok (érkezés - távozás)
- Éjszakák száma (automatikusan számolva)
- Vendégek száma
- **Végösszeg** (éjszakák × ár/éj)
- "Vissza" és "Foglalás megerősítése" gombok

### Sikeres foglalás
- ✅ Sikeres üzenet
- Foglalás azonosító megjelenítése
- "Foglalásaim megtekintése" gomb

---

# 🟡 4. HÉT - Profil Oldal

## 14. Profil Oldal (Profile.vue)

### Személyes adatok szekció
- Név megjelenítése
- Email megjelenítése
- Telefonszám megjelenítése
- "Adatok szerkesztése" gomb → modal vagy külön form

### Jelszó módosítás
- Jelenlegi jelszó mező
- Új jelszó mező
- Új jelszó megerősítése
- "Jelszó módosítása" gomb

### Foglalásaim szekció
- Táblázat vagy kártya lista a foglalásokról
- Minden foglalásnál:
  - Camping neve
  - Hely típusa
  - Dátumok
  - Státusz (aktív/múltbeli/lemondott)
  - Összeg
  - "Részletek" gomb
  - "Lemondás" gomb (ha még aktív és van rá idő)

### Foglalás részletek modal
- Részletes információk a foglalásról
- QR kód (ha van, a belépéshez)

---

# 🟡 5. HÉT - Admin Panel

## 15. Admin Dashboard (admin/Dashboard.vue)

**Csak admin felhasználóknak elérhető!**

### Statisztikák
- Összes camping száma
- Összes foglalás száma
- Mai bejelentkezések
- Bevétel összesen

### Gyors linkek
- Campingek kezelése
- Foglalások kezelése
- Felhasználók kezelése

---

## 16. Camping Lista Admin (admin/CampingList.vue)

### Táblázat az összes campinggel
- ID
- Név
- Helyszín
- Helyek száma
- Aktív foglalások
- Műveletek (szerkesztés, törlés)

### Funkciók
- Keresés
- Szűrés státusz szerint
- "Új camping" gomb
- Pagination (lapozás)

---

## 17. Camping Szerkesztés (admin/CampingEdit.vue)

### Form mezők
- Név
- Leírás (hosszú szöveg)
- Helyszín választó
- Címkék kiválasztása (multi-select)
- Képek feltöltése (drag & drop)
- Helyek hozzáadása/szerkesztése

---

## 18. Foglalások Kezelése (admin/BookingList.vue)

### Táblázat
- Foglalás ID
- Vendég neve
- Camping
- Dátumok
- Státusz
- Összeg
- Műveletek

### Szűrők
- Státusz szerint (pending, confirmed, cancelled)
- Dátum szerint
- Camping szerint

---

# 🟢 6. HÉT - Finomítások

## 19. Reszponzív Design

**Minden oldal működjön mobilon is:**
- Hamburger menü mobilon
- Egy oszlopos elrendezés kis képernyőn
- Touch-barát gombok (elég nagyok)
- Képek megfelelő méretezése

---

## 20. UX Fejlesztések

### Loading állapotok
- Spinner vagy skeleton komponensek betöltés közben

### Toast üzenetek
- Sikeres műveleteknél zöld üzenet
- Hibáknál piros üzenet
- Automatikusan eltűnik 3 másodperc után

### Form validáció
- Hibás mezők piros kerettel
- Hibaüzenet a mező alatt
- Gomb letiltva ha a form nem valid

### 404 oldal
- Szép "Oldal nem található" üzenet
- Vissza a főoldalra link

---

# ✅ Ellenőrzőlista

## 1. Hét
- [ ] Router beállítva, minden útvonal működik
- [ ] API kommunikáció beállítva (useApi.js)
- [ ] useCampings.js kész
- [ ] Főoldal megjelenik
- [ ] Camping kártyák renderelődnek
- [ ] Keresés működik

## 2. Hét
- [ ] Camping részletes oldal kész
- [ ] Képgaléria működik
- [ ] Helyek megjelennek
- [ ] Vélemények megjelennek
- [ ] Vélemény írása működik

## 3. Hét
- [ ] useBookings.js kész
- [ ] Foglalási folyamat 3 lépésben
- [ ] Dátum választás működik
- [ ] Elérhetőség ellenőrzés működik
- [ ] Ár kalkuláció helyes
- [ ] Sikeres foglalás visszajelzés

## 4. Hét
- [ ] Profil oldal kész
- [ ] Foglalások listája működik
- [ ] Foglalás lemondás működik

## 5. Hét
- [ ] Admin dashboard kész
- [ ] Camping CRUD működik
- [ ] Foglalások kezelése működik

## 6. Hét
- [ ] Minden oldal reszponzív
- [ ] Loading állapotok mindenhol
- [ ] Toast üzenetek működnek
- [ ] Tesztelés kész

---

# 🔗 Kommunikáció a Backend-del

**Máté készíti a backend API-t. Ezeket a végpontokat fogod használni:**

| Végpont | Mire kell |
|---------|-----------|
| `GET /api/campings` | Főoldal - camping lista |
| `GET /api/campings/{id}` | Camping részletek |
| `GET /api/campings/{id}/spots` | Elérhető helyek |
| `GET /api/spots/{id}/availability` | Szabad-e a hely |
| `POST /api/bookings` | Új foglalás |
| `GET /api/bookings` | Saját foglalásaim |
| `DELETE /api/bookings/{id}` | Foglalás lemondás |
| `GET /api/campings/{id}/comments` | Vélemények |
| `POST /api/comments` | Új vélemény |

**Ha valamelyik végpontra szükséged van és még nincs kész, szólj Máténak!**

---

# 📚 Hasznos Dokumentációk

- **Vue.js 3:** https://vuejs.org/guide/introduction.html
- **Vue Router:** https://router.vuejs.org/
- **Axios:** https://axios-http.com/docs/intro

---

Kérdés esetén keress bátran! 🚀
