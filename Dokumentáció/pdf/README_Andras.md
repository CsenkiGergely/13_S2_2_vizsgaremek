# 🌐 Dicső András - Backend Kiegészítő

## Szerepkör
Te vagy a projekt kiegészítő backend fejlesztője. A feladatod a kisebb backend funkciók elkészítése (vélemények, képek, címkék, helyszínek, vendégek).

**Technológiák amiket használnod kell:**
- Laravel 11 (backend)
- PHP 8.2
- PostgreSQL (adatbázis)
- Git (verziókezelés)

---

## 📁 Mappastruktúra amit követned kell

```
backend/app/Http/Controllers/
├── CommentController.php      → Te csinálod
├── CampingPhotoController.php → Te csinálod
├── CampingTagController.php   → Te csinálod
├── LocationController.php     → Te csinálod
└── UserGuestController.php    → Te csinálod
```

---

# 🔴 1. HÉT - Comment (Vélemény) API

## 1. Comment Model

**Mi ez?**
A felhasználók véleményeket írhatnak a campingekről, amihez értékelést (1-5 csillag) is adnak.

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| user | belongsTo | Ki írta a véleményt |
| camping | belongsTo | Melyik campingről szól |

**Mezők:**
- id
- user_id
- camping_id
- rating (1-5 integer)
- content (szöveg)
- created_at, updated_at

---

## 2. Comment Controller

### Végpontok:

#### `GET /api/campings/{id}/comments` - Camping véleményei
**Mit csinál:**
- Visszaadja az adott camping összes véleményét
- Újabb vélemények elöl (created_at desc)
- Minden véleményhez: user neve, rating, content, dátum

**Válasz formátum:**
```json
{
  "data": [
    {
      "id": 1,
      "user": { "name": "Kiss Péter" },
      "rating": 5,
      "content": "Nagyon jó camping, ajánlom!",
      "created_at": "2025-01-10"
    }
  ]
}
```

#### `POST /api/comments` - Új vélemény írása
**Ki használhatja:** Bejelentkezett felhasználók

**Szükséges mezők:**
- camping_id (kötelező, létező camping)
- rating (kötelező, 1-5 közötti szám)
- content (kötelező, min 10 karakter)

**Validáció:**
- Egy felhasználó egy campinghez csak egy véleményt írhat
- Ha már írt, 422 hiba: "Már írtál véleményt erről a campingről"

#### `PUT /api/comments/{id}` - Vélemény szerkesztése
**Ki használhatja:** 
- A vélemény írója VAGY
- Admin felhasználó

**Szerkeszthető:**
- rating
- content

#### `DELETE /api/comments/{id}` - Vélemény törlése
**Ki használhatja:**
- A vélemény írója VAGY
- Admin felhasználó

**Mit csinál:**
- Törli a véleményt az adatbázisból (hard delete)

---

# 🔴 1-2. HÉT - CampingPhoto API

## 3. CampingPhoto Model

**Mi ez?**
Egy camping több képpel rendelkezhet. Ezeket feltöltik az adminok.

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| camping | belongsTo | Melyik campinghez tartozik |

**Mezők:**
- id
- camping_id
- path (fájl elérési útja)
- is_main (boolean - ez-e a főkép)
- order (sorrend)
- created_at

---

## 4. CampingPhoto Controller

### Végpontok:

#### `GET /api/campings/{id}/photos` - Camping képei
**Mit csinál:**
- Visszaadja a camping összes képét
- Főkép elöl, utána order szerint rendezve

**Válasz:**
```json
{
  "data": [
    {
      "id": 1,
      "url": "https://example.com/storage/campings/1/photo1.jpg",
      "is_main": true
    }
  ]
}
```

#### `POST /api/photos` - Kép feltöltése (admin)
**Ki használhatja:** Admin

**Request:** multipart/form-data
- camping_id
- photo (fájl - jpg, png, webp)
- is_main (optional, boolean)

**Mit csinál:**
1. Validáld a fájlt (max 5MB, csak kép)
2. Generálj egyedi fájlnevet
3. Mentsd a `storage/app/public/campings/{camping_id}/` mappába
4. Ha is_main=true, állítsd a többi kép is_main-jét false-ra
5. Hozd létre a CampingPhoto rekordot

**Kép átméretezés (opcionális de hasznos):**
- Készíts thumbnail-t is (pl. 300x200)
- Használd az Intervention Image csomagot

#### `DELETE /api/photos/{id}` - Kép törlése (admin)
**Mit csinál:**
- Töröld a fájlt a szerverről
- Töröld a rekordot az adatbázisból

#### `PUT /api/photos/{id}/main` - Főkép beállítása (admin)
**Mit csinál:**
- Az adott képet állítsd főképnek
- A többi kép is_main = false

---

## 5. Storage Beállítása

**Feladat:**
Állítsd be, hogy a feltöltött képek publikusan elérhetőek legyenek.

**Lépések:**
1. Futtasd: `php artisan storage:link`
2. Ez létrehozza: `public/storage` → `storage/app/public` szimlinket
3. A képek elérhetőek lesznek: `http://domain.com/storage/campings/1/photo.jpg`

---

# 🟡 3. HÉT - Tag és Location API

## 6. CampingTag Model

**Mi ez?**
Címkék amik jellemzik a campinget (pl. WiFi, Strand, Kutyabarát).

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| campings | belongsToMany | Mely campingekhez tartozik |

Ez egy many-to-many kapcsolat, kell hozzá pivot tábla: `camping_tag`

**Mezők (camping_tags tábla):**
- id
- name
- icon (opcionális, pl. "wifi", "beach")

---

## 7. CampingTag Controller

### Végpontok:

#### `GET /api/tags` - Összes címke
**Ki használhatja:** Bárki (publikus)

**Mire kell:** Frontend szűrőkhöz

**Válasz:**
```json
{
  "data": [
    { "id": 1, "name": "WiFi", "icon": "wifi" },
    { "id": 2, "name": "Strand", "icon": "beach" },
    { "id": 3, "name": "Kutyabarát", "icon": "dog" }
  ]
}
```

#### `POST /api/tags` - Új címke (admin)
**Mezők:**
- name (kötelező, unique)
- icon (opcionális)

#### `DELETE /api/tags/{id}` - Címke törlése (admin)
**Mit csinál:**
- Törli a címkét
- A pivot táblából is törlődik (cascade)

#### `POST /api/campings/{id}/tags` - Címkék hozzárendelése (admin)
**Request:**
```json
{
  "tag_ids": [1, 2, 5]
}
```

**Mit csinál:**
- Szinkronizálja a camping címkéit
- Törli a régieket, hozzáadja az újakat
- Használd a `sync()` metódust

---

## 8. Location Model

**Mi ez?**
Helyszínek (városok) ahol campingek találhatóak.

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| campings | hasMany | Ezen a helyen lévő campingek |

**Mezők:**
- id
- city (város neve)
- county (megye)
- country (ország, default: Magyarország)
- latitude (GPS, opcionális)
- longitude (GPS, opcionális)

---

## 9. Location Controller

### Végpontok:

#### `GET /api/locations` - Összes helyszín
**Ki használhatja:** Bárki

**Mire kell:** Camping szűréshez, legördülő listához

**Válasz:**
```json
{
  "data": [
    { "id": 1, "city": "Siófok", "county": "Somogy" },
    { "id": 2, "city": "Balatonfüred", "county": "Veszprém" }
  ]
}
```

#### `GET /api/locations/{id}` - Helyszín részletei
**Mit ad vissza:**
- Helyszín adatai
- Ezen a helyen lévő campingek száma

#### `POST /api/locations` - Új helyszín (admin)
**Mezők:**
- city (kötelező)
- county (kötelező)
- country (opcionális, default: Magyarország)
- latitude, longitude (opcionális)

---

## 10. UserGuest Model és Controller

**Mi ez?**
Egy foglaláshoz tartozó vendégek adatai (nem felhasználók, hanem a foglaláshoz megadott személyek).

**Kapcsolatok:**
| Kapcsolat | Típus | Leírás |
|-----------|-------|--------|
| booking | belongsTo | Melyik foglaláshoz tartozik |

**Mezők:**
- id
- booking_id
- name (vendég neve)
- birth_date (opcionális)
- id_number (személyi szám, opcionális)

### Végpontok:

#### `GET /api/bookings/{id}/guests` - Foglalás vendégei
#### `POST /api/guests` - Vendég hozzáadása
#### `PUT /api/guests/{id}` - Vendég szerkesztése
#### `DELETE /api/guests/{id}` - Vendég törlése

---
- Server block a domain-hez
- Root mappa: `/var/www/campsite/frontend/dist` (Vue build)
- PHP kérések továbbítása a Laravel-hez
- `/api` útvonal → Laravel backend
- Minden más → Vue frontend (SPA routing)
- Gzip tömörítés bekapcsolva

**Lépések:**
1. Hozd létre a config fájlt
2. Symlink: `ln -s /etc/nginx/sites-available/campsite /etc/nginx/sites-enabled/`
3. Teszteld: `nginx -t`
4. Újraindítás: `systemctl restart nginx`

---

## 14. SSL Tanúsítvány (HTTPS)

**Mi ez?**
A Let's Encrypt ingyenes SSL tanúsítványt ad, ami titkosítja a forgalmat.

**Feladat:**
1. Telepítsd a certbot-ot
2. Futtasd: `certbot --nginx -d yourdomain.com`
3. Automatikus megújítás beállítása

**Fontos:**
- A domain DNS-ének már a szerverre kell mutatnia
- Először állítsd be a DNS-t, várj 10-15 percet, utána futtasd a certbot-ot

---

## 15. Laravel Deployment

### Fájlok feltöltése
**Opció 1: Git clone (ajánlott)**
```
cd /var/www
git clone https://github.com/CsenkiGergely/13_S2_2_vizsgaremek.git campsite
```

**Opció 2: Manuális feltöltés (SFTP)**

### Backend beállítás

#### 1. Composer függőségek
```
cd /var/www/campsite/backend
composer install --no-dev --optimize-autoloader
```

#### 2. Environment fájl
- Másold az `.env.example`-t `.env`-re
- Állítsd be:
  - APP_ENV=production
  - APP_DEBUG=false
  - APP_URL=https://yourdomain.com
  - DB_CONNECTION=pgsql
  - DB_HOST=... (Neon adatbázis)
  - stb.

#### 3. App key generálás
```
php artisan key:generate
```

#### 4. Cache-ek
```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5. Migrációk
```
php artisan migrate --force
```

#### 6. Storage link
```
php artisan storage:link
```

#### 7. Jogosultságok
```
chown -R www-data:www-data /var/www/campsite/backend/storage
chown -R www-data:www-data /var/www/campsite/backend/bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 16. Frontend Deployment

### Lokálisan (a saját gépeden):
```
cd frontend
npm install
npm run build
```

Ez létrehozza a `dist` mappát.

### Szerveren:
A `dist` mappa tartalmát töltsd fel ide:
`/var/www/campsite/frontend/dist/`

**Alternatíva:**
A build-et a szerveren is csinálhatod, de akkor Node.js kell a szerverre.

---

## 17. Domain Beállítása

**Lépések:**
1. Vásárolj/szerezz domain-t (vagy használj ingyenes subdomain-t)
2. A domain szolgáltatónál állítsd be az A rekordot:
   - `@` → szerver IP
   - `www` → szerver IP
3. Várj 10-30 percet a DNS propagációra
4. Teszteld: `ping yourdomain.com`

---

## 18. Email Küldés Beállítása (Opcionális)

**Mire kell?**
- Foglalás visszaigazolás
- Jelszó visszaállítás

**Lehetőségek:**
1. **MailerSend** (már be van állítva a .env-ben)
2. **Mailgun**
3. **SendGrid**

**Laravel beállítás:**
A `.env` fájlban állítsd be a MAIL_* változókat.

---

# 🟢 4. HÉT - API Dokumentáció és Tesztelés

## 11. API Dokumentáció

**Feladat:**
Készíts egy egyszerű dokumentációt az API végpontokról.

**Lehetőségek:**
1. **Postman Collection** - exportáld és oszd meg
2. **README.md** - egyszerű markdown dokumentáció

**Mit tartalmazzon:**
- Minden végpont URL-je
- HTTP metódus (GET, POST, PUT, DELETE)
- Szükséges paraméterek
- Példa request és response
- Autentikáció szükséges-e

---

## 12. Tesztelés

**Feladat:**
Teszteld le az összes általad készített végpontot Postman-nel vagy Thunder Client-tel.

**Ellenőrizd:**
- Sikeres válaszok működnek-e
- Hibakezelés működik-e (rossz adatok)
- Jogosultság ellenőrzés működik-e (csak admin / csak saját)

---

# ✅ Ellenőrzőlista

## 1. Hét
- [ ] Comment model kész kapcsolatokkal
- [ ] GET /api/campings/{id}/comments működik
- [ ] POST /api/comments működik validációval
- [ ] PUT/DELETE csak saját véleményre működik

## 2. Hét
- [ ] CampingPhoto model kész
- [ ] Kép feltöltés működik
- [ ] Kép törlés működik
- [ ] Storage link beállítva
- [ ] Képek elérhetőek URL-en

## 3. Hét
- [ ] Tag model és controller kész
- [ ] Location model és controller kész
- [ ] UserGuest model és controller kész

## 4. Hét
- [ ] API dokumentáció kész
- [ ] Minden végpont tesztelve
- [ ] Hibák javítva

---

# 🔗 API Végpontok Összefoglaló (amiket TE készítesz)

## Comment
```
GET    /api/campings/{id}/comments  → Vélemények listája
POST   /api/comments                → Új vélemény
PUT    /api/comments/{id}           → Vélemény szerkesztése
DELETE /api/comments/{id}           → Vélemény törlése
```

## Photo
```
GET    /api/campings/{id}/photos    → Camping képei
POST   /api/photos                  → Kép feltöltés (admin)
DELETE /api/photos/{id}             → Kép törlés (admin)
PUT    /api/photos/{id}/main        → Főkép beállítás (admin)
```

## Tag
```
GET    /api/tags                    → Összes címke
POST   /api/tags                    → Új címke (admin)
DELETE /api/tags/{id}               → Címke törlés (admin)
POST   /api/campings/{id}/tags      → Címkék hozzárendelése (admin)
```

## Location
```
GET    /api/locations               → Összes helyszín
GET    /api/locations/{id}          → Helyszín részletei
POST   /api/locations               → Új helyszín (admin)
```

## UserGuest
```
GET    /api/bookings/{id}/guests    → Foglalás vendégei
POST   /api/guests                  → Vendég hozzáadása
PUT    /api/guests/{id}             → Vendég szerkesztése
DELETE /api/guests/{id}             → Vendég törlése
```

---

# 📚 Hasznos Dokumentációk

- **Laravel Storage:** https://laravel.com/docs/11.x/filesystem
- **Laravel File Uploads:** https://laravel.com/docs/11.x/filesystem#file-uploads

---

Kérdés esetén keress bátran! 🚀
