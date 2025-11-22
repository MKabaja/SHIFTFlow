# Backend MVP - Plan Implementacji (TDD-Style z Dokumentacją)
## Aplikacja do Zarządzania Grafikami - Kopalnia Soli Wieliczka

---

## 1. EXECUTIVE SUMMARY

Budujemy REST API w Laravel + Breeze dla systemu obsługi grafików turystycznych w kopalni Soli Wieliczka. System obsługuje:
- 3 role: pracownik, kierownik, admin
- 20+ stanowisk w kopalni (B1-B8, PW, WR, WS, TGT, itp.)
- Wariantację godzin pracy, dyspozycje, urlopy
- Raporty godzin i finansowe
- Import pracowników z CSV/Excel

**Zakres MVP:** Migracje, modele, CRUD API, auth z JWT, walidacje biznesowe, dokumentacja.

---

## 2. STACK TECHNICZNY

- **Framework:** Laravel 11 + Breeze (auth/logowanie)
- **Auth:** JWT via `tymon/jwt-auth`
- **Database:** MySQL 8.0 (Docker)
- **API:** REST (JSON responses)
- **Testing:** Laravel Feature tests
- **Deployment:** Docker Compose

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
Database (Users, Schedules, Availabilities)
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
- pin (string, nullable, hashed) — dla pracownika
- positions (json) — ["B1", "B2", "PW", "WR"] — uprawnienia
- hourly_rate (decimal 8,2, nullable) — domyślna stawka
- max_hours_per_month (int) — limit godzin/miesiąc
- min_break_hours (int) — min przerwa między zmianami
- created_at, updated_at
```

### Schedule Model
```
- id (PK)
- user_id (FK → users)
- date (date)
- position (string) — np. "B1", "WR", "TGT"
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
- user_id (FK → users)
- date (date)
- available_from (time, nullable)
- available_to (time, nullable)
- created_at, updated_at
```

---

## 5. ENDPOINTY MVP (REST API)

### Auth
- `POST /api/auth/login` — kierownik/admin (email + password)
- `POST /api/auth/login-pin` — pracownik (id + pin)
- `POST /api/auth/logout` — wylogowanie
- `GET /api/auth/me` — dane zalogowanego użytkownika

### Employees (Admin/Manager)
- `GET /api/employees` — lista pracowników (z pozycjami)
- `POST /api/employees` — dodawanie pracownika (admin)
- `GET /api/employees/{id}` — szczegóły pracownika
- `PUT /api/employees/{id}` — edycja (admin)
- `DELETE /api/employees/{id}` — usunięcie (admin)
- `POST /api/employees/import` — import z CSV/Excel (admin)

### Schedules
- `GET /api/schedules?date=2025-11-24&user_id=1` — lista grafików
- `POST /api/schedules` — dodawanie grafiku (manager/admin)
- `PUT /api/schedules/{id}` — edycja grafiku (manager/admin, drag&drop)
- `DELETE /api/schedules/{id}` — usunięcie grafiku
- `GET /api/schedules/{id}` — szczegóły grafiku

### Availabilities (Pracownik)
- `GET /api/availabilities/{user_id}` — dyspozycje pracownika
- `POST /api/availabilities` — dodawanie dyspozycji (pracownik na siebie)
- `DELETE /api/availabilities/{id}` — usunięcie dyspozycji

### Reports (Manager/Admin)
- `GET /api/reports/hours/{user_id}?month=11&year=2025` — raport godzin
- `GET /api/reports/payroll?month=11&year=2025` — raport płacowy
- `GET /api/reports/coverage?date=2025-11-24` — obsada na dzień

---

## 6. WALIDACJE BIZNESOWE

### Schedule Creation/Update Validations
Przed zapisaniem Schedule musisz sprawdzić:

1. **Uprawnienia do stanowiska**
   - Input: user_id, position
   - Logic: Pobierz user.positions (JSON array) i sprawdź czy position ∈ user.positions
   - Return: True/False lub throw ValidationException

2. **Dostępność pracownika**
   - Input: user_id, date
   - Logic: Sprawdź czy istnieje Availability dla tego user_id i date
   - Return: True (dostępny) / False (ma dyspozycję/urlop)

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

### PIN Login Validation
1. Rate limiting: max 5 prób / 15 minut z tego IP
2. PIN comparison: porównaj hashed PIN z bazą

---

## 7. STRUKTURA PROJEKTU LARAVEL

```
laravel-schedule-app/
├── app/
│   ├── Models/
│   │   ├── User.php (rozszerzony o role, positions, hourly_rate)
│   │   ├── Schedule.php
│   │   ├── Availability.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
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
│   │   ├── 2024_01_01_000002_create_schedules_table.php
│   │   ├── 2024_01_01_000003_create_availabilities_table.php
│   ├── seeders/
│   │   ├── UserSeeder.php
│   │   ├── ScheduleSeeder.php
├── routes/
│   ├── api.php (wszystkie endpointy /api)
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── ScheduleTest.php
│   │   ├── EmployeeTest.php
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
- https://laravel.com/docs/11/installation
- https://laravel.com/docs/11/starter-kits#breeze

- [ ] Utwórz nowy projekt Laravel: `composer create-project laravel/laravel schedule-app`
- [ ] Zainstaluj Breeze: `composer require laravel/breeze && php artisan breeze:install`
- [ ] Sprawdź czy logowanie działa: `php artisan serve` → localhost:8000/login

#### Zadanie 1.2: Docker Setup
**Dokumentacja:**
- https://laravel.com/docs/11/installation#docker-installation

- [ ] Utwórz `Dockerfile` (PHP 8.2 FPM z ext: pdo_mysql, zip)
- [ ] Utwórz `docker-compose.yml` z usługami:
  - **app**: Laravel container na porcie 8000
  - **db**: MySQL 8.0 na porcie 3306 (baza: wieliczka_db)
  - **phpmyadmin**: PhpMyAdmin na porcie 8080 (optional)
- [ ] `docker-compose up -d` → aplikacja dostępna na localhost:8000
- [ ] Sprawdź że baza połączy się: `php artisan migrate`

**Commit:** `:tada: feat(setup): Laravel Breeze initial setup with Docker`

---

### SESJA 3-4: JWT Authentication Setup

#### Zadanie 3.1: Zainstaluj i konfiguruj JWT
**Dokumentacja:**
- https://github.com/tymondesigns/jwt-auth
- https://jwt-auth.readthedocs.io/en/develop/

- [ ] `composer require tymon/jwt-auth`
- [ ] `php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\JWTAuthServiceProvider"`
- [ ] `php artisan jwt:secret` (generator klucza)
- [ ] W `config/auth.php` dodaj guard 'api' z JWT driver:
  - Typ: `jwt`
  - Provider: `users`
  - Ścieżka do klucza z .env

#### Zadanie 3.2: Testuj JWT endpoint
**Dokumentacja:**
- https://laravel.com/docs/11/authentication#guards

- [ ] Utwórz testowy route: `GET /api/auth/me` (chroniony middleware `auth:api`)
- [ ] Route powinien zwrócić zalogowanego użytkownika lub 401 Unauthorized
- [ ] Test w Postmanie bez tokenu → 401
- [ ] Przygotuj się do login endpointu (kolejna sesja)

**Commit:** `:lock: feat(auth): JWT authentication setup`

---

### SESJA 5-6: User Model Extension

#### Zadanie 5.1: Utwórz migrację rozszerzającą User
**Dokumentacja:**
- https://laravel.com/docs/11/migrations
- https://laravel.com/docs/11/migrations#columns

**Plik:** `database/migrations/XXXX_extend_users_table.php`

Funkcja `up()` powinna:
- Dodać kolumnę `role` (enum: employee, manager, admin, default: employee)
- Dodać kolumnę `pin` (string, nullable)
- Dodać kolumnę `positions` (json, nullable) — lista stanowisk
- Dodać kolumnę `hourly_rate` (decimal 8,2, nullable)
- Dodać kolumnę `max_hours_per_month` (unsignedSmallInteger, default: 160)
- Dodać kolumnę `min_break_hours` (unsignedSmallInteger, default: 11)

Funkcja `down()` powinna usunąć wszystkie dodane kolumny.

#### Zadanie 5.2: Rozszerz User Model
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#mass-assignment
- https://laravel.com/docs/11/eloquent#attribute-casting
- https://laravel.com/docs/11/eloquent#relationships

**Plik:** `app/Models/User.php`

W modelu dodaj:
- `$fillable` array — dodaj nowe kolumny
- `$hidden` — dodaj 'pin' (nigdy nie zwracaj PIN w API!)
- `$casts` — rzutuj 'positions' na 'array' (automatyczne JSON ↔ Array konwersje)
- Relacje:
  - `schedules()` — hasMany Schedule
  - `availabilities()` — hasMany Availability

#### Zadanie 5.3: Uruchom migrację
**Dokumentacja:**
- https://laravel.com/docs/11/migrations#running-migrations

- [ ] `php artisan migrate`
- [ ] Sprawdź w PhpMyAdmin że kolumny dodane w users

**Commit:** `:wrench: feat(models): Extend User model with role, positions, hourly_rate`

---

### SESJA 7-8: Schedule & Availability Models

#### Zadanie 7.1: Utwórz Schedule model i migrację
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#generating-model-classes
- https://laravel.com/docs/11/migrations#creating-tables

**Polecenie:** `php artisan make:model Schedule -m`

Migracja `create_schedules_table` powinna:
- `id` (PK)
- `user_id` (FK → users, on delete cascade)
- `date` (date)
- `position` (string) — stanowisko
- `shift_start` (time)
- `shift_end` (time)
- `hours_worked` (unsignedSmallInteger)
- `status` (enum: scheduled, completed, cancelled, vacation, unavailable)
- `hourly_rate` (decimal 8,2, nullable)
- `notes` (text, nullable)
- `timestamps`
- Indeksy: na user_id, date, (user_id, date)

Model `app/Models/Schedule.php`:
- Relacja: `user()` — belongsTo User
- `$fillable` — wszystkie kolumny
- `$casts` — rzutuj date i times na Carbon

#### Zadanie 7.2: Utwórz Availability model i migrację
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#generating-model-classes

**Polecenie:** `php artisan make:model Availability -m`

Migracja `create_availabilities_table` powinna:
- `id` (PK)
- `user_id` (FK → users, on delete cascade)
- `date` (date)
- `available_from` (time, nullable)
- `available_to` (time, nullable)
- `timestamps`
- Indeks: na (user_id, date)

Model `app/Models/Availability.php`:
- Relacja: `user()` — belongsTo User
- `$fillable` — user_id, date, available_from, available_to
- `$casts` — rzutuj date na Carbon

#### Zadanie 7.3: Uruchom migracje
- [ ] `php artisan migrate`
- [ ] Sprawdź strukturę tabel w PhpMyAdmin

**Commit:** `:database: feat(models): Schedule & Availability models with migrations`

---

### SESJA 9-10: Authentication Endpoints

#### Zadanie 9.1: AuthController - Login (email + password)
**Dokumentacja:**
- https://laravel.com/docs/11/controllers#generating-controllers
- https://laravel.com/docs/11/hashing#verifying-that-a-password-matches-a-hash
- https://jwt-auth.readthedocs.io/en/develop/authentication/

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `login()`:
- Accept: `POST /api/auth/login` → JSON body: {email, password}
- Validuj input (email required, password required)
- Sprawdź credentials: znajdź User po email i porównaj hasło
- Jeśli OK: wygeneruj JWT token (używaj auth()->attempt() + auth()->tokenById())
- Return: 200 JSON: {token, user: {id, name, role}}
- Jeśli błąd: 401 {error: "Invalid credentials"}

#### Zadanie 9.2: AuthController - Login PIN (dla pracownika)
**Dokumentacja:**
- https://laravel.com/docs/11/rate-limiting#defining-rate-limiters
- https://laravel.com/docs/11/hashing

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `loginPin()`:
- Accept: `POST /api/auth/login-pin` → JSON body: {employee_id, pin}
- Validuj input (employee_id required, pin required)
- Sprawdź czy user istnieje i role === 'employee'
- Porównaj PIN (hashed): Hash::check($pin, $user->pin)
- Rate limiting: max 5 prób / 15 minut (użyj RateLimiter)
- Jeśli OK: wygeneruj JWT token
- Return: 200 JSON: {token, user: {id, name, role}}
- Jeśli błąd: 401 {error: "Invalid PIN"}

#### Zadanie 9.3: AuthController - Current User
**Dokumentacja:**
- https://laravel.com/docs/11/authentication#retrieving-the-authenticated-user

**Plik:** `app/Http/Controllers/Api/AuthController.php`

Metoda `me()`:
- Accept: `GET /api/auth/me` (protected: middleware auth:api)
- Return: 200 JSON zalogowanego użytkownika (auth()->user())
- Nie zwracaj PIN!

#### Zadanie 9.4: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#route-groups

**Plik:** `routes/api.php`

Utwórz routes:
- `POST /api/auth/login` → AuthController@login (public)
- `POST /api/auth/login-pin` → AuthController@loginPin (public)
- `GET /api/auth/me` → AuthController@me (protected: middleware auth:api)

#### Zadanie 9.5: Testuj w Postmanie
- [ ] POST /api/auth/login z email admina + password (z Breeze seedera) → powinna zwrócić token
- [ ] Copy token, ustawie header: `Authorization: Bearer {token}`
- [ ] GET /api/auth/me → powinna zwrócić dane użytkownika

**Commit:** `:lock: feat(auth): Login endpoints (email & PIN)`

---

### SESJA 11-12: Middleware (Autoryzacja)

#### Zadanie 11.1: RoleMiddleware
**Dokumentacja:**
- https://laravel.com/docs/11/middleware#defining-middleware

**Plik:** `app/Http/Middleware/RoleMiddleware.php`

Polecenie: `php artisan make:middleware RoleMiddleware`

Middleware `RoleMiddleware`:
- Accept parametry: `...roles` (np. 'manager', 'admin')
- Logic:
  - Sprawdź czy user zalogowany (auth()->check())
  - Sprawdź czy role zalogowanego === jeden z parametrów (in_array(auth()->user()->role, $roles))
  - Jeśli OK: pass to next request
  - Jeśli błąd: return 403 Forbidden {error: "Unauthorized role"}

#### Zadanie 11.2: Zarejestruj middleware
**Dokumentacja:**
- https://laravel.com/docs/11/middleware#registering-middleware

**Plik:** `app/Http/Kernel.php`

W `routeMiddleware` dodaj:
```
'role' => \App\Http\Middleware\RoleMiddleware::class,
```

#### Zadanie 11.3: Testuj middleware
- [ ] W `routes/api.php` dodaj testowy route: `Route::get('/admin-only', [...])->middleware('auth:api', 'role:admin')`
- [ ] Zaloguj się jako employee → 403
- [ ] Zaloguj się jako admin → 200

**Commit:** `:shield: feat(middleware): Role-based access control middleware`

---

### SESJA 13-14: Schedule CRUD

#### Zadanie 13.1: ScheduleController - Index
**Dokumentacja:**
- https://laravel.com/docs/11/controllers#resource-controllers
- https://laravel.com/docs/11/eloquent#retrieving-results

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Polecenie: `php artisan make:controller Api/ScheduleController --api`

Metoda `index()`:
- Accept: `GET /api/schedules?date=2025-11-24&user_id=1` (optional query params)
- Logic:
  - Jeśli role === 'employee': pokaż tylko grafiki zalogowanego użytkownika
  - Jeśli role === 'manager' lub 'admin': pokaż wszystkie, ale jeśli user_id w query → filtruj po user_id
  - Jeśli date w query: filtruj po date
  - Eager load user (with('user'))
  - Sortuj po date DESC
- Return: 200 JSON array Schedule'ów z user details

#### Zadanie 13.2: ScheduleController - Store
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#inserting-models
- https://laravel.com/docs/11/validation

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `store()`:
- Accept: `POST /api/schedules` → JSON body: {user_id, date, position, shift_start, shift_end}
- Validuj input (Form Request: StoreScheduleRequest)
- Sprawdź autoryzację: tylko manager/admin mogą tworzyć dla innych
- Oblicz hours_worked = (shift_end - shift_start) w godzinach
- Przed save: CALL ValidationService do sprawdzenia biznesowych reguł
  - Pozycja w positions?
  - Dostępny pracownik?
  - Konflikt czasowy?
  - Przerwa między zmianami?
  - Limit godzin/miesiąc?
- Jeśli validation throws exception: catch i return 422 {error: message}
- Jeśli OK: Create Schedule i return 201 {schedule}

#### Zadanie 13.3: ScheduleController - Update
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#updates

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `update(Schedule $schedule, Request $request)`:
- Accept: `PUT /api/schedules/{id}` → JSON body: {position, shift_start, shift_end, notes}
- Autoryzacja: tylko creator/manager/admin mogą edytować
- Validuj input (UpdateScheduleRequest)
- Powtórz walidacje biznesowe (jak w store)
- Update i return 200 {schedule}

#### Zadanie 13.4: ScheduleController - Delete
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/ScheduleController.php`

Metoda `destroy(Schedule $schedule)`:
- Accept: `DELETE /api/schedules/{id}`
- Autoryzacja: tylko manager/admin
- Delete schedule
- Return 200 {message: "Schedule deleted"}

#### Zadanie 13.5: StoreScheduleRequest
**Dokumentacja:**
- https://laravel.com/docs/11/validation#form-request-validation

**Plik:** `app/Http/Requests/StoreScheduleRequest.php`

Polecenie: `php artisan make:request StoreScheduleRequest`

Form Request do validacji:
- `authorize()`: sprawdź czy user jest manager lub admin
- `rules()`:
  - user_id: required, exists:users,id
  - date: required, date, date_format:Y-m-d
  - position: required, string
  - shift_start: required, date_format:H:i
  - shift_end: required, date_format:H:i, after:shift_start

#### Zadanie 13.6: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:manager,admin'])->group(function () {
    Route::apiResource('schedules', ScheduleController::class);
});
```

**Commit:** `:calendar: feat(api): Schedule CRUD operations`

---

### SESJA 15-16: Validation Service (Business Logic)

#### Zadanie 15.1: ValidationService
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#retrieving-single-models
- https://laravel.com/docs/11/eloquent#counting-models

**Plik:** `app/Services/ValidationService.php`

Polecenie: `php artisan make:class Services/ValidationService`

Klasa z metodami do walidacji (patrz sekcja 6. WALIDACJE BIZNESOWE):

Metoda `validateScheduleCreation($userId, $date, $shiftStart, $shiftEnd, $position)`:
- Sprawdzenie 1: Uprawnienia do stanowiska
  - Pobierz user.positions
  - Sprawdź czy position ∈ positions
  - Throw: "User does not have permission for position: {position}"
  
- Sprawdzenie 2: Dostępność
  - Query Availability gdzie user_id i date
  - Throw: "User is unavailable on {date}"
  
- Sprawdzenie 3: Konflikt czasowy
  - Query Schedule gdzie user_id, date, i time overlap
  - Throw: "Time conflict: User has schedule during this time"
  
- Sprawdzenie 4: Minimum break hours
  - Query Schedule user_id, order by date DESC
  - Oblicz break = (shift_start - previous_shift_end)
  - Throw: "Insufficient break: required {min}h, got {actual}h"
  
- Sprawdzenie 5: Max hours per month
  - Query sum(hours_worked) dla user_id w current month
  - Oblicz new_hours = (shift_end - shift_start)
  - Throw: "Max hours exceeded: {total}h > {max}h"

Jeśli wszystkie OK: return true

#### Zadanie 15.2: Use ValidationService w ScheduleController
**Dokumentacja:**
- https://laravel.com/docs/11/container#method-injection

- Inject ValidationService do konstruktora controllera
- W store() i update() wywoła `$this->validationService->validateScheduleCreation(...)`
- Catch ValidationException i return 422

**Commit:** `:mag: feat(services): Business logic validation service`

---

### SESJA 17-18: Employee Management (CRUD)

#### Zadanie 17.1: EmployeeController - Index
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#retrieving-multiple-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `index()`:
- Accept: `GET /api/employees` (protected: only manager/admin)
- Return: 200 JSON array wszystkich Users role=employee
- Include positions, hourly_rate

#### Zadanie 17.2: EmployeeController - Store
**Dokumentacja:**
- https://laravel.com/docs/11/hashing#hashing-passwords

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `store()`:
- Accept: `POST /api/employees` → JSON body: {name, email, pin, positions, hourly_rate, max_hours_per_month, min_break_hours}
- Autoryzacja: tylko admin
- Validuj input (Form Request)
- Hash PIN: `Hash::make($pin)`
- Create User z role='employee'
- Return 201 {user}

#### Zadanie 17.3: EmployeeController - Update
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#updating-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `update(User $user, Request $request)`:
- Accept: `PUT /api/employees/{id}` → JSON body: {positions, hourly_rate, ...}
- Autoryzacja: tylko admin
- Update user
- Return 200 {user}

#### Zadanie 17.4: EmployeeController - Delete
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `destroy(User $user)`:
- Accept: `DELETE /api/employees/{id}`
- Autoryzacja: tylko admin
- Delete user (cascade usunie schedules)
- Return 200 {message}

#### Zadanie 17.5: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('employees', EmployeeController::class);
});
```

**Commit:** `:bust_in_silhouette: feat(api): Employee management endpoints`

---

### SESJA 19-20: CSV Import

#### Zadanie 19.1: ImportService
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#mass-assignment
- https://github.com/SpartnerNL/Laravel-Excel (optional, lub parse CSV ręcznie)

**Plik:** `app/Services/ImportService.php`

Metoda `parseCSV(UploadedFile $file)`:
- Accept: UploadedFile z CSV
- Logic:
  - Odczytaj CSV (każdy wiersz = pracownik)
  - Kolumny: name, email, pin, B1, B2, B3, ..., PW, PW2, WR, WS, TGT, ...
  - Dla każdego pracownika:
    - Zbierz wszystkie pozycje gdzie wartość = "TAK" (lub 1)
    - Utwórz array positions: ["B1", "B2", ...]
    - Hash PIN
    - Create User z role='employee'
- Return: array{success: count, errors: []}

#### Zadanie 19.2: EmployeeController - Import endpoint
**Dokumentacja:**
- https://laravel.com/docs/11/requests#file-uploads
- https://laravel.com/docs/11/validation#validating-files

**Plik:** `app/Http/Controllers/Api/EmployeeController.php`

Metoda `import()`:
- Accept: `POST /api/employees/import` → multipart/form-data: file
- Autoryzacja: tylko admin
- Validuj file (must be xlsx/csv)
- Inject ImportService i parse CSV
- Return 200 {imported: count, errors: [...]}

#### Zadanie 19.3: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#custom-resource-routes

**Plik:** `routes/api.php`

```
Route::post('/employees/import', [EmployeeController::class, 'import'])
    ->middleware(['auth:api', 'role:admin']);
```

**Commit:** `:inbox_tray: feat(import): CSV/Excel employee import`

---

### SESJA 21-22: Availability API

#### Zadanie 21.1: AvailabilityController - Index
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#retrieving-results

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Polecenie: `php artisan make:controller Api/AvailabilityController --api`

Metoda `index()`:
- Accept: `GET /api/availabilities?user_id=1` (optional query)
- Logic:
  - Jeśli employee: pokaż tylko swoje availabilities
  - Jeśli manager/admin: pokaż wszystkie (opcjonalnie filtruj po user_id)
- Return: 200 JSON array Availabilities

#### Zadanie 21.2: AvailabilityController - Store
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#inserting-models

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Metoda `store()`:
- Accept: `POST /api/availabilities` → JSON body: {date, available_from, available_to}
- Jeśli employee: na siebie. Jeśli manager/admin: może na kogośkolwiek (+ user_id w body)
- Create Availability
- Return 201 {availability}

#### Zadanie 21.3: AvailabilityController - Delete
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#deleting-models

**Plik:** `app/Http/Controllers/Api/AvailabilityController.php`

Metoda `destroy(Availability $availability)`:
- Accept: `DELETE /api/availabilities/{id}`
- Autoryzacja: tylko owner lub manager/admin
- Delete
- Return 200 {message}

#### Zadanie 21.4: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#resource-controllers

**Plik:** `routes/api.php`

```
Route::middleware('auth:api')->group(function () {
    Route::apiResource('availabilities', AvailabilityController::class);
});
```

**Commit:** `:calendar: feat(api): Availability endpoints (dyspozycje)`

---

### SESJA 23-24: Reports API

#### Zadanie 23.1: ReportController - Hours Report
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#aggregates
- https://laravel.com/docs/11/eloquent#grouping-results

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Polecenie: `php artisan make:controller Api/ReportController`

Metoda `hours($userId)`:
- Accept: `GET /api/reports/hours/{user_id}?month=11&year=2025` (query: month, year)
- Autoryzacja: employee widzi swoje, manager/admin widzi wszystkie
- Query Schedule dla user_id w danym miesiącu
- Aggregate:
  - Total hours per month
  - Hours per position
  - Hours per day
- Return: 200 JSON {user, month, year, total_hours, by_position: {...}, by_date: {...}}

#### Zadanie 23.2: ReportController - Payroll Report
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#raw-expressions
- https://laravel.com/docs/11/eloquent#selecting-specific-columns

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Metoda `payroll()`:
- Accept: `GET /api/reports/payroll?month=11&year=2025`
- Autoryzacja: tylko manager/admin
- Query wszystkich Schedule dla miesiąca
- Calculate: per pracownik: hours_worked * hourly_rate = cost
- Aggregate: total cost per employee, total cost per position, total cost
- Return: 200 JSON {month, year, employees: [{name, hours, rate, cost}], by_position: {...}, total_cost}

#### Zadanie 23.3: ReportController - Coverage Report
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent#grouping-results

**Plik:** `app/Http/Controllers/Api/ReportController.php`

Metoda `coverage()`:
- Accept: `GET /api/reports/coverage?date=2025-11-24`
- Autoryzacja: manager/admin
- Query Schedule dla date
- Group by position: ile osób na każdym stanowisku?
- Return: 200 JSON {date, positions: {B1: 2, B2: 3, WR: 1, ...}}

#### Zadanie 23.4: Routes
**Dokumentacja:**
- https://laravel.com/docs/11/routing#route-parameters

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

### SESJA 25-26: Feature Tests

#### Zadanie 25.1: ScheduleTest
**Dokumentacja:**
- https://laravel.com/docs/11/testing#creating-tests
- https://laravel.com/docs/11/testing#making-requests
- https://laravel.com/docs/11/database-testing#introduction

**Plik:** `tests/Feature/ScheduleTest.php`

Polecenie: `php artisan make:test ScheduleTest --type=Feature`

Utwórz Feature Tests (każdy test to jedna metoda):

Test 1: `test_manager_can_create_schedule()`
- Stwórz manager i employee z positions
- POST /api/schedules → 201
- Assert Schedule created w bazie

Test 2: `test_cannot_exceed_max_hours()`
- Stwórz employee z max_hours=40
- Dodaj Schedule 35 godzin
- Spróbuj dodać 10 godzin → 422 validation error

Test 3: `test_cannot_create_with_insufficient_break()`
- Stwórz Schedule 09:00-17:00 na poniedziałek
- Spróbuj dodać Schedule 02:00-10:00 na wtorek (5 godzin break) → 422

Test 4: `test_employee_sees_only_own_schedules()`
- Stwórz 2 employees
- Zaloguj się jako employee A
- GET /api/schedules → widzisz tylko swoje

Test 5: `test_schedule_requires_position_permission()`
- Stwórz employee z positions: ["B1"]
- Spróbuj dodać Schedule na "WR" → 422

#### Zadanie 25.2: AuthTest
**Dokumentacja:**
- https://laravel.com/docs/11/testing#authenticated-requests

**Plik:** `tests/Feature/AuthTest.php`

Polecenie: `php artisan make:test AuthTest --type=Feature`

Test 1: `test_login_with_email_password()`
- POST /api/auth/login {email, password} → 200 + token

Test 2: `test_login_pin_for_employee()`
- POST /api/auth/login-pin {employee_id, pin} → 200 + token

Test 3: `test_invalid_credentials_return_401()`
- POST /api/auth/login {email, wrong_password} → 401

Test 4: `test_get_current_user()`
- Zaloguj się
- GET /api/auth/me → 200 + user data

#### Zadanie 25.3: Run tests
**Dokumentacja:**
- https://laravel.com/docs/11/testing#running-tests

- [ ] `php artisan test`
- [ ] Wszystkie testy powinny pass

**Commit:** `:test_tube: test(feature): Feature tests for API`

---

### SESJA 27-28: Seeders & Factories

#### Zadanie 27.1: UserFactory
**Dokumentacja:**
- https://laravel.com/docs/11/eloquent-factories#generating-models

**Plik:** `database/factories/UserFactory.php`

Polecenie: `php artisan make:factory UserFactory --model=User`

Factory powinien generować:
- name: fake()->name()
- email: unique fake()->email()
- password: Hash::make('password')
- role: fake()->randomElement(['employee', 'manager', 'admin'])
- pin: Hash::make(fake()->numerify('####')) (jeśli role=employee)
- positions: (jeśli role=employee) fake()->randomElements(['B1', 'B2', 'PW', 'WR', 'WS', 'TGT', ...], fake()->numberBetween(2, 5))
- hourly_rate: fake()->numberBetween(15, 30)
- max_hours_per_month: 160
- min_break_hours: 11

#### Zadanie 27.2: UserSeeder
**Dokumentacja:**
- https://laravel.com/docs/11/seeding#writing-seeders

**Plik:** `database/seeders/UserSeeder.php`

Polecenie: `php artisan make:seeder UserSeeder`

Seeder powinien utworzyć:
- 1 admin: email=admin@example.com, password=password
- 2 managers: names=Kierownik 1 & 2, emails=manager1@, manager2@
- 20 employees: random names, pins, positions, rates

#### Zadanie 27.3: ScheduleSeeder
**Dokumentacja:**
- https://laravel.com/docs/11/seeding#using-factories

**Plik:** `database/seeders/ScheduleSeeder.php`

Polecenie: `php artisan make:seeder ScheduleSeeder`

Seeder powinien:
- Pobierz wszystkich employees
- Dla każdego employee: create 15 Schedule'ów w bieżącym miesiącu
- Każdy Schedule: random date, random position (z user.positions), random shift (08:00-17:00 lub 09:00-18:00), auto-calc hours
- Validuj że nie ma konfliktów/naruszenia walidacji biznesowych

#### Zadanie 27.4: DatabaseSeeder
**Dokumentacja:**
- https://laravel.com/docs/11/seeding#running-seeders

**Plik:** `database/seeders/DatabaseSeeder.php`

Main seeder powinien call:
```
$this->call([
    UserSeeder::class,
    ScheduleSeeder::class,
]);
```

#### Zadanie 27.5: Run seeders
**Dokumentacja:**
- https://laravel.com/docs/11/migrations#seeding-your-database

- [ ] `php artisan migrate:fresh --seed`
- [ ] Sprawdź w PhpMyAdmin że dane dodane
- [ ] Sprawdź w API: GET /api/employees z tokenem managera → powinno zwrócić 20 employees

**Commit:** `:seedling: test(seeders): Database seeders & factories`

---

### SESJA 29-30: Documentation & README

#### Zadanie 29.1: README.md
**Dokumentacja:**
- https://laravel.com/docs/11

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
   - Metoda, Path, Descripción, Auth required?
   - Przykład request/response dla kilku kluczowych endpointów

4. **Architecture** — diagram: Frontend → API → Database

5. **Database schema** — opis tabel i kolumn

6. **Authentication** — jak JWT działa, jak się zalogować

7. **Testing** — jak uruchomić testy: `php artisan test`

8. **Deployment** — jak deployować (opcjonalnie na później)

9. **Troubleshooting** — typowe problemy

#### Zadanie 29.2: Environment variables
**Dokumentacja:**
- https://laravel.com/docs/11/configuration#environment-configuration

**Plik:** `.env.example`

Powinno zawierać wszystkie zmienne:
- APP_NAME, APP_ENV, APP_DEBUG, APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- JWT_SECRET (albo wygeneruj przy setup)

#### Zadanie 29.3: API endpoints listing
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

## Employees

### List All
- **Method:** GET
- **Path:** /api/employees
- **Auth:** Yes (manager/admin)
- **Response:** 200 [{id, name, positions, hourly_rate, ...}]

... (itd dla wszystkich endpointów)
```

**Commit:** `:memo: docs(readme): API documentation & setup guide`

---

## 9. ESTYMACJA CZASU (dla początkującego w Laravelu)

| Sesja | Faza | Zadania | Estymacja |
|-------|------|---------|-----------|
| 1-2 | Setup | Docker, Breeze, struktura | 4h |
| 3-4 | JWT | Auth config, test endpoint | 4h |
| 5-6 | Models | User extend, migrations | 4h |
| 7-8 | Models | Schedule & Availability | 4h |
| 9-10 | Auth | Login endpoints, Postman test | 4h |
| 11-12 | Middleware | RoleMiddleware, autoryzacja | 3h |
| 13-14 | CRUD | Schedule CRUD, walidacje input | 5h |
| 15-16 | Services | ValidationService, biznes logic | 5h |
| 17-18 | CRUD | Employee management | 4h |
| 19-20 | Import | CSV parser, ImportService | 4h |
| 21-22 | API | Availability endpoints | 3h |
| 23-24 | Reports | Hours, payroll, coverage reports | 5h |
| 25-26 | Tests | Feature tests | 5h |
| 27-28 | Seeders | Factories, seeders, data | 4h |
| 29-30 | Docs | README, documentation | 3h |
| — | Buffer | Bugfixes, debugging | 4h |
| | **TOTAL** | | **~60h** |

**Realistycznie: ~2.5-3 tygodnie (2 sesje/dzień × 6 dni/tydzień)**

---

## 10. SCHEMAT COMMITOWANIA

Dla każdej sesji:
- Atomic commit po ukończeniu zadań
- Format: `:emoji: type(scope): subject`
- Subject = krótko co zrobiłeś

Przykłady:
```
:tada: feat(setup): Laravel Breeze initial setup with Docker
:lock: feat(auth): JWT authentication setup
:wrench: feat(models): Extend User model with role, positions
:database: feat(models): Schedule & Availability models
:lock: feat(auth): Login endpoints (email & PIN)
:shield: feat(middleware): Role-based access control
:calendar: feat(api): Schedule CRUD operations
:mag: feat(services): Business logic validation
:bust_in_silhouette: feat(api): Employee management
:inbox_tray: feat(import): CSV employee import
:calendar: feat(api): Availability endpoints
:bar_chart: feat(api): Reports endpoints
:test_tube: test(feature): Feature tests
:seedling: test(seeders): Database seeders
:memo: docs(readme): API documentation
```

---

## 11. PODSUMOWANIE

- **Estymacja:** ~60 godzin (30 sesji po 2h)
- **Stack:** Laravel 11 + Breeze + JWT + Docker
- **Aproach:** TDD-style — opisane zadania zamiast gotowych snippetów
- **Struktura:** 30 sesji, każda atomic + commit
- **Focus:** Nauczenie się zamiast copy-paste
- **Dokumentacja:** Linki do Laravel docs przy każdym zadaniu

**Ty samy napiszesz kod, będziesz rozumieć każdy kawałek, i nauczysz się Laravela na praktyce!** 🚀

Powodzenia! Jeśli będziesz miał pytania na temat specyfikacji zadań — pytaj w osobnej przestrzeni z prompt-asystent.md!
