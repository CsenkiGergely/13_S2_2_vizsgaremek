# Postman Teszt Gyűjtemény - CampSite API

## 📌 Alapbeállítások

**Base URL**: `http://127.0.0.1:8000/api`

**Environment Variables** (Postman-ben):
- `base_url` = `http://127.0.0.1:8000/api`
- `token` = `YOUR_TOKEN_HERE` (bejelentkezés után kapott token)

---

## 🔐 1. AUTENTIKÁCIÓ ENDPOINTS

### ✅ 1.1 Regisztráció

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

### ✅ 1.2 Bejelentkezés

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

**⚠️ FONTOS**: Másold ki a `token` értéket és állítsd be az environment változóban!

---

### ✅ 1.3 Kijelentkezés

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

### ✅ 1.4 Elfelejtett Jelszó

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

### ✅ 1.5 Jelszó Visszaállítás

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

### ✅ 1.6 Partner Státuszra Váltás

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

### ✅ 1.7 Bejelentkezett User Adatai

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

## 🏕️ 2. KEMPINGEK ENDPOINTS

### ✅ 2.1 Összes Kemping Listázása

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

### ✅ 2.2 Kemping Keresés Szűréssel

```
Method: GET
URL: {{base_url}}/campings?search=balaton&min_price=1500&max_price=4000
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 2.3 Egy Kemping Részletei

```
Method: GET
URL: {{base_url}}/campings/1
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 2.4 Kemping Helyeinek Listázása

```
Method: GET
URL: {{base_url}}/campings/1/spots
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 2.5 Kemping Elérhetőség Ellenőrzése

```
Method: GET
URL: {{base_url}}/campings/1/availability?arrival_date=2026-02-01&departure_date=2026-02-05
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 2.6 Kemping Létrehozása (Partner státusz szükséges)

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

### ✅ 2.7 Kemping Módosítása (Tulajdonos)

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

### ✅ 2.8 Kemping Törlése (Tulajdonos)

```
Method: DELETE
URL: {{base_url}}/campings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

## 🔍 3. FOGLALÁS KERESÉS

### ✅ 3.1 Kemping Keresés Foglalási Paraméterekkel

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

## 📅 4. FOGLALÁSOK ENDPOINTS

### ✅ 4.1 Saját Foglalások Listázása

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

### ✅ 4.2 Egy Foglalás Részletei

```
Method: GET
URL: {{base_url}}/bookings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

### ✅ 4.3 Új Foglalás Létrehozása

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

### ✅ 4.4 Foglalás Módosítása

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

### ✅ 4.5 Foglalás Törlése

```
Method: DELETE
URL: {{base_url}}/bookings/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

### ✅ 4.6 Foglalás QR Kód Lekérése

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

## 👨‍💼 5. TULAJDONOSI FUNKCIÓK

### ✅ 5.1 Tulajdonos Kempingjeihez Tartozó Foglalások

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

### ✅ 5.2 Foglalás Státusz Módosítása

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

### ✅ 5.3 QR Kód Beolvasása (Check-in/Check-out)

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
```

---

## 📝 6. POSTS (Teszt/Demo Endpoints)

### ✅ 6.1 Összes Post Listázása

```
Method: GET
URL: {{base_url}}/posts
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 6.2 Post Létrehozása

```
Method: POST
URL: {{base_url}}/posts
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "title": "Első bejegyzésem",
  "body": "Ez egy teszt bejegyzés tartalma."
}
```

---

### ✅ 6.3 Post Részletei

```
Method: GET
URL: {{base_url}}/posts/1
Headers:
  Accept: application/json

Body: (none)
```

---

### ✅ 6.4 Post Módosítása

```
Method: PUT
URL: {{base_url}}/posts/1
Headers:
  Content-Type: application/json
  Accept: application/json
  Authorization: Bearer {{token}}

Body (raw JSON):
{
  "title": "Módosított bejegyzés címe",
  "body": "Frissített tartalom."
}
```

---

### ✅ 6.5 Post Törlése

```
Method: DELETE
URL: {{base_url}}/posts/1
Headers:
  Accept: application/json
  Authorization: Bearer {{token}}

Body: (none)
```

---

## 🎯 TESZTELÉSI SORREND (Ajánlott)

### 1. Felhasználói folyamat:
1. ✅ **Regisztráció** → Token mentése
2. ✅ **Bejelentkezés** → Token mentése
3. ✅ **User adatok lekérése**
4. ✅ **Kempingek listázása**
5. ✅ **Egy kemping részletei**
6. ✅ **Kemping helyek**
7. ✅ **Foglalás létrehozása**
8. ✅ **Saját foglalások**
9. ✅ **Foglalás QR kód**
10. ✅ **Kijelentkezés**

### 2. Partner folyamat:
1. ✅ **Bejelentkezés**
2. ✅ **Partner státuszra váltás**
3. ✅ **Kemping létrehozása**
4. ✅ **Kemping módosítása**
5. ✅ **Tulajdonos foglalásai**
6. ✅ **Foglalás státusz változtatás**
7. ✅ **QR kód beolvasás**

### 3. Jelszó visszaállítás:
1. ✅ **Elfelejtett jelszó**
2. ✅ Email ellenőrzése
3. ✅ **Jelszó visszaállítás** (token az emailből)
4. ✅ **Bejelentkezés új jelszóval**

---

## 📦 POSTMAN COLLECTION IMPORTÁLÁS

### JSON formátum (másold be Postman-be):

```json
{
  "info": {
    "name": "CampSite API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://127.0.0.1:8000/api"
    },
    {
      "key": "token",
      "value": ""
    }
  ]
}
```

---

## 🛠️ HIBAELHÁRÍTÁS

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

---

## 💡 TIPPEK

1. **Environment Variables használata**: Állíts be `base_url` és `token` változókat
2. **Token automatikus mentése**: Használj Postman Test script-et:
   ```javascript
   var jsonData = pm.response.json();
   pm.environment.set("token", jsonData.token);
   ```
3. **Pre-request Scripts**: Automatikus dátum generálás:
   ```javascript
   pm.environment.set("arrival_date", new Date().toISOString().split('T')[0]);
   ```

---

**Utolsó frissítés**: 2026. január 27.  
**Verzió**: 1.0  
**Készítette**: Butty Máté, Csenki Gergely, Dicső András
