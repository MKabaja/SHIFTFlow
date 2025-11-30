# Backend MVP - Plan Implementacji (TDD-Style z Dokumentacją)

## Aplikacja do Zarządzania Grafikami - Kopalnia Soli Wieliczka

---

## 1. EXECUTIVE SUMMARY

Budujemy REST API w Laravel + Breeze dla systemu obsługi grafików turystycznych w kopalni Soli Wieliczka. System obsługuje:

-   3 role: pracownik, kierownik, admin
-   20+ stanowisk w kopalni (B1-B8, PW, WR, WS, TGT, itp.)
-   Wariantację godzin pracy, dyspozycje, urlopy
-   Raporty godzin i finansowe
-   Import pracowników z CSV/Excel

**Zakres MVP:** Migracje, modele, CRUD API, auth z JWT, walidacje biznesowe, dokumentacja.

---

## 2. STACK TECHNICZNY

-   **Framework:** Laravel 11 + Breeze (auth/logowanie)
-   **Auth:** JWT via `tymon/jwt-auth`
-   **Database:** MySQL 8.0 (Docker)
-   **API:** REST (JSON responses)
-   **Testing:** Laravel Feature tests
-   **Deployment:** Docker Compose

---

## 3. ARCHITEKTURA

```
Frontend (React SPA)
    ↓
API Gateway (CORS, Rate Limit)
    ↓
Laravel Controllers (Auth, Schedules, Employees, Reports)
    ↓
Service Layer (Business Logic, Validations)
    ↓
Database (Users, Schedules, Availabilities, Positions)
```

---

## 4. MODELE DANYCH

### User Model (rozszerzony Breeze)

```
- id (PK)
- name (string)
- email (string, unique, nullable) — dla kierownika/admina
- password (string, hashed)
- role (enum: employee, manager, admin)
- pin_hashed (string, 60, nullable, hashed) — dla pracownika (PIN login)
- is_active (boolean, default: true) — czy pracownik aktywny
- positions (json, nullable) — relacja do positions → array position_id — uprawnienia
- hourly_rate (decimal 8,2, nullable) — domyślna stawka
- max_hours_per_month (unsignedSmallInteger, nullable) — limit godzin/miesiąc
- min_break_hours (unsignedSmallInteger, default: 11) — min przerwa między zmianami
- contract_type (enum: uop, zlecenie, default: uop) — rodzaj umowy
- created_at, updated_at
```

### Position Model (NEW - 28.11.2025)

```
- id (PK)
- name (string, unique) — np. "B1", "B2", "PW", "WR", "WS", "TGT"
- description (text, nullable) — np. "Bileter jeden"
- created_by (FK → users, nullable) — kto dodał pozycję
- created_at, updated_at
```

### Schedule Model

```
- id (PK)
- user_id (FK → users)
- position_id (FK → positions) — zamiast enum position
- date (date)
- shift_start (time)
- shift_end (time)
- hours_worked (smallint) — wyliczone lub ręczne
- status (enum: scheduled, completed, cancelled, vacation, unavailable)
- hourly_rate (decimal 8,2, nullable) — może różnić się od default
- notes (text, nullable)
- created_at, updated_at
```

### Availability Model

```
- id (PK)
- user_id (FK → users, on delete cascade)
- date (date) — konkretny dzień, którym pracownik chce/nie chce pracować
- is_available (boolean) — TRUE = chce pracować, FALSE = nie chce (dyspozycja/urlop)
- submission_date (date, nullable) — kiedy pracownik złożył dyspozycję (dla audytu)
- notes (text, nullable) — powód (urlop, choroba, itp.)
- created_at, updated_at
- Indeks: unique (user_id, date) — jeden rekord per dzień per pracownik
```

---

## 5. ENDPOINTY MVP (REST API)

### Auth

-   `POST /api/auth/login` — kierownik/admin (email + password)
-   `POST /api/auth/login-pin` — pracownik (id + pin)
-   `POST /api/auth/logout` — wylogowanie
-   `GET /api/auth/me` — dane zalogowanego użytkownika

### Positions (Admin/Manager)

-   `GET /api/positions` — lista wszystkich stanowisk
-   `POST /api/positions` — dodawanie stanowiska (admin)
-   `GET /api/positions/{id}` — szczegóły stanowiska
-   `PUT /api/positions/{id}` — edycja stanowiska (admin)
-   `DELETE /api/positions/{id}` — usunięcie stanowiska (admin)

### Employees (Admin/Manager)

-   `GET /api/employees` — lista pracowników (z pozycjami)
-   `POST /api/employees` — dodawanie pracownika (admin)
-   `GET /api/employees/{id}` — szczegóły pracownika
-   `PUT /api/employees/{id}` — edycja (admin)
-   `DELETE /api/employees/{id}` — usunięcie (admin)
-   `POST /api/employees/import` — import z CSV/Excel (admin)

### Schedules

-   `GET /api/schedules?date=2025-11-24&user_id=1` — lista grafików
-   `POST /api/schedules` — dodawanie grafiku (manager/admin)
-   `PUT /api/schedules/{id}` — edycja grafiku (manager/admin, drag&drop)
-   `DELETE /api/schedules/{id}` — usunięcie grafiku
-   `GET /api/schedules/{id}` — szczegóły grafiku

### Availabilities (Pracownik)

-   `GET /api/availabilities?user_id=1` — dyspozycje pracownika
-   `POST /api/availabilities` — dodawanie dyspozycji (pracownik na siebie)
-   `DELETE /api/availabilities/{id}` — usunięcie dyspozycji

### Reports (Manager/Admin)

-   `GET /api/reports/hours/{user_id}?month=11&year=2025` — raport godzin
-   `GET /api/reports/payroll?month=11&year=2025` — raport płacowy
-   `GET /api/reports/coverage?date=2025-11-24` — obsada na dzień

---

## 6. WALIDACJE BIZNESOWE

### Schedule Creation/Update Validations

Przed zapisaniem Schedule musisz sprawdzić:

1. **Uprawnienia do stanowiska**

    - Input: user_id, position_id
    - Logic: Sprawdź czy position_id ∈ user.positions (array position_ids)
    - Return: True/False lub throw ValidationException

2. **Dostępność pracownika**

    - Input: user_id, date
    - Logic:
        - Query: SELECT \* FROM availabilities WHERE user_id=? AND date=?
        - Jeśli znaleziony rekord I is_available=false → BŁĄD: "User is unavailable on {date}"
        - Jeśli nie ma rekordu → OK (domyślnie pracownik dostępny)
    - Return: True (dostępny) / False (brak dostępności)

3. **Konflikt czasowy**

    - Input: user_id, date, shift_start, shift_end
    - Logic: Query Schedule gdzie user_id ma już grafik na tym dniu pomiędzy shift_start a shift_end
    - Return: True (konflikt) / False (brak konfliktu)

4. **Minimalna przerwa między zmianami**

    - Input: user_id, date, shift_start, min_break_hours
    - Logic: Pobierz ostatni Schedule tego user_id przed date. Oblicz różnicę czasu między shift_end poprzedniego a shift_start nowego. Sprawdź czy >= min_break_hours
    - Return: True (OK) / False (za krótka przerwa) + error message z liczą godzin

5. **Limit godzin/miesiąc**
    - Input: user_id, date, shift_start, shift_end, max_hours_per_month
    - Logic: Oblicz hours_worked = (shift_end - shift_start) / 60. Pobierz wszystkie Schedule dla user_id w bieżącym miesiącu i zsumuj hours_worked. Sprawdź czy (suma + nowe godziny) <= max_hours_per_month
    - Return: True (OK) / False (przekroczenie) + error message z liczbą

### Logika domyślna dostępności

Pracownik jest domyślnie dostępny na każdy dzień, chyba że:

1. Wyraźnie złożył dyspozycję: `is_available = false` dla danego dnia
2. Brak rekordu w availabilities → brak danych o niedostępności

To oznacza, że kierownik MOŻE przypisać zmianę pracownikowi nawet bez jego zgody,
ale JEŚLI pracownik złożył dyspozycję `is_available=false` → przypisanie zmiany będzie zablokowane.

### PIN Login Validation

1. Rate limiting: max 5 prób / 15 minut z tego IP
2. PIN comparison: porównaj hashed PIN z bazą (Hash::check($request->pin, $user->pin_hashed))

---

## 7. STRUKTURA PROJEKTU LARAVEL

```
laravel-schedule-app/
├── app/
│   ├── Models/
│   │   ├── User.php (rozszerzony o role, positions, pin_hashed, contract_type)
│   │   ├── Schedule.php
│   │   ├── Availability.php
│   │   ├── Position.php (NEW - 28.11.2025)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── PositionController.php (NEW - 28.11.2025)
│   │   │   │   ├── EmployeeController.php
│   │   │   │   ├── ScheduleController.php
│   │   │   │   ├── AvailabilityController.php
│   │   │   │   ├── ReportController.php
│   │   ├── Middleware/
│   │   │   ├── JwtMiddleware.php (sprawdź JWT token)
│   │   │   ├── RoleMiddleware.php (sprawdź role)
│   │   ├── Requests/ (Form Requests do walidacji input)
│   │   │   ├── StoreScheduleRequest.php
│   │   │   ├── UpdateScheduleRequest.php
│   ├── Services/
│   │   ├── ScheduleService.php (logika biznesowa)
│   │   ├── ValidationService.php (walidacje limitów)
│   │   ├── ImportService.php (parser CSV)
│   ├── Exceptions/
│   │   ├── ValidationException.php
│   │   ├── ScheduleConflictException.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_extend_users_table.php
│   │   ├── 2024_01_01_000002_create_positions_table.php (NEW - 28.11.2025)
│   │   ├── 2024_01_01_000003_create_schedules_table.php (ZMIENIONA - position_id FK)
│   │   ├── 2024_01_01_000004_create_availabilities_table.php
│   ├── seeders/
│   │   ├── UserSeeder.php
│   │   ├── PositionSeeder.php (NEW - 28.11.2025)
│   │   ├── ScheduleSeeder.php
├── routes/
│   ├── api.php (wszystkie endpointy /api)
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── ScheduleTest.php
│   │   ├── EmployeeTest.php
│   │   ├── PositionTest.php (NEW - 28.11.2025)
├── .env.example
├── docker-compose.yml
├── Dockerfile
├── README.md
```

---

## 8. SPECYFIKACJE KOMPONENTÓW (ZADANIA TDD)

### SESJA 1-2: Setup & Docker

#### Zadanie 1.1: Inicjalizacja projektu

**Dokumentacja:**

-   https://laravel.com/docs/11/installation
-   https://laravel.com/docs/11/starter-kits#breeze

-   [x] Utwórz nowy projekt Laravel: `composer create-project laravel/laravel schedule-app`
-   [x] Zainstaluj Breeze: `composer require laravel/breeze && php artisan breeze:install`
-   [x] Sprawdź czy logowanie działa: `php artisan serve` → localhost:8000/login

#### Zadanie 1.2: Docker Setup

**Dokumentacja:**

-   https://laravel.com/docs/11/installation#docker-installation

-   [x] Utwórz `Dockerfile` (PHP 8.2 FPM z ext: pdo_mysql, zip)
-   [x] Utwórz `docker-compose.yml` z usługami:
    -   **app**: Laravel container na porcie 8000
    -   **db**: MySQL 8.0 na porcie 3306 (baza: wieliczka_db)
    -   **phpmyadmin**: PhpMyAdmin na porcie 8080 (optional)
-   [x] `docker-compose up -d` → aplikacja dostępna na localhost:8000
-   [x] Sprawdź że baza połączy się: `php artisan migrate`

**Commit:** `:tada: feat(setup): Laravel Breeze initial setup with Docker`

---

### SESJA 3-4: JWT Authentication Setup

#### Zadanie 3.1: Zainstaluj i konfiguruj JWT

**Dokumentacja:**

-   https://github.com/tymondesigns/jwt-auth
-   https://jwt-auth.readthedocs.io/en/develop/

-   [x] `composer require tymon/jwt-auth`
-   [x] `php artisan vendor:publish --provider="Tymon\\JWTAuth\\Providers\\JWTAuthServiceProvider"`
-   [x] `php artisan jwt:secret` (generator klucza)
-   [x] W `config/auth.php` dodaj guard 'api' z JWT driver:
    -   Typ: `jwt`
    -   Provider: `users`
    -   Ścieżka do klucza z .env

#### Zadanie 3.2: Testuj JWT endpoint

**Dokumentacja:**

-   https://laravel.com/docs/11/authentication#guards

-   [x] Utwórz testowy route: `GET /api/auth/me` (chroniony middleware `auth:api`)
-   [x] Route powinien zwrócić zalogowanego użytkownika lub 401 Unauthorized
-   [x] Test w Postmanie bez tokenu → 401
-   [x] Przygotuj się do login endpointu (kolejna sesja)

**Commit:** `:lock: feat(auth): JWT authentication setup`

---

### SESJA 5-6: User Model Extension

#### Zadanie 5.1: Utwórz migrację rozszerzającą User

**Dokumentacja:**

-   https://laravel.com/docs/11/migrations
-   https://laravel.com/docs/11/migrations#columns

**Plik:** `database/migrations/XXXX_extend_users_table.php`

Funkcja `up()` powinna:

-   Dodać kolumnę `pin_hashed` (string 60, nullable) — hashed PIN dla pracownika
-   Dodać kolumnę `is_active` (boolean, default: true) — czy pracownik aktywny
-   Dodać kolumnę `role` (enum: employee, manager, admin, default: employee)
-   Dodać kolumnę `positions` (json, nullable) — lista position_ids (FK → positions.id)
-   Dodać kolumnę `hourly_rate` (decimal 8,2, nullable)
-   Dodać kolumnę `max_hours_per_month` (unsignedSmallInteger, nullable)
-   Dodać kolumnę `min_break_hours` (unsignedSmallInteger, default: 11)
-   Dodać kolumnę `contract_type` (enum: uop, zlecenie, default: uop)

Funkcja `down()` powinna usunąć wszystkie dodane kolumny.

#### Zadanie 5.2: Rozszerz User Model

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#mass-assignment
-   https://laravel.com/docs/11/eloquent#attribute-casting
-   https://laravel.com/docs/11/eloquent#relationships

**Plik:** `app/Models/User.php`

W modelu dodaj:

-   `$fillable` array — wszystkie nowe kolumny (name, email, password, pin_hashed, is_active, role, positions, hourly_rate, max_hours_per_month, min_break_hours, contract_type)
-   `$hidden` — dodaj 'pin_hashed' (nigdy nie zwracaj PIN w API!)
-   `$casts` — rzutuj 'positions' na 'array', 'is_active' na 'boolean', 'hourly_rate' na 'decimal:2'
-   Relacje:
    -   `schedules()` — hasMany Schedule
    -   `availabilities()` — hasMany Availability
-   JWT Methods (jeśli używasz Tymon/JWT-Auth):
    -   `getJWTIdentifier()` — zwróć getKey()
    -   `getJWTCustomClaims()` — zwróć ['role' => $this->role, 'is_active' => $this->is_active]

#### Zadanie 5.3: Uruchom migrację

**Dokumentacja:**

-   https://laravel.com/docs/11/migrations#running-migrations

-   [x] `php artisan migrate`
-   [x] Sprawdź w PhpMyAdmin że kolumny dodane w users

**Commit:** `:wrench: feat(models): Extend User model with role, positions, pin_hashed, contract_type`

---

### SESJA 7-8: Schedule & Availability Models

#### Zadanie 7.1: Utwórz Schedule model i migrację

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#generating-model-classes
-   https://laravel.com/docs/11/migrations#creating-tables

**Polecenie:** `php artisan make:model Schedule -m`

Migracja `create_schedules_table` powinna:

-   `id` (PK)
-   `user_id` (FK → users, on delete cascade)
-   `position_id` (FK → positions, on delete cascade) — zamiast enum position
-   `date` (date)
-   `shift_start` (time)
-   `shift_end` (time)
-   `hours_worked` (unsignedSmallInteger)
-   `status` (enum: scheduled, completed, cancelled, vacation, unavailable)
-   `hourly_rate` (decimal 8,2, nullable)
-   `notes` (text, nullable)
-   `timestamps`
-   Indeksy: na user_id, date, (user_id, date)

Model `app/Models/Schedule.php`:

-   Relacja: `user()` — belongsTo User
-   Relacja: `position()` — belongsTo Position (NEW - 28.11.2025)
-   `$fillable` — wszystkie kolumny
-   `$casts` — rzutuj date i times na Carbon

#### Zadanie 7.2: Utwórz Availability model i migrację

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#generating-model-classes

**Polecenie:** `php artisan make:model Availability -m`

Migracja `create_availabilities_table` powinna:

-   `id` (PK)
-   `user_id` (FK → users, on delete cascade)
-   `date` (date)
-   `is_available` (boolean, default: true) — TRUE = chce pracować, FALSE = nie chce (dyspozycja/urlop)
-   `submission_date` (date, nullable) — kiedy pracownik złożył dyspozycję
-   `notes` (text, nullable) — powód (urlop, choroba, itp.)
-   `timestamps`
-   Indeks: unique na (user_id, date) — jeden rekord per dzień per pracownik

Model `app/Models/Availability.php`:

-   Relacja: `user()` — belongsTo User
-   `$fillable` — user_id, date, is_available, notes (submission_date BEZ $fillable!)
-   `$casts` — rzutuj date i submission_date na Carbon, is_available na boolean

#### Zadanie 7.2.5: Position Model i migracja (NEW - 28.11.2025)

**Problem rozwiązany:** Zamiast enum position w schedules, mamy osobną tabelę positions dla elastyczności.

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#generating-model-classes
-   https://laravel.com/docs/11/migrations#creating-tables

**Polecenie:** `php artisan make:model Position -m`

Migracja `create_positions_table` powinna:

-   `id` (PK)
-   `name` (string, unique) — np. "B1", "B2", "PW", "WR", "WS", "TGT"
-   `description` (text, nullable) — np. "Bileter jeden"
-   `created_by` (FK → users, nullable, on delete set null)
-   `timestamps`

Model `app/Models/Position.php`:

-   Relacja: `creator()` — belongsTo User, 'created_by'
-   Relacja: `schedules()` — hasMany Schedule (NEW)
-   `$fillable` — ['name', 'description', 'created_by']
-   `$casts` — brak specjalnych rzutów

**Migracja pozycji w kolejności:**

1. `create_positions_table` (PIERWSZA - 2024_01_01_000002)
2. `create_schedules_table` (z position_id FK - 2024_01_01_000003) — ZMIENIONA!
3. `create_availabilities_table` (2024_01_01_000004)

**Dlaczego ta zmiana:**

-   ✅ **Dynamiczność** – dodawanie/usuwanie stanowisk bez kodu
-   ✅ **Skalowalność** – łatwe rozbudowywanie + archiwizowanie
-   ✅ **Bezpieczeństwo** – walidacja przez FK, brak "orphan" rekordów
-   ✅ **Audyt** – wiemy kto dodał daną pozycję (created_by)
-   ✅ **Best practice** – relacyjne rozdzielenie odpowiedzialności

#### Zadanie 7.3: Uruchom migracje

-   [x] `php artisan migrate`
-   [x] Sprawdź strukturę tabel w PhpMyAdmin

**Commit:** `:database: feat(models): Schedule, Availability & Position models (position_id FK)`

---

### SESJA 9-10: Authentication Endpoints

#### Zadanie 9.1: AuthController - Login (email + password)

**Dokumentacja:**

-   https://laravel.com/docs/11/controllers#generating-controllers
-   https://laravel.com/docs/11/hashing#verifying-that-a-password-matches-a-hash
-   https://jwt-auth.readthedocs.io/en/develop/authentication/

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `login()`:

-   Accept: `POST /api/auth/login` → JSON body: {email, password}
-   Validuj input (email required, password required)
-   Sprawdź credentials: znajdź User po email i porównaj hasło
-   Jeśli OK: wygeneruj JWT token (używaj auth()->attempt() + auth()->tokenById())
-   Return: 200 JSON: {token, user: {id, name, role}}
-   Jeśli błąd: 401 {error: "Invalid credentials"}

#### Zadanie 9.2: AuthController - Login PIN (dla pracownika)

**Dokumentacja:**

-   https://laravel.com/docs/11/rate-limiting#defining-rate-limiters
-   https://laravel.com/docs/11/hashing

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `loginPin()`:

-   Accept: `POST /api/auth/login-pin` → JSON body: {employee_id, pin}
-   Validuj input (employee_id required, pin required)
-   Sprawdź czy user istnieje i role === 'employee'
-   Porównaj PIN (hashed): Hash::check($pin, $user->pin_hashed)
-   Rate limiting: max 5 prób / 15 minut (użyj RateLimiter)
-   Jeśli OK: wygeneruj JWT token
-   Return: 200 JSON: {token, user: {id, name, role}}
-   Jeśli błąd: 401 {error: "Invalid PIN"}

#### Zadanie 9.3: AuthController - Current User

**Dokumentacja:**

-   https://laravel.com/docs/11/authentication#retrieving-the-authenticated-user

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `me()`:

-   Accept: `GET /api/auth/me` (protected: middleware auth:api)
-   Return: 200 JSON zalogowanego użytkownika (auth()->user())
-   Nie zwracaj PIN!

#### Zadanie 9.4: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#route-groups

**Plik:** `routes/api.php`

Utwórz routes:

-   `POST /api/auth/login` → AuthController@login (public)
-   `POST /api/auth/login-pin` → AuthController@loginPin (public)
-   `GET /api/auth/me` → AuthController@me (protected: middleware auth:api)

#### Zadanie 9.5: Testuj w Postmanie

-   [x] POST /api/auth/login z email admina + password (z Breeze seedera) → powinna zwrócić token
-   [x] Copy token, ustawie header: `Authorization: Bearer {token}`
-   [x] GET /api/auth/me → powinna zwrócić dane użytkownika

**Commit:** `:lock: feat(auth): Login endpoints (email & PIN)`

---

### SESJA 11-12: Middleware (Autoryzacja)

#### Zadanie 11.1: RoleMiddleware

**Dokumentacja:**

-   https://laravel.com/docs/11/middleware#defining-middleware

**Plik:** `app/Http/Middleware/RoleMiddleware.php`

Polecenie: `php artisan make:middleware RoleMiddleware`

Middleware `RoleMiddleware`:

-   Accept parametry: `...roles` (np. 'manager', 'admin')
-   Logic:
    -   Sprawdź czy user zalogowany (auth()->check())
    -   Sprawdź czy role zalogowanego === jeden z parametrów (in_array(auth()->user()->role, $roles))
    -   Jeśli OK: pass to next request
    -   Jeśli błąd: return 403 Forbidden {error: "Unauthorized role"}

#### Zadanie 11.2: Zarejestruj middleware

**Dokumentacja:**

-   https://laravel.com/docs/11/middleware#registering-middleware

**Plik:** `app/Http/Kernel.php`

W `routeMiddleware` dodaj:

```
'role' => \\App\\Http\\Middleware\\RoleMiddleware::class,
```

#### Zadanie 11.3: Testuj middleware

-   [x] W `routes/api.php` dodaj testowy route: `Route::get('/admin-only', [...])->middleware('auth:api', 'role:admin')`
-   [x] Zaloguj się jako employee → 403
-   [x] Zaloguj się jako admin → 200

**Commit:** `:shield: feat(middleware): Role-based access control middleware`

---

### SESJA 13-14: Schedule CRUD

#### Zadanie 13.1: ScheduleController - Index

**Dokumentacja:**

-   https://laravel.com/docs/11/controllers#resource-controllers
-   https://laravel.com/docs/11/eloquent#retrieving-results

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Polecenie: `php artisan make:controller Api/ScheduleController --api`

Metoda `index()`:

-   Accept: `GET /api/schedules?date=2025-11-24&user_id=1` (optional query params)
-   Logic:
    -   Jeśli role === 'employee': pokaż tylko grafiki zalogowanego użytkownika
    -   Jeśli role === 'manager' lub 'admin': pokaż wszystkie, ale jeśli user_id w query → filtruj po user_id
    -   Jeśli date w query: filtruj po date
    -   Eager load user i position (with(['user', 'position']))
    -   Sortuj po date DESC
-   Return: 200 JSON array Schedule'ów z user i position details

#### Zadanie 13.2: ScheduleController - Store

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#inserting-models
-   https://laravel.com/docs/11/validation

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `store()`:

-   Accept: `POST /api/schedules` → JSON body: {user_id, date, position_id, shift_start, shift_end}
-   Validuj input (Form Request: StoreScheduleRequest)
-   Sprawdź autoryzację: tylko manager/admin mogą tworzyć dla innych
-   Oblicz hours_worked = (shift_end - shift_start) w godzinach
-   Przed save: CALL ValidationService do sprawdzenia biznesowych reguł
    -   Pozycja w positions?
    -   Dostępny pracownik?
    -   Konflikt czasowy?
    -   Przerwa między zmianami?
    -   Limit godzin/miesiąc?
-   Jeśli validation throws exception: catch i return 422 {error: message}
-   Jeśli OK: Create Schedule i return 201 {schedule}

#### Zadanie 13.3: ScheduleController - Update

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#updates

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `update(Schedule $schedule, Request $request)`:

-   Accept: `PUT /api/schedules/{id}` → JSON body: {position_id, shift_start, shift_end, notes}
-   Autoryzacja: tylko creator/manager/admin mogą edytować
-   Validuj input (UpdateScheduleRequest)
-   Powtórz walidacje biznesowe (jak w store)
-   Update i return 200 {schedule}

#### Zadanie 13.4: ScheduleController - Delete

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `destroy(Schedule $schedule)`:

-   Accept: `DELETE /api/schedules/{id}`
-   Autoryzacja: tylko manager/admin
-   Delete schedule
-   Return 200 {message: "Schedule deleted"}

#### Zadanie 13.5: StoreScheduleRequest

**Dokumentacja:**

-   https://laravel.com/docs/11/validation#form-request-validation

**Plik:** `app/Http/Requests/StoreScheduleRequest.php`

Polecenie: `php artisan make:request StoreScheduleRequest`

Form Request do validacji:

-   `authorize()`: sprawdź czy user jest manager lub admin
-   `rules()`:
    -   user_id: required, exists:users,id
    -   position_id: required, exists:positions,id
    -   date: required, date, date_format:Y-m-d
    -   shift_start: required, date_format:H:i
    -   shift_end: required, date_format:H:i, after:shift_start

#### Zadanie 13.6: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:manager,admin'])->group(function () {
    Route::apiResource('schedules', ScheduleController::class);
});
```

**Commit:** `:calendar: feat(api): Schedule CRUD operations (position_id)`

---

### SESJA 15-16: Validation Service (Business Logic)

#### Zadanie 15.1: ValidationService

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#retrieving-single-models
-   https://laravel.com/docs/11/eloquent#counting-models

**Plik:** `app/Services/ValidationService.php`

Polecenie: `php artisan make:class Services/ValidationService`

Klasa z metodami do walidacji (patrz sekcja 6. WALIDACJE BIZNESOWE):

Metoda `validateScheduleCreation($userId, $date, $shiftStart, $shiftEnd, $positionId)`:

-   Sprawdzenie 1: Uprawnienia do stanowiska
    -   Pobierz user.positions (array position_ids)
    -   Sprawdź czy position_id ∈ positions
    -   Throw: "User does not have permission for position: {positionId}"
-   Sprawdzenie 2: Dostępność
    -   Query Availability gdzie user_id i date
    -   Throw: "User is unavailable on {date}"
-   Sprawdzenie 3: Konflikt czasowy
    -   Query Schedule gdzie user_id, date, i time overlap
    -   Throw: "Time conflict: User has schedule during this time"
-   Sprawdzenie 4: Minimum break hours
    -   Query Schedule user_id, order by date DESC
    -   Oblicz break = (shift_start - previous_shift_end)
    -   Throw: "Insufficient break: required {min}h, got {actual}h"
-   Sprawdzenie 5: Max hours per month
    -   Query sum(hours_worked) dla user_id w current month
    -   Oblicz new_hours = (shift_end - shift_start)
    -   Throw: "Max hours exceeded: {total}h > {max}h"

Jeśli wszystkie OK: return true

#### Zadanie 15.2: Use ValidationService w ScheduleController

**Dokumentacja:**

-   https://laravel.com/docs/11/container#method-injection

-   Inject ValidationService do konstruktora controllera
-   W store() i update() wywoła `$this->validationService->validateScheduleCreation(...)`
-   Catch ValidationException i return 422

**Commit:** `:mag: feat(services): Business logic validation service`

---

### SESJA 17-18: Position CRUD (NEW - 28.11.2025)

#### Zadanie 17.1: PositionController - Index

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#retrieving-multiple-models

**Plik:** `app/Http/Controllers/Api/PositionController.php`

Polecenie: `php artisan make:controller Api/PositionController --api`

Metoda `index()`:

-   Accept: `GET /api/positions` (protected: manager/admin)
-   Return: 200 JSON array wszystkich Positions z creator info (eager load creator)
-   Include: id, name, description, created_by (user.name), created_at

#### Zadanie 17.2: PositionController - Store

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#inserting-models

**Plik:** `app/Http/Controllers/Api/PositionController.php`

Metoda `store()`:

-   Accept: `POST /api/positions` → JSON body: {name, description}
-   Autoryzacja: tylko admin
-   Validuj input: name (required, string, unique:positions,name)
-   Set created_by = auth()->id()
-   Create Position
-   Return 201 {position}

#### Zadanie 17.3: PositionController - Update

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#updating-models

**Plik:** `app/Http/Controllers/Api/PositionController.php`

Metoda `update(Position $position, Request $request)`:

-   Accept: `PUT /api/positions/{id}` → JSON body: {name, description}
-   Autoryzacja: tylko admin
-   Validuj input: name (required, string, unique:positions,name,{id})
-   Update position
-   Return 200 {position}

#### Zadanie 17.4: PositionController - Delete

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/PositionController.php`

Metoda `destroy(Position $position)`:

-   Accept: `DELETE /api/positions/{id}`
-   Autoryzacja: tylko admin
-   Sprawdź czy pozycja nie jest używana w Schedule (query count)
-   Jeśli używana: return 422 {error: "Cannot delete position - used in schedules"}
-   Delete position
-   Return 200 {message: "Position deleted"}

#### Zadanie 17.5: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('positions', PositionController::class);
});
```

**Commit:** `:package: feat(api): Position management CRUD endpoints`

---

### SESJA 19-20: Employee Management (CRUD) - ZMIENIONA

#### Zadanie 19.1: EmployeeController - Index

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#retrieving-multiple-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `index()`:

-   Accept: `GET /api/employees` (protected: only manager/admin)
-   Return: 200 JSON array wszystkich Users role=employee
-   Include positions (jako position objects - eager load), hourly_rate
-   Eager load: with('positions') - relacja many-to-many (jeśli użyjesz pivot table, albo json array z IDs)

#### Zadanie 19.2: EmployeeController - Store

**Dokumentacja:**

-   https://laravel.com/docs/11/hashing#hashing-passwords

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `store()`:

-   Accept: `POST /api/employees` → JSON body: {name, email, pin, position_ids (array), hourly_rate, max_hours_per_month, min_break_hours, contract_type}
-   Autoryzacja: tylko admin
-   Validuj input (Form Request):
    -   position_ids: required, array, each ID exists:positions,id
-   Hash PIN: `Hash::make($pin)` i zapisz do `pin_hashed`
-   Validate position_ids exist
-   Create User z role='employee'
-   Set positions = $position_ids (jako JSON array lub relacja)
-   Return 201 {user}

#### Zadanie 19.3: EmployeeController - Update

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#updating-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `update(User $user, Request $request)`:

-   Accept: `PUT /api/employees/{id}` → JSON body: {position_ids, hourly_rate, contract_type, ...}
-   Autoryzacja: tylko admin
-   Update user
-   Return 200 {user}

#### Zadanie 19.4: EmployeeController - Delete

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `destroy(User $user)`:

-   Accept: `DELETE /api/employees/{id}`
-   Autoryzacja: tylko admin
-   Delete user (cascade usunie schedules)
-   Return 200 {message}

#### Zadanie 19.5: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('employees', EmployeeController::class);
});
```

**Commit:** `:bust_in_silhouette: feat(api): Employee management endpoints`

---

### SESJA 21-22: CSV Import

#### Zadanie 21.1: ImportService

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#mass-assignment
-   https://github.com/SpartnerNL/Laravel-Excel (optional, lub parse CSV ręcznie)

**Plik:** `app/Services/ImportService.php`

Metoda `parseCSV(UploadedFile $file)`:

-   Accept: UploadedFile z CSV
-   Logic:
    -   Odczytaj CSV (każdy wiersz = pracownik)
    -   Kolumny: name, email, pin, B1, B2, B3, ..., PW, PW2, WR, WS, TGT, ...
    -   Dla każdego pracownika:
        -   Zbierz wszystkie pozycje gdzie wartość = "TAK" (lub 1)
        -   Query positions by name i zbierz position_ids
        -   Utwórz array positions: [id1, id2, ...]
        -   Hash PIN do `pin_hashed`
        -   Create User z role='employee'
        -   Set positions = array position_ids
-   Return: array{success: count, errors: []}

#### Zadanie 21.2: EmployeeController - Import endpoint

**Dokumentacja:**

-   https://laravel.com/docs/11/requests#file-uploads
-   https://laravel.com/docs/11/validation#validating-files

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `import()`:

-   Accept: `POST /api/employees/import` → multipart/form-data: file
-   Autoryzacja: tylko admin
-   Validuj file (must be xlsx/csv)
-   Inject ImportService i parse CSV
-   Return 200 {imported: count, errors: [...]}

#### Zadanie 21.3: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#custom-resource-routes

**Plik:** `routes/api.php`

```
Route::post('/employees/import', [EmployeeController::class, 'import'])
    ->middleware(['auth:api', 'role:admin']);
```

**Commit:** `:inbox_tray: feat(import): CSV/Excel employee import`

---

### SESJA 23-24: Availability API

#### Zadanie 23.1: AvailabilityController - Index

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#retrieving-results

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Polecenie: `php artisan make:controller Api/AvailabilityController --api`

Metoda `index()`:

-   Accept: `GET /api/availabilities?user_id=1` (optional query)
-   Logic:
    -   Jeśli employee: pokaż tylko swoje availabilities
    -   Jeśli manager/admin: pokaż wszystkie (opcjonalnie filtruj po user_id)
-   Return: 200 JSON array Availabilities

#### Zadanie 23.2: AvailabilityController - Store

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#inserting-models

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Metoda `store()`:

-   Accept: `POST /api/availabilities` → JSON body: {date, is_available, notes}
-   Jeśli employee: pracownik dodaje sam na siebie (user_id = auth()->id())
-   Jeśli manager/admin: może dodać dla kogokolwiek (+ user_id w body)
-   Validuj input:
    -   user_id: required, exists:users,id
    -   date: required, date, unique per (user_id, date)
    -   is_available: required, boolean
    -   notes: optional, string, max 255
-   submission_date: automatycznie ustawia się na dzisiejszą datę (NIE w $fillable!)
-   Create Availability (updateOrCreate dla existing)
-   Return 201 {availability} lub 200 {availability} (jeśli update)

#### Zadanie 23.3: AvailabilityController - Delete

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Metoda `destroy(Availability $availability)`:

-   Accept: `DELETE /api/availabilities/{id}`
-   Autoryzacja: tylko owner lub manager/admin
-   Delete
-   Return 200 {message}

#### Zadanie 23.4: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware('auth:api')->group(function () {
    Route::apiResource('availabilities', AvailabilityController::class);
});
```

**Commit:** `:calendar: feat(api): Availability endpoints (dyspozycje)`

---

### SESJA 25-26: Reports API

#### Zadanie 25.1: ReportController - Hours Report

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#aggregates
-   https://laravel.com/docs/11/eloquent#grouping-results

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Polecenie: `php artisan make:controller Api/ReportController`

Metoda `hours($userId)`:

-   Accept: `GET /api/reports/hours/{user_id}?month=11&year=2025` (query: month, year)
-   Autoryzacja: employee widzi swoje, manager/admin widzi wszystkie
-   Query Schedule dla user_id w danym miesiącu
-   Aggregate:
    -   Total hours per month
    -   Hours per position
    -   Hours per day
-   Return: 200 JSON {user, month, year, total_hours, by_position: {...}, by_date: {...}}

#### Zadanie 25.2: ReportController - Payroll Report

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#raw-expressions
-   https://laravel.com/docs/11/eloquent#selecting-specific-columns

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Metoda `payroll()`:

-   Accept: `GET /api/reports/payroll?month=11&year=2025`
-   Autoryzacja: tylko manager/admin
-   Query wszystkich Schedule dla miesiąca
-   Calculate: per pracownik: hours_worked \* hourly_rate = cost
-   Aggregate: total cost per employee, total cost per position, total cost
-   Return: 200 JSON {month, year, employees: [{name, hours, rate, cost}], by_position: {...}, total_cost}

#### Zadanie 25.3: ReportController - Coverage Report

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent#grouping-results

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Metoda `coverage()`:

-   Accept: `GET /api/reports/coverage?date=2025-11-24`
-   Autoryzacja: manager/admin
-   Query Schedule dla date
-   Group by position: ile osób na każdym stanowisku?
-   Return: 200 JSON {date, positions: {B1: 2, B2: 3, WR: 1, ...}}

#### Zadanie 25.4: Routes

**Dokumentacja:**

-   https://laravel.com/docs/11/routing#route-parameters

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:manager,admin'])->group(function () {
    Route::get('/reports/hours/{user_id}', [ReportController::class, 'hours']);
    Route::get('/reports/payroll', [ReportController::class, 'payroll']);
    Route::get('/reports/coverage', [ReportController::class, 'coverage']);
});
```

**Commit:** `:bar_chart: feat(api): Reports endpoints`

---

### SESJA 27-28: Feature Tests

#### Zadanie 27.1: ScheduleTest

**Dokumentacja:**

-   https://laravel.com/docs/11/testing#creating-tests
-   https://laravel.com/docs/11/testing#making-requests
-   https://laravel.com/docs/11/database-testing#introduction

**Plik:** `tests/Feature/ScheduleTest.php`

Polecenie: `php artisan make:test ScheduleTest --type=Feature`

Utwórz Feature Tests (każdy test to jedna metoda):

Test 1: `test_manager_can_create_schedule()`

-   Stwórz manager i employee z positions
-   POST /api/schedules → 201
-   Assert Schedule created w bazie

Test 2: `test_cannot_exceed_max_hours()`

-   Stwórz employee z max_hours=40
-   Dodaj Schedule 35 godzin
-   Spróbuj dodać 10 godzin → 422 validation error

Test 3: `test_cannot_create_with_insufficient_break()`

-   Stwórz Schedule 09:00-17:00 na poniedziałek
-   Spróbuj dodać Schedule 02:00-10:00 na wtorek (5 godzin break) → 422

Test 4: `test_employee_sees_only_own_schedules()`

-   Stwórz 2 employees
-   Zaloguj się jako employee A
-   GET /api/schedules → widzisz tylko swoje

Test 5: `test_schedule_requires_position_permission()`

-   Stwórz employee z positions: [position_B1.id]
-   Spróbuj dodać Schedule na position_WR → 422

#### Zadanie 27.2: AuthTest

**Dokumentacja:**

-   https://laravel.com/docs/11/testing#authenticated-requests

**Plik:** `tests/Feature/AuthTest.php`

Polecenie: `php artisan make:test AuthTest --type=Feature`

Test 1: `test_login_with_email_password()`

-   POST /api/auth/login {email, password} → 200 + token

Test 2: `test_login_pin_for_employee()`

-   POST /api/auth/login-pin {employee_id, pin} → 200 + token

Test 3: `test_invalid_credentials_return_401()`

-   POST /api/auth/login {email, wrong_password} → 401

Test 4: `test_get_current_user()`

-   Zaloguj się
-   GET /api/auth/me → 200 + user data

#### Zadanie 27.3: PositionTest (NEW - 28.11.2025)

**Dokumentacja:**

-   https://laravel.com/docs/11/testing#creating-tests

**Plik:** `tests/Feature/PositionTest.php`

Polecenie: `php artisan make:test PositionTest --type=Feature`

Test 1: `test_admin_can_create_position()`

-   POST /api/positions {name: "B9", description: "New position"} → 201
-   Assert Position created

Test 2: `test_cannot_create_duplicate_position()`

-   Create Position "B1"
-   Try POST /api/positions {name: "B1"} → 422 (unique constraint)

Test 3: `test_cannot_delete_position_in_use()`

-   Create Position i Schedule z tą position_id
-   Try DELETE /api/positions/{id} → 422

Test 4: `test_employee_cannot_manage_positions()`

-   Zaloguj się jako employee
-   POST /api/positions → 403 Forbidden

#### Zadanie 27.4: Run tests

**Dokumentacja:**

-   https://laravel.com/docs/11/testing#running-tests

-   [ ] `php artisan test`
-   [ ] Wszystkie testy powinny pass

**Commit:** `:test_tube: test(feature): Feature tests for API`

---

### SESJA 29-30: Seeders & Factories

#### Zadanie 29.1: PositionSeeder (NEW - 28.11.2025)

**Dokumentacja:**

-   https://laravel.com/docs/11/seeding#writing-seeders

**Plik:** `database/seeders/PositionSeeder.php`

Polecenie: `php artisan make:seeder PositionSeeder`

Seeder powinien utworzyć:

-   Default pozycje: B1, B2, B3, B4, B5, B6, B7, B8, PW, WR, WS, TGT
-   Dla każdej: Position::create(['name' => 'B1', 'description' => 'Bileter jeden', 'created_by' => 1])
-   created_by = admin user (ID 1)

#### Zadanie 29.2: UserFactory (ZMIENIONA)

**Dokumentacja:**

-   https://laravel.com/docs/11/eloquent-factories#generating-models

**Plik:** `database/factories/UserFactory.php`

Polecenie: `php artisan make:factory UserFactory --model=User`

Factory powinien generować:

-   name: fake()->name()
-   email: unique fake()->email()
-   password: Hash::make('password')
-   role: fake()->randomElement(['employee', 'manager', 'admin'])
-   pin_hashed: Hash::make(fake()->numerify('####')) (jeśli role=employee)
-   is_active: true
-   positions: (jeśli role=employee) Query Position::whereIn('name', fake()->randomElements(['B1', 'B2', 'PW', 'WR', 'WS', 'TGT'], 3))->pluck('id')->toArray()
-   hourly_rate: fake()->numberBetween(15, 30)
-   max_hours_per_month: 160
-   min_break_hours: 11
-   contract_type: fake()->randomElement(['uop', 'zlecenie'])

#### Zadanie 29.3: UserSeeder (ZMIENIONA)

**Dokumentacja:**

-   https://laravel.com/docs/11/seeding#writing-seeders

**Plik:** `database/seeders/UserSeeder.php`

Polecenie: `php artisan make:seeder UserSeeder`

Seeder powinien utworzyć:

-   1 admin: email=admin@example.com, password=password, positions=all position_ids
-   2 managers: names=Kierownik 1 & 2, emails=manager1@, manager2@, positions=random 5 positions
-   20 employees: random names, pins, positions (2-4 losowe), rates, contracts

#### Zadanie 29.4: ScheduleSeeder

**Dokumentacja:**

-   https://laravel.com/docs/11/seeding#using-factories

**Plik:** `database/seeders/ScheduleSeeder.php`

Polecenie: `php artisan make:seeder ScheduleSeeder`

Seeder powinien:

-   Pobierz wszystkich employees
-   Dla każdego employee: create 15 Schedule'ów w bieżącym miesiącu
-   Każdy Schedule: random date, random position (z user.positions), random shift (08:00-17:00 lub 09:00-18:00), auto-calc hours
-   Validuj że nie ma konfliktów/naruszenia walidacji biznesowych

#### Zadanie 29.5: DatabaseSeeder

**Dokumentacja:**

-   https://laravel.com/docs/11/seeding#running-seeders

**Plik:** `database/seeders/DatabaseSeeder.php`

Main seeder powinien call:

```
$this->call([
    PositionSeeder::class,   // FIRST!
    UserSeeder::class,
    ScheduleSeeder::class,
]);
```

#### Zadanie 29.6: Run seeders

**Dokumentacja:**

-   https://laravel.com/docs/11/migrations#seeding-your-database

-   [ ] `php artisan migrate:fresh --seed`
-   [ ] Sprawdź w PhpMyAdmin że dane dodane
-   [ ] Sprawdź w API: GET /api/employees z tokenem managera → powinno zwrócić 20 employees
-   [ ] Sprawdź: GET /api/positions → powinno zwrócić 12 default positions

**Commit:** `:seedling: test(seeders): Database seeders with positions`

---

### SESJA 31-32: Documentation & README

#### Zadanie 31.1: README.md

**Dokumentacja:**

-   https://laravel.com/docs/11

**Plik:** `README.md`

README powinno zawierać:

1. **Project overview** — co to jest, dla kogo
2. **Quick start:**

    - Clone repo
    - `cp .env.example .env`
    - `docker-compose up -d`
    - `docker exec laravel_app php artisan migrate --seed`
    - Access: http://localhost:8000

3. **API Documentation** — tabela endpointów:

    - Metoda, Path, Description, Auth required?
    - Przykład request/response dla kilku kluczowych endpointów

4. **Architecture** — diagram: Frontend → API → Database

5. **Database schema** — opis tabel i kolumn (including Position table)

6. **Authentication** — jak JWT działa, jak się zalogować

7. **Testing** — jak uruchomić testy: `php artisan test`

8. **Deployment** — jak deployować (opcjonalnie na później)

9. **Troubleshooting** — typowe problemy

#### Zadanie 31.2: Environment variables

**Dokumentacja:**

-   https://laravel.com/docs/11/configuration#environment-configuration

**Plik:** `.env.example`

Powinno zawierać wszystkie zmienne:

-   APP_NAME, APP_ENV, APP_DEBUG, APP_URL
-   DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
-   JWT_SECRET (albo wygeneruj przy setup)

#### Zadanie 31.3: API endpoints listing

**Plik:** `API_ENDPOINTS.md` (opcjonalnie)

Listing wszystkich endpointów w formacie:

```
## Authentication

### Login
- **Method:** POST
- **Path:** /api/auth/login
- **Auth:** No
- **Body:** {email, password}
- **Response:** 200 {token, user}

### Login PIN
- **Method:** POST
- **Path:** /api/auth/login-pin
- **Auth:** No
- **Body:** {employee_id, pin}
- **Response:** 200 {token, user}

## Positions (NEW)

### List All
- **Method:** GET
- **Path:** /api/positions
- **Auth:** Yes (manager/admin)
- **Response:** 200 [{id, name, description, created_by, ...}]

### Create
- **Method:** POST
- **Path:** /api/positions
- **Auth:** Yes (admin)
- **Body:** {name, description}
- **Response:** 201 {id, name, ...}

... (itd dla wszystkich endpointów)
```

**Commit:** `:memo: docs(readme): API documentation & setup guide (with Positions)`

---

## 9. ESTYMACJA CZASU (dla początkującego w Laravelu)

| Sesja | Faza       | Zadania                          | Estymacja |
| ----- | ---------- | -------------------------------- | --------- |
| 1-2   | Setup      | Docker, Breeze, struktura        | 4h        |
| 3-4   | JWT        | Auth config, test endpoint       | 4h        |
| 5-6   | Models     | User extend, migrations          | 4h        |
| 7-8   | Models     | Schedule & Availability          | 4h        |
| 7.2.5 | Models     | Position model & migration       | 2h        |
| 9-10  | Auth       | Login endpoints, Postman test    | 4h        |
| 11-12 | Middleware | RoleMiddleware, autoryzacja      | 3h        |
| 13-14 | CRUD       | Schedule CRUD, walidacje input   | 5h        |
| 15-16 | Services   | ValidationService, biznes logic  | 5h        |
| 17-18 | CRUD       | Position management              | 3h        |
| 19-20 | CRUD       | Employee management              | 4h        |
| 21-22 | Import     | CSV parser, ImportService        | 4h        |
| 23-24 | API        | Availability endpoints           | 3h        |
| 25-26 | Reports    | Hours, payroll, coverage reports | 5h        |
| 27-28 | Tests      | Feature tests                    | 5h        |
| 29-30 | Seeders    | Factories, seeders, data         | 4h        |
| 31-32 | Docs       | README, documentation            | 3h        |
| —     | Buffer     | Bugfixes, debugging              | 5h        |
|       | **TOTAL**  |                                  | **~67h**  |

**Realistycznie: ~3 tygodnie (2 sesje/dzień × 6 dni/tydzień)**

---

## 10. SCHEMAT COMMITOWANIA

Dla każdej sesji:

-   Atomic commit po ukończeniu zadań
-   Format: `:emoji: type(scope): subject`
-   Subject = krótko co zrobiłeś

Przykłady:

```
:tada: feat(setup): Laravel Breeze initial setup with Docker
:lock: feat(auth): JWT authentication setup
:wrench: feat(models): Extend User model with role, positions, pin_hashed, contract_type
:database: feat(models): Schedule, Availability & Position models (position_id FK)
:lock: feat(auth): Login endpoints (email & PIN)
:shield: feat(middleware): Role-based access control
:calendar: feat(api): Schedule CRUD operations (position_id)
:mag: feat(services): Business logic validation
:package: feat(api): Position management CRUD endpoints
:bust_in_silhouette: feat(api): Employee management
:inbox_tray: feat(import): CSV employee import
:calendar: feat(api): Availability endpoints
:bar_chart: feat(api): Reports endpoints
:test_tube: test(feature): Feature tests
:seedling: test(seeders): Database seeders with positions
:memo: docs(readme): API documentation
```

---

## 11. PODSUMOWANIE

-   **Estymacja:** ~67 godzin (32 sesje po 2h)
-   **Stack:** Laravel 11 + Breeze + JWT + Docker
-   **Approach:** TDD-style — opisane zadania zamiast gotowych snippetów
-   **Struktura:** 32 sesji, każda atomic + commit
-   **Focus:** Nauczenie się zamiast copy-paste
-   **Dokumentacja:** Linki do Laravel docs przy każdym zadaniu

**Ty samy napiszesz kod, będziesz rozumieć każdy kawałek, i nauczysz się Laravela na praktyce!** 🚀

Powodzenia! Jeśli będziesz miał pytania na temat specyfikacji zadań — pytaj!
