# SHIFTFlow — Plan poprawek i ulepszeń

**Data:** 2026-04-24  
**Zakres:** PHPStan, 22 poprawki z audytu bezpieczeństwa, Redis, dokumentacja  
**Autor kodu:** Mateusz | **Mentor / Code Review:** Claude

---

## Legenda statusów

- [ ] Do zrobienia
- [x] Ukończone
- [~] W trakcie

---

## Faza 0 — PHPStan + GitHub Actions

> Cel: zamrozić istniejące błędy baseline'em, żeby nowy kod był pilnowany od razu.

### Task 0.1 — Instalacja Larastan

**Szacowany czas:** 10 min  
**Commit:** `chore: install Larastan with phpstan.neon config`

Checkpoints:

- [x] `composer require larastan/larastan --dev` (w kontenerze)
- [x] Utworzyć `phpstan.neon` w root projektu (level 2, paths: app)
- [x] Uruchomić `./vendor/bin/phpstan analyse` — zobaczyć ile błędów

---

### Task 0.2 — Generowanie baseline

**Szacowany czas:** 5 min  
**Commit:** `chore: add PHPStan baseline (freeze existing errors)`

Checkpoints:

- [ ] `./vendor/bin/phpstan analyse --generate-baseline`
- [ ] Dodać `phpstan-baseline.neon` do `phpstan.neon` (includes)
- [ ] Uruchomić ponownie — wynik powinien być 0 błędów
- [ ] Commit: `phpstan.neon` + `phpstan-baseline.neon`

---

### Task 0.3 — GitHub Actions CI

**Szacowany czas:** 15 min  
**Commit:** `ci: add PHPStan GitHub Actions workflow`

Checkpoints:

- [ ] Utworzyć `.github/workflows/phpstan.yml`
- [ ] Workflow na push do `main` i `develop`, PR do `main`
- [ ] PHP 8.2, composer install, `./vendor/bin/phpstan analyse --memory-limit=256M`
- [ ] Sprawdzić że `phpstan-baseline.neon` jest w repo (bez niego CI krzyczy)

---

## Faza 1 — Poprawki krytyczne (AUDYT #1–#6)

### Task 1.1 — Rate limiting na endpointach auth (AUDYT #5)

**Szacowany czas:** 15 min  
**Commit:** `fix: add throttle rate limiting on auth login endpoints`  
**Plik:** `backend/routes/api.php` linie 18–20

Checkpoints:

- [ ] Dodać `throttle:5,1` na `POST /api/auth/login`
- [ ] Dodać `throttle:5,1` na `POST /api/auth/login-pin`
- [ ] Ręczny test: 6. próba logowania → 429 Too Many Requests

---

### Task 1.2 — Sprawdzenie `is_active` przy logowaniu (AUDYT #4)

**Szacowany czas:** 20 min  
**Commit:** `fix: block deactivated users from logging in`  
**Plik:** `backend/app/Http/Controllers/Api/AuthController.php` linie 16–71

Checkpoints:

- [ ] W metodzie `login()` po weryfikacji credentials → sprawdzenie `is_active`
- [ ] W metodzie `loginPin()` po weryfikacji PIN → sprawdzenie `is_active`
- [ ] Response 403 z `{'message': 'Account deactivated'}` dla nieaktywnych
- [ ] Ręczny test: deaktywowany user → 403

---

### Task 1.3 — PIN do konfiguracji (AUDYT #2)

**Szacowany czas:** 20 min  
**Commit:** `fix: move default employee PIN to env config`  
**Pliki:** `backend/app/Repositories/EmployeeRepository.php` L142, `backend/config/app.php`

> Decyzja: PIN `1234` jest świadomy (onboarding — pracownik zmienia przy pierwszym logowaniu).  
> Zmieniamy tylko sposób przechowywania — z hardcoded na konfigurowalny.

Checkpoints:

- [ ] Dodać do `config/app.php`: `'default_employee_pin' => env('DEFAULT_EMPLOYEE_PIN', '1234')`
- [ ] W `EmployeeRepository.php` L142: `config('app.default_employee_pin')` zamiast `1234`
- [ ] Dodać komentarz wyjaśniający intencję (onboarding flow)
- [ ] Dodać `DEFAULT_EMPLOYEE_PIN=1234` do `.env.example`

---

### Task 1.4 — Regeneracja JWT_SECRET (AUDYT #6)

**Szacowany czas:** 15 min  
**Commit:** `security: regenerate JWT secret (prev. accidentally committed in .env)`

> KONTEKST: Plik `.env` był w historii gita (commit b57ddd5, 28.11.2025). Stary `JWT_SECRET`  
> jest skompromitowany. Wybrana opcja A — regeneracja bez czyszczenia historii.  
> Brak żywych użytkowników → unieważnienie tokenów bez konsekwencji.

Checkpoints:

- [ ] Sprawdzić że `backend/.env` jest w `.gitignore`
- [ ] `docker compose exec app php artisan jwt:secret` — generuje nowy secret w `.env`
- [ ] Dodać `JWT_SECRET=` (bez wartości) do `.env.example`
- [ ] Dodać notatkę w README: sekcja "Security Notes"

---

### Task 1.5 — APP_DEBUG i `.env.example` (AUDYT #3)

**Szacowany czas:** 15 min  
**Commit:** `chore: update .env.example with correct production defaults`

Checkpoints:

- [ ] Upewnić się że lokalny `.env` ma `APP_DEBUG=true` (dev)
- [ ] W `.env.example` ustawić `APP_DEBUG=false` (produkcja domyślnie false)
- [ ] Sprawdzić że `.env` NIE jest w `git status`

---

### Task 1.6 — Wyłączenie routes raportów (AUDYT #1)

**Szacowany czas:** 15 min  
**Commit:** `chore: disable report routes pending frontend-driven rewrite`  
**Plik:** `backend/routes/api.php`

> ReportService wywołuje `Schedule::` zamiast `Shift::` — wszystkie 3 endpointy raportów  
> rzucają błąd 500. Moduł będzie przepisany gdy powstanie frontend.

Checkpoints:

- [ ] Zakomentować routes raportów w `api.php`
- [ ] Dodać komentarz: `// TODO: rewrite ReportService using Shift model, pending frontend spec`
- [ ] Dodać notatkę w README

---

## Faza 2 — Poprawki wysokie (AUDYT #7–#11)

### Task 2.1 — Walidacja unikalności dostępności (AUDYT #7)

**Szacowany czas:** 15 min  
**Commit:** `fix: use authenticated user id in availability uniqueness validation`  
**Plik:** `backend/app/Http/Requests/StoreAvailabilityRequest.php` L34–36

Checkpoints:

- [ ] Zmienić `$this->user_id` na `$this->user()->id`
- [ ] Test: pracownik próbuje dodać duplikat dostępności → 422 (nie 500)

---

### Task 2.2 — Złe nazwy kolumn w migracji rollback (AUDYT #8)

**Szacowany czas:** 10 min  
**Commit:** `fix: correct column names in migration rollback method`  
**Plik:** `backend/database/migrations/2025_11_24_200342_add_fields_to_users_table.php` L47–57

Checkpoints:

- [ ] Poprawić `max_hours_per_month` → `max_minutes_per_month`
- [ ] Poprawić `max_hours_per_quarter` → `max_minutes_per_quarter`
- [ ] Poprawić `min_break_hours` → `min_break_minutes`
- [ ] `docker compose exec app php artisan migrate:rollback` — brak wyjątku

---

### Task 2.3 — Hasła w docker-compose.yml (AUDYT #9)

**Szacowany czas:** 20 min  
**Commit:** `fix: move docker-compose credentials to .env variables`  
**Plik:** `docker-compose.yml` L19–32

Checkpoints:

- [ ] Zastąpić hardcoded `shiftflow_pass` i `root` zmiennymi: `${DB_PASSWORD}`, `${DB_ROOT_PASSWORD}`
- [ ] Dodać `DB_PASSWORD=` i `DB_ROOT_PASSWORD=` do `.env.example`
- [ ] Sprawdzić że kontenery startują poprawnie po zmianie

---

### Task 2.4 — AvailabilityPolicy — pełna implementacja (AUDYT #10)

**Szacowany czas:** 45 min  
**Commit:** `fix: implement AvailabilityPolicy with role-based access control`  
**Plik:** `backend/app/Policies/AvailabilityPolicy.php` L13–44

> Uzgodniona logika:
>
> - **Admin:** pełny CRUD na dostępności wszystkich pracowników
> - **Manager:** tylko odczyt (viewAny, view) — nie może modyfikować dostępności
> - **Employee:** CRUD tylko na swojej własnej dostępności

Checkpoints:

- [ ] Zaimplementować `viewAny()` — admin i manager mogą, employee może (swoje)
- [ ] Zaimplementować `view()` — admin/manager zawsze, employee tylko swój rekord
- [ ] Zaimplementować `create()` — admin zawsze, employee tylko swój, manager nie
- [ ] Zaimplementować `update()` — admin zawsze, employee tylko swój, manager nie
- [ ] Zaimplementować `delete()` — admin zawsze, employee tylko swój, manager nie
- [ ] Test ręczny: manager próbuje edytować dostępność → 403

---

### Task 2.5 — Osobny endpoint dla shifts w Position (AUDYT #11)

**Szacowany czas:** 30 min  
**Commit:** `feat: add paginated shifts endpoint for position`  
**Plik:** `backend/app/Http/Controllers/Api/PositionController.php`

Checkpoints:

- [ ] Usunąć `shifts` z `$position->load(['creator', 'shifts'])` w `show()`
- [ ] Dodać metodę `shifts(Position $position)` zwracającą `$position->shifts()->paginate(20)`
- [ ] Dodać route: `GET /api/positions/{position}/shifts`
- [ ] Zaktualizować `api.php`

---

## Faza 3 — Redis

### Task 3.1 — Konfiguracja Redis jako cache driver

**Szacowany czas:** 20 min  
**Commit:** `feat: configure Redis as cache and queue driver`

Checkpoints:

- [ ] Dodać do `.env`: `CACHE_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `REDIS_HOST=redis`
- [ ] Zaktualizować `.env.example`
- [ ] Sprawdzić że `redis` service jest w `docker-compose.yml`
- [ ] `docker compose exec app php artisan cache:clear` — brak błędów

---

### Task 3.2 — JWT Blacklist (unieważnianie tokenów przy logout)

**Szacowany czas:** 45 min  
**Commit:** `feat: add Redis JWT blacklist on logout`

Checkpoints:

- [ ] Utworzyć `app/Services/JwtBlacklistService.php` (blacklist/isBlacklisted)
- [ ] Zaktualizować `AuthController::logout()` — wrzuca `jti` do Redis z TTL
- [ ] Utworzyć `app/Http/Middleware/CheckJwtBlacklist.php`
- [ ] Zarejestrować middleware w `bootstrap/app.php` na chronionych routach
- [ ] Test: po logout stary token → 401

---

## Faza 4 — Dokumentacja i porządki

### Task 4.1 — Migracja koloru pozycji (bonus z context.md)

**Szacowany czas:** 15 min  
**Commit:** `feat: add color field to positions table`

Checkpoints:

- [ ] `php artisan make:migration add_color_to_positions_table`
- [ ] `$table->string('color', 7)->nullable()->default('#6366f1')`
- [ ] Dodać `color` do `$fillable` w modelu `Position`

---

### Task 4.2 — README z sekcją "Planned Improvements" (AUDYT #12–#22)

**Szacowany czas:** 20 min  
**Commit:** `docs: add planned improvements section to README`

> Problemy z audytu które dokumentujemy jako świadomie odłożone:
>
> - Brak indeksów na `schedules` (created_by, status, published_at)
> - Brak walidacji parametrów w ReportController
> - Brak sprawdzenia `is_active` przy tworzeniu zmian
> - CORS permisywny (`*`)
> - Lazy loading w `AuthController::me()`
> - Brak paginacji w `AvailabilityController::index()`
> - `ShiftResource` — dzielenie przez null (`minutes_worked`)
> - Hardcoded `'TAK'` w CSV walidatorze
> - Niespójny format odpowiedzi API

Checkpoints:

- [ ] Sekcja "Known Limitations & Planned Improvements" w README
- [ ] Sekcja "Security Notes" — wzmianka o JWT historii + regeneracji

---

## Podsumowanie

| Faza             | Zadania | Szacowany czas |
| ---------------- | ------- | -------------- |
| 0 — PHPStan + CI | 3       | ~30 min        |
| 1 — Krytyczne    | 6       | ~1.5h          |
| 2 — Wysokie      | 5       | ~2h            |
| 3 — Redis        | 2       | ~1h            |
| 4 — Dokumentacja | 2       | ~35 min        |
| **RAZEM**        | **18**  | **~5.5–6h**    |

---

## Kontekst do README dla rekrutera (zebrane decyzje)

> Ta sekcja to surowe notatki — posłużą do napisania profesjonalnego README po zakończeniu poprawek.

**Co pokazuje ten projekt:**

- REST API w Laravel 12 (PHP 8.2) do zarządzania harmonogramami pracowników
- JWT auth z dwoma flow (password dla admin/manager, PIN dla employees)
- Walidacja w łańcuchu 7 walidatorów (Chain of Responsibility pattern)
- Batch insert zmian w jednej transakcji DB
- Import pracowników z CSV (pipeline: extract → validate → assemble → persist)
- Role-based access control (RBAC) — 3 role: admin, manager, employee
- Static analysis (PHPStan/Larastan level 2 + baseline)
- CI pipeline (GitHub Actions)
- Redis — JWT blacklist + cache driver

**Świadome decyzje techniczne warte wyjaśnienia w README:**

- PIN `1234` jako default — celowy UX (onboarding flow, pracownik zmienia sam)
- Czas w minutach w DB — unika błędów zaokrąglania przy obliczaniu nadgodzin
- Moduł raportów wyłączony — czeka na specyfikację frontendu (React SPA w planie)
- Soft deletes na User — dane historyczne muszą być zachowane

**Incydent bezpieczeństwa do udokumentowania:**

- `.env` przypadkowo w historii gita (commit b57ddd5) — JWT_SECRET zregenerowany
- Brak produkcyjnych użytkowników w momencie wykrycia
