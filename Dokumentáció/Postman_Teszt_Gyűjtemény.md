# Postman Teszt Gyűjtemény - CampSite API


**Base URL**: `http://127.0.0.1:8000/api`

**Environment Variables** (Postman-ben):
- `base_url` = `http://127.0.0.1:8000/api`
- `token` = `YOUR_TOKEN_HERE` (bejelentkezés után kapott token)

---

## AUTENTIKÁCIÓ ENDPOINTS

### Regisztráció
```
Method: POST
URL: {{base_url}}/register
Headers:
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "name": "Teszt Felhasználó",
  "email": "teszt@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Várható válasz:**
```json
{
  "user": {
    "id": 1,
    "name": "Teszt Felhasználó",
    "email": "teszt@example.com",
    "role": 0
  },
  "token": "1|abcdef123456..."
}
```

---

### Bejelentkezés
```
Method: POST
URL: {{base_url}}/login
Headers:
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "email": "teszt@example.com",
  "password": "password123"
}
```

**Várható válasz:**
```json
{
  "user": {
    "id": 1,
    "name": "Teszt Felhasználó",
    "email": "teszt@example.com"
  },
  "token": "2|xyz789..."
}

```

### Kijelentkezés
```
Method: POST
URL: {{base_url}}/logout
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

**Várható válasz:**
```json
{
  "message": "Logged out"
}
```

---

### Elfelejtett Jelszó
```
Method: POST
URL: {{base_url}}/forgot-password
Headers:
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "email": "teszt@example.com"
}
```

**Várható válasz:**
```json
{
  "message": "Jelszó visszaállító linket elküldtük az email címedre."
}
```

---

### Jelszó Visszaállítás
```
Method: POST
URL: {{base_url}}/reset-password
Headers:
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "email": "teszt@example.com",
  "token": "a1b2c3d4e5f6...",
  "password": "ujjelszo123",
  "password_confirmation": "ujjelszo123"
}
```

**Várható válasz:**
```json
{
  "message": "Jelszó sikeresen megváltoztatva!"
}
```

---

### Partner Státuszra Váltás
```
Method: POST
URL: {{base_url}}/upgrade-to-partner
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "phone_number": "+36301234567"
}
```

**Várható válasz:**
```json
{
  "message": "Sikeresen partner státuszra váltottál!",
  "user": {
    "id": 1,
    "name": "Teszt Felhasználó",
    "email": "teszt@example.com",
    "phone_number": "+36301234567",
    "role": 1
  }
}
```

---

### Bejelentkezett User Adatai
```
Method: GET
URL: {{base_url}}/user
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

**Várható válasz:**
```json
{
  "id": 1,
  "name": "Teszt Felhasználó",
  "email": "teszt@example.com",
  "role": 0,
  "created_at": "2026-01-27T10:00:00.000000Z"
}
```

---

## KEMPINGEK ENDPOINTS

### Összes Kemping Listázása
```
Method: GET
URL: {{base_url}}/campings
Headers:
  Accept: application/json

Body: (none)
```

**Query paraméterek (opcionális):**
```
{{base_url}}/campings?search=balaton&min_price=1500&max_price=4000&page=1
```

---

### Kemping Keresés Szűréssel
```
Method: GET
URL: {{base_url}}/campings?search=balaton&min_price=1500&max_price=4000
Headers:
  Accept: application/json

Body: (none)
```

---

### Egy Kemping Részletei
```
Method: GET
URL: {{base_url}}/campings/1
Headers:
  Accept: application/json

Body: (none)
```

---

### Kemping Helyeinek Listázása
```
Method: GET
URL: {{base_url}}/campings/1/spots
Headers:
  Accept: application/json

Body: (none)
```

---

### Kemping Elérhetőség Ellenőrzése
```
Method: GET
URL: {{base_url}}/campings/1/availability?arrival_date=2026-02-01&departure_date=2026-02-05
Headers:
  Accept: application/json

Body: (none)
```

---

### Kemping Létrehozása (Partner státusz szükséges)
```
Method: POST
URL: {{base_url}}/campings
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "camping_name": "Balatoni Camping Paradicsom",
  "description": "Csodálatos kilátással a Balatonra",
  "address": "Balatonfüred, Strand utca 12",
  "city": "Balatonfüred",
  "postal_code": "8230",
  "country": "Magyarország",
  "latitude": 46.9578,
  "longitude": 17.8893,
  "amenities": ["wifi", "strand", "étterem"],
  "check_in_time": "14:00:00",
  "check_out_time": "10:00:00"
}
```

---

### Kemping Módosítása (Tulajdonos)
```
Method: PUT
URL: {{base_url}}/campings/1
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "camping_name": "Balatoni Camping Paradicsom - Frissítve",
  "description": "Még jobb leírás",
  "check_in_time": "15:00:00"
}
```

---

### Kemping Törlése (Tulajdonos)
```
Method: DELETE
URL: {{base_url}}/campings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

## FOGLALÁS KERESÉS

### Kemping Keresés Foglalási Paraméterekkel
```
Method: GET
URL: {{base_url}}/booking/search?location=Balaton&arrival_date=2026-02-01&departure_date=2026-02-05&guests=4
Headers:
  Accept: application/json

Body: (none)
```

**Query paraméterek:**
- `location`: Helyszín (pl. "Balaton", "Budapest")
- `arrival_date`: Érkezési dátum (YYYY-MM-DD formátum)
- `departure_date`: Távozási dátum (YYYY-MM-DD formátum)
- `guests`: Vendégek száma

---

## FOGLALÁSOK ENDPOINTS

### Saját Foglalások Listázása
```
Method: GET
URL: {{base_url}}/bookings
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

**Lapozással:**
```
{{base_url}}/bookings?page=1
```

---

### Egy Foglalás Részletei
```
Method: GET
URL: {{base_url}}/bookings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

### Új Foglalás Létrehozása
```
Method: POST
URL: {{base_url}}/bookings
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "camping_id": 1,
  "camping_spot_id": 5,
  "arrival_date": "2026-02-01",
  "departure_date": "2026-02-05",
  "guests": 4
}
```

**Várható válasz:**
```json
{
  "message": "Foglalás sikeresen létrehozva!",
  "booking": {
    "id": 1,
    "camping_id": 1,
    "camping_spot_id": 5,
    "user_id": 1,
    "arrival_date": "2026-02-01",
    "departure_date": "2026-02-05",
    "guests": 4,
    "total_price": 16000,
    "status": "pending",
    "qr_code": "base64_encoded_qr_code..."
  }
}
```

---

### Foglalás Módosítása
```
Method: PUT
URL: {{base_url}}/bookings/1
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "arrival_date": "2026-02-03",
  "departure_date": "2026-02-07",
  "guests": 3
}
```

---

### Foglalás Törlése
```
Method: DELETE
URL: {{base_url}}/bookings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

### Foglalás QR Kód Lekérése
```
Method: GET
URL: {{base_url}}/bookings/1/qr-code
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

**Várható válasz:**
```json
{
  "qr_code": "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDov...",
  "booking_id": 1
}
```

---

## TULAJDONOSI FUNKCIÓK

### Tulajdonos Kempingjeihez Tartozó Foglalások
```
Method: GET
URL: {{base_url}}/owner/bookings
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

**Query paraméter (opcionális):**
```
{{base_url}}/owner/bookings?camping_id=1
```

---

### Foglalás Státusz Módosítása
```
Method: PATCH
URL: {{base_url}}/bookings/1/status
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "status": "confirmed"
}
```

**Lehetséges státuszok:**
- `pending` - Függőben
- `confirmed` - Megerősítve
- `cancelled` - Törölve
- `completed` - Befejezve

---

### QR Kód Beolvasása (Check-in/Check-out) még nem jó
```
Method: POST
URL: {{base_url}}/bookings/scan
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "qr_code": "encoded_booking_data_from_qr_code"
}
```

**Várható válasz:**
```json
{
  "message": "Sikeres check-in!",
  "booking": {
    "id": 1,
    "status": "checked_in",
    "check_in_time": "2026-02-01T14:30:00.000000Z"
  }
}

---

## HIBAELHÁRÍTÁS

### 401 Unauthorized
- Ellenőrizd, hogy a token helyesen van-e beállítva
- Ellenőrizd, hogy a token nem járt-e le
- Próbálj meg újra bejelentkezni

### 403 Forbidden
- Nincs jogosultságod az adott művelethez
- Partner státusz szükséges

### 404 Not Found
- Rossz endpoint URL
- Az erőforrás (kemping, foglalás, stb.) nem létezik

### 422 Validation Error
- Ellenőrizd a request body mezőit
- Minden kötelező mező ki van töltve?
- Az email formátum helyes?
- A dátumok megfelelő formátumban vannak?

### 500 Server Error
- Backend hiba
- Ellenőrizd a Laravel log fájlokat: `storage/logs/laravel.log`
```
