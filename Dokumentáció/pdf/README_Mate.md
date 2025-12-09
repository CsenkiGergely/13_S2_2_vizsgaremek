# 🔧 Butty Máté - Backend + Hardware Fejlesztő

## Szerepkör
Te vagy a projekt backend fejlesztője és a hardware (ESP32) integrációért is te felelsz. A feladatod a Laravel API elkészítése, amit Gergely frontend-je fog használni, valamint az ESP32-vel való kommunikáció megvalósítása a beléptetőrendszerhez.

**Technológiák amiket használnod kell:**
- Laravel 11 (PHP keretrendszer)
- PostgreSQL (Neon - adatbázis)
- Laravel Sanctum (autentikáció, tokenes belépés)
- ESP32 (mikrokontroller a kapuhoz)

---

## 📁 Mappastruktúra amit követned kell

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/    → API végpontok logikája
│   │   ├── Middleware/     → Jogosultság ellenőrzés
│   │   ├── Requests/       → Form validáció
│   │   └── Resources/      → API válasz formázás
│   ├── Models/             → Adatbázis modellek
│   └── Policies/           → Ki mit csinálhat
├── database/
│   ├── migrations/         → Adatbázis táblák
│   └── seeders/            → Teszt adatok
└── routes/
    └── api.php             → API útvonalak
```

---

# 🔴 1. HÉT - Camping API

## 1. Camping Model és Kapcsolatok

**Mi ez?**
A Camping model reprezentálja egy kemping összes adatát az adatbázisban, és meghatározza a kapcsolatait más táblákkal.

**Feladat:**
Egészítsd ki a `app/Models/Camping.php` fájlt a következő kapcsolatokkal:

| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| location | belongsTo | Melyik helyszínen van |
| spots | hasMany | Milyen helyek vannak benne |
| photos | hasMany | Képek a campingről |
| tags | belongsToMany | Címkék (WiFi, Strand, stb.) |
| comments | hasMany | Vélemények |

**Számított mezők (accessor-ok):**
- `average_rating` - Vélemények átlagos értékelése
- `reviews_count` - Vélemények száma
- `min_price` - Legolcsóbb hely ára

---

## 2. Camping Controller

**Feladat:**
Hozd létre a `app/Http/Controllers/CampingController.php` fájlt.

### Végpontok amiket meg kell valósítani:

#### `GET /api/campings` - Összes camping listázása
**Mit csinál:**
- Visszaadja az összes campinget
- Támogassa a keresést (`?search=balaton`)
- Támogassa az ár szűrést (`?min_price=5000&max_price=10000`)
- Támogassa a címke szűrést (`?tags=1,2,3`)
- Lapozás (pagination) - 12 elem oldalanként
- Minden campinghez töltse be: photos, location, tags

**Válasz formátum:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Balaton Camping",
      "description": "...",
      "location": { "city": "Siófok", "county": "Somogy" },
      "photos": [{ "url": "..." }],
      "tags": [{ "name": "Strand" }],
      "average_rating": 4.5,
      "reviews_count": 23,
      "min_price": 5000
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50
  }
}
```

#### `GET /api/campings/{id}` - Egy camping részletei
**Mit csinál:**
- Visszaadja a camping összes adatát
- Beletöltve: spots, photos, tags, comments, location
- 404 ha nem található

#### `POST /api/campings` - Új camping (admin only)
**Szükséges mezők:**
- name (kötelező, max 255 karakter)
- description (kötelező)
- location_id (kötelező, létező location)
- address (opcionális)

**Jogosultság:** Csak admin (role = true) hozhat létre

#### `PUT /api/campings/{id}` - Camping szerkesztése (admin only)
**Jogosultság:** Csak admin

#### `DELETE /api/campings/{id}` - Camping törlése (admin only)
**Jogosultság:** Csak admin, és csak ha nincs aktív foglalás

---

## 3. API Route-ok Beállítása

**Feladat:**
Add hozzá a `routes/api.php` fájlhoz:

```
Publikus (nem kell bejelentkezés):
  GET /api/campings
  GET /api/campings/{id}

Védett (bejelentkezés kell):
  POST /api/campings        (admin)
  PUT /api/campings/{id}    (admin)
  DELETE /api/campings/{id} (admin)
```

---

# 🔴 2. HÉT - CampingSpot API

## 4. CampingSpot Model

**Mi ez?**
A CampingSpot egy konkrét hely a campingben (pl. 15-ös sátorhely, A5-ös lakókocsi parcella).

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| camping | belongsTo | Melyik campinghez tartozik |
| bookings | hasMany | Ezen a helyen lévő foglalások |

**Mezők a táblában:**
- id
- camping_id
- name (pl. "A15")
- type (enum: tent, caravan, cabin, glamping)
- price_per_night
- capacity (hány fő)
- description
- is_active

---

## 5. CampingSpot Controller

**Végpontok:**

#### `GET /api/campings/{id}/spots` - Camping helyei
**Mit csinál:**
- Visszaadja a camping összes helyét
- Csak az aktív helyeket (is_active = true)

#### `GET /api/spots/{id}` - Egy hely részletei
**Mit csinál:**
- Hely adatai
- Camping adatai is benne

#### `GET /api/spots/{id}/availability` - Foglalhatóság ellenőrzése
**Paraméterek:**
- check_in (dátum)
- check_out (dátum)

**Mit csinál:**
- Megnézi, van-e foglalás erre a helyre ebben az időszakban
- Visszaadja: `{ "available": true/false }`

**Fontos logika:**
Egy hely akkor foglalt, ha van olyan booking ahol:
- `(booking.check_in < check_out) AND (booking.check_out > check_in)`
- És a booking státusza nem "cancelled"

#### `POST /api/spots` - Új hely (admin)
#### `PUT /api/spots/{id}` - Hely szerkesztése (admin)
#### `DELETE /api/spots/{id}` - Hely törlése (admin)

---

# 🔴 3. HÉT - Booking API

## 6. Booking Model

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| user | belongsTo | Ki foglalta |
| campingSpot | belongsTo | Melyik helyet |
| guests | hasMany | Vendégek adatai (UserGuest) |

**Mezők:**
- id
- user_id
- camping_spot_id
- check_in (date)
- check_out (date)
- guests_count
- total_price
- status (enum: pending, confirmed, cancelled)
- notes
- qr_code (a belépéshez)
- created_at, updated_at

---

## 7. Booking Controller

**Végpontok:**

#### `GET /api/bookings` - Saját foglalások
**Mit csinál:**
- Visszaadja a bejelentkezett felhasználó foglalásait
- Beletöltve: campingSpot, campingSpot.camping

#### `GET /api/bookings/{id}` - Foglalás részletei
**Jogosultság:** Csak saját foglalást láthatja (vagy admin)

#### `POST /api/bookings` - Új foglalás
**Szükséges mezők:**
- camping_spot_id
- check_in
- check_out
- guests_count
- notes (opcionális)

**Validáció:**
1. check_in >= ma
2. check_out > check_in
3. A hely szabad ebben az időszakban
4. guests_count <= spot.capacity

**Logika:**
1. Ellenőrizd az elérhetőséget
2. Számold ki a total_price-t: (éjszakák száma) × (spot.price_per_night)
3. Generálj QR kódot (egyedi azonosító)
4. Hozd létre a foglalást "pending" státusszal
5. Küldj email értesítést (opcionális)

#### `PUT /api/bookings/{id}` - Foglalás módosítása
**Jogosultság:** Csak saját, és csak "pending" státuszú

#### `DELETE /api/bookings/{id}` - Foglalás lemondása
**Mit csinál:**
- Nem törli, hanem "cancelled" státuszra állítja
- Csak ha check_in > ma + 1 nap (legalább 24 órával előtte)

#### `GET /api/admin/bookings` - Összes foglalás (admin)
**Szűrők:**
- status
- camping_id
- date range

---

## 8. Ár Kalkuláció

**Hogyan számolod az árat:**
```
Éjszakák száma = check_out - check_in (napokban)
Teljes ár = Éjszakák × Hely ár/éj
```

**Példa:**
- check_in: 2025-01-15
- check_out: 2025-01-18
- spot.price_per_night: 5000 Ft
- Éjszakák: 3
- Teljes ár: 15000 Ft

---

# 🟡 4. HÉT - Jogosultságok + ESP32

## 9. Jogosultságkezelés

### Admin Middleware
**Feladat:**
Hozz létre `app/Http/Middleware/AdminMiddleware.php`:
- Ellenőrizd, hogy a user `role = true`
- Ha nem admin, 403 Forbidden válasz

### Superuser Middleware
- Ellenőrizd, hogy `is_superuser = true`
- Minden camping-et kezelhet, nem csak a sajátját

### Route védelem
```
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin route-ok
});
```

---

## 10. ESP32 / Kapu Integráció

**Mi ez?**
Az ESP32 egy mikrokontroller ami a camping bejárati kapuját vezérli. A vendég QR kódját beolvassa, és ha érvényes foglalása van, kinyitja a kaput.

### EntranceGate Model
**Mezők:**
- id
- camping_id
- name (pl. "Főkapu")
- status (open/closed)
- last_opened_at
- api_key (az ESP32 ezzel azonosítja magát)

### Gate Controller Végpontok

#### `POST /api/gates/validate` - QR kód ellenőrzése
**Ki hívja:** ESP32

**Request:**
```json
{
  "api_key": "gate_secret_key",
  "qr_code": "BOOKING-ABC123"
}
```

**Mit csinál:**
1. Ellenőrizd az api_key-t (létező kapu-e)
2. Keresd meg a foglalást a qr_code alapján
3. Ellenőrizd:
   - Státusz = confirmed
   - Mai dátum a check_in és check_out között van
4. Ha minden OK: `{ "valid": true, "guest_name": "Kiss Péter" }`
5. Ha nem OK: `{ "valid": false, "reason": "Lejárt foglalás" }`

#### `POST /api/gates/{id}/open` - Kapu nyitás
**Ki hívja:** ESP32 sikeres validáció után
**Mit csinál:**
- Logold a belépést
- Frissítsd a last_opened_at mezőt

#### `GET /api/gates/{id}/status` - Kapu állapot
**Mit ad vissza:**
- Kapu nyitva/zárva
- Utolsó nyitás időpontja

### ESP32 Kommunikáció

**Az ESP32 HTTP kérést küld:**
1. Vendég beolvassa a QR kódot
2. ESP32 küld POST-ot: `/api/gates/validate`
3. Ha valid=true, ESP32 nyitja a kaput
4. ESP32 küld POST-ot: `/api/gates/{id}/open`

**Biztonsági szempontok:**
- Minden kapu egyedi api_key-jel rendelkezik
- HTTPS használata
- Rate limiting (ne lehessen spam-elni)

---

# 🟡 5. HÉT - Seeders

## 11. Teszt Adatok Létrehozása

### UserSeeder
Hozz létre:
- 1 admin felhasználó (role=true)
- 1 superuser (is_superuser=true)
- 10 normál felhasználó

### LocationSeeder
Hozz létre 10-15 helyszínt:
- Budapest, Pest megye
- Siófok, Somogy megye
- Balatonfüred, Veszprém megye
- stb.

### CampingSeeder
Hozz létre 5-10 campinget:
- Valós nevekkel és leírásokkal
- Mindegyikhez location hozzárendelve

### CampingSpotSeeder
Minden campinghez 5-10 hely:
- Vegyes típusok (sátor, lakókocsi, faház)
- Különböző árak

### TagSeeder
Címkék:
- WiFi, Strand, Kutyabarát, Családbarát, Tűzrakóhely, Játszótér, Étterem, Bolt, Mosoda, stb.

### BookingSeeder
Néhány teszt foglalás:
- Múltbeli (lezárt)
- Jelenlegi (aktív)
- Jövőbeli

### CommentSeeder
Vélemények:
- Minden campinghez 5-10 vélemény
- Különböző értékelések (1-5)

---

# 🟢 6. HÉT - Optimalizálás

## 12. Teljesítmény Javítások

### Eager Loading
**Probléma:** N+1 query probléma
**Megoldás:** Használj `with()` metódust:
```
Camping::with(['photos', 'location', 'tags'])->get()
```

### API Resource-ok
Hozz létre Resource osztályokat a válaszok formázásához:
- CampingResource
- CampingCollection
- BookingResource
- SpotResource

**Miért jó?**
- Egységes válasz formátum
- Elrejti a belső mezőket
- Könnyen módosítható

### Rate Limiting
Korlátozd az API hívások számát:
- 60 kérés / perc vendégeknek
- 120 kérés / perc bejelentkezett felhasználóknak

---

# ✅ Ellenőrzőlista

## 1. Hét
- [ ] Camping model kapcsolatokkal kész
- [ ] CampingController kész
- [ ] GET /api/campings működik keresésssel/szűréssel
- [ ] GET /api/campings/{id} működik
- [ ] POST/PUT/DELETE admin védett

## 2. Hét
- [ ] CampingSpot model kész
- [ ] Spot végpontok működnek
- [ ] Elérhetőség ellenőrzés működik

## 3. Hét
- [ ] Booking model kész
- [ ] Foglalás létrehozás validációval
- [ ] Ár kalkuláció helyes
- [ ] QR kód generálás

## 4. Hét
- [ ] Admin middleware működik
- [ ] Gate/ESP32 végpontok készek
- [ ] Validáció logika működik

## 5. Hét
- [ ] Minden seeder kész
- [ ] Teszt adatok betöltve
- [ ] Tesztelés különböző esetekre

## 6. Hét
- [ ] Eager loading mindenhol
- [ ] API Resource-ok használva
- [ ] Rate limiting beállítva
- [ ] Végső tesztelés

---

# 🔗 API Végpontok Összefoglaló

## Publikus
```
GET  /api/campings
GET  /api/campings/{id}
GET  /api/campings/{id}/spots
GET  /api/spots/{id}
GET  /api/spots/{id}/availability
GET  /api/tags
GET  /api/locations
```

## Bejelentkezés szükséges
```
GET    /api/bookings
GET    /api/bookings/{id}
POST   /api/bookings
PUT    /api/bookings/{id}
DELETE /api/bookings/{id}
GET    /api/campings/{id}/comments
POST   /api/comments
PUT    /api/comments/{id}
DELETE /api/comments/{id}
```

## Admin
```
POST   /api/campings
PUT    /api/campings/{id}
DELETE /api/campings/{id}
POST   /api/spots
PUT    /api/spots/{id}
DELETE /api/spots/{id}
GET    /api/admin/bookings
```

## ESP32 / Gate
```
POST   /api/gates/validate
POST   /api/gates/{id}/open
POST   /api/gates/{id}/close
GET    /api/gates/{id}/status
```

---

# 📚 Hasznos Dokumentációk

- **Laravel 11:** https://laravel.com/docs/11.x
- **Laravel Sanctum:** https://laravel.com/docs/11.x/sanctum
- **Eloquent Relationships:** https://laravel.com/docs/11.x/eloquent-relationships
- **ESP32 HTTP Client:** https://randomnerdtutorials.com/esp32-http-get-post-arduino/

---

Kérdés esetén keress bátran! 🚀
