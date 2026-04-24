# ShiftFlow — kontekst dla Claude Code

## O projekcie

ShiftFlow to REST API w Laravel (bez frontendu) do zarządzania harmonogramami i zmianami pracowników. Projekt portfolio — publiczne repo na GitHubie.

**Stack:** Laravel, MySQL, Redis, Docker (Laravel Sail), JWT Auth  
**PHP:** 8.2  
**Środowisko:** WSL2 + Docker

---

## Co robimy dziś

Trzy zadania w tej kolejności:

1. **Larastan + PHPStan** — instalacja, konfiguracja, baseline
2. **GitHub Actions** — CI z PHPStan na każdy push/PR
3. **Poprawki z audytu** — lista poniżej

---

## Zadanie 1 — Larastan + PHPStan

### Instalacja

```bash
composer require larastan/larastan --dev
```

### Konfiguracja — plik `phpstan.neon` w root projektu

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
    level: 2
    checkMissingIterableValueType: false
```

Zaczynamy od **poziomu 2** — nie wyżej. Na istniejącym projekcie wyższy poziom generuje zbyt dużo szumu.

### Generowanie baseline (ważne — robimy to przed pierwszym commitem)

```bash
./vendor/bin/phpstan analyse --generate-baseline
```

To tworzy plik `phpstan-baseline.neon` który "zamraża" wszystkie obecne błędy. Od tej chwili PHPStan krzyczy tylko na **nowy kod**. Stare błędy naprawiamy stopniowo.

Dodaj baseline do `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - app
    level: 2
    checkMissingIterableValueType: false
```

### Kolejność commitów

```bash
git add phpstan.neon phpstan-baseline.neon
git commit -m "chore: add Larastan with baseline"
```

---

## Zadanie 2 — GitHub Actions

Utwórz plik `.github/workflows/phpstan.yml`:

```yaml
name: PHPStan

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  phpstan:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer:v2

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse --memory-limit=256M
```

**Ważne:** `phpstan-baseline.neon` musi być wcommitowany zanim odpalisz Actions — inaczej CI będzie krzyczeć na stare błędy.

### CodeRabbit

Podpinasz przez stronę [coderabbit.ai](https://coderabbit.ai) — logujesz się GitHubem, wybierasz repo, gotowe. Darmowy dla publicznych repo, działa automatycznie na każdym PR. Zero konfiguracji po stronie kodu.

---

## Zadanie 3 — Poprawki z audytu

### Krytyczne — naprawić wszystkie

**#1 ReportService — martwy kod (błąd runtime na wszystkich endpointach raportów)**
- Moduł raportów będzie przepisany gdy powstanie frontend
- Rozwiązanie: zakomentować lub usunąć routes raportów, dodać notatkę w README
- Nie przepisywać teraz

**#2 Hardcoded PIN `1234` w imporcie CSV**
- To świadoma decyzja UX: każdy zaimportowany pracownik dostaje domyślny PIN, zmienia go przy pierwszym logowaniu (onboarding)
- Rozwiązanie: przenieść do `.env` jako `DEFAULT_EMPLOYEE_PIN=1234`
- W kodzie dodać komentarz wyjaśniający intencję
- `EmployeeRepository.php` linia ~142: `'pin_hashed' => config('app.default_employee_pin')`
- Dodać do `config/app.php`: `'default_employee_pin' => env('DEFAULT_EMPLOYEE_PIN', '1234')`

**#3 `APP_DEBUG=true`**
- Ustawić `APP_DEBUG=false` w środowisku niedeweloperckim
- Upewnić się że `.env` nie jest w repozytorium

**#4 Brak sprawdzenia `is_active` przy logowaniu**
- `AuthController.php` metody `login()` i `loginPin()`
- Po weryfikacji credentials dodać: `if (!$user->is_active) { return response()->json(['message' => 'Account deactivated'], 403); }`

**#5 Brak rate limiting na endpointach logowania**
- `routes/api.php`
- Dodać middleware `throttle:5,1` na `/api/auth/login` i `/api/auth/login-pin`
- Czterocyfrowy PIN ma tylko 10 000 kombinacji — bez throttlingu brute-force jest trywialny

### Wysokie — naprawić wszystkie

**#6 JWT_SECRET w historii git**
- Sprawdzić: `git log --all --full-history -- .env`
- Jeśli był śledzony: wygenerować nowy sekret (`php artisan jwt:secret`), unieważnić aktywne tokeny
- Upewnić się że `.env` jest w `.gitignore`

**#7 Walidacja unikalności dostępności nie działa dla pracowników**
- `StoreAvailabilityRequest.php` linie 34-36
- Zamienić `$this->user_id` na `$this->user()->id`

**#8 Rollback migracji — złe nazwy kolumn**
- `2025_11_24_200342_add_fields_to_users_table.php` metoda `down()`
- Poprawić nazwy: `max_hours_per_month` → `max_minutes_per_month`, analogicznie pozostałe dwie

**#9 Hardcoded dane w docker-compose.yml**
- Przenieść hasła do `.env`, w `docker-compose.yml` używać `${DB_PASSWORD}` itp.

**#10 `AvailabilityPolicy` — metody zawsze zwracają `false`**
- `AvailabilityPolicy.php` linie 13-44
- Zaimplementować poprawną logikę lub tymczasowo zwrócić `true` z komentarzem TODO

**#11 `PositionController::show()` — brak paginacji dla relacji shifts**
- Zamienić `$position->load(['creator', 'shifts'])` na paginowaną relację

### Średnie i niskie — wrzucić do README jako "Planned improvements"

Zamiast naprawiać teraz, udokumentować świadomość problemu:
- Brak indeksów na `schedules` (created_by, status, published_at)
- Brak walidacji parametrów w ReportController
- Brak sprawdzenia `is_active` przy tworzeniu zmian
- CORS zbyt permisywny (`*`)
- Lazy loading w `AuthController::me()` — użyć `$user->load('positions')`
- Brak paginacji w `AvailabilityController::index()`
- Zakomentowany `dd()` w AuthController linia 47 — usunąć
- `ShiftResource` — dzielenie przez null (`minutes_worked`)
- Hardcoded string `'TAK'` w CSV walidatorze
- Niespójny format odpowiedzi API

---

## Zadanie 4 — Redis

Laravel Sail ma Redis już w docker-compose — wystarczy włączyć i skonfigurować.

### Konfiguracja `.env`

```env
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Rate limiting przez Redis

W `AppServiceProvider::boot()` lub `RouteServiceProvider` upewnij się że rate limiter używa Redis — przy `CACHE_DRIVER=redis` działa automatycznie. Throttle na routach auth już jest w planie z audytu (`throttle:5,1`).

### JWT Blacklista (unieważnianie tokenów przy logout)

To główna wartość Redisa w tym projekcie. Bez blacklisty logout nie unieważnia tokena — ktoś kto przechwyci token może go używać do wygaśnięcia.

**Jak działa:**
- Przy logout wrzucasz `jti` (JWT ID) do Redisa z TTL równym pozostałemu czasowi życia tokena
- Middleware sprawdza przy każdym żądaniu czy token nie jest na blackliście

**Implementacja — `app/Services/JwtBlacklistService.php`:**

```php
class JwtBlacklistService
{
    public function blacklist(string $jti, int $ttl): void
    {
        Redis::setex("jwt_blacklist:{$jti}", $ttl, 'true');
    }

    public function isBlacklisted(string $jti): bool
    {
        return (bool) Redis::exists("jwt_blacklist:{$jti}");
    }
}
```

**W `AuthController::logout()`:**

```php
public function logout(): JsonResponse
{
    $payload = JWTAuth::parseToken()->getPayload();
    $jti = $payload->get('jti');
    $ttl = $payload->get('exp') - now()->timestamp;

    $this->blacklistService->blacklist($jti, $ttl);

    JWTAuth::invalidate();

    return response()->json(['message' => 'Logged out']);
}
```

**Middleware `app/Http/Middleware/CheckJwtBlacklist.php`:**

```php
public function handle(Request $request, Closure $next): Response
{
    $payload = JWTAuth::parseToken()->getPayload();
    $jti = $payload->get('jti');

    if ($this->blacklistService->isBlacklisted($jti)) {
        return response()->json(['message' => 'Token has been revoked'], 401);
    }

    return $next($request);
}
```

Zarejestrować middleware w `bootstrap/app.php` na chronionych routach.

### Commit

```
feat: add Redis JWT blacklist on logout
feat: configure Redis as cache driver for rate limiting
```

---



Przy okazji — dodać kolumnę `color` do tabeli `positions`. Frontend będzie jej potrzebował do wyróżniania pozycji na harmonogramie.

```bash
php artisan make:migration add_color_to_positions_table
```

```php
$table->string('color', 7)->nullable()->default('#6366f1'); // hex color
```

Pamiętaj o dodaniu do `$fillable` w modelu `Position`.

---

## Commit strategy

Każda poprawka jako osobny commit z opisowym message:

```
fix: add is_active check on login endpoints
fix: add rate limiting on auth routes  
fix: move default PIN to env config
chore: disable report routes pending frontend rewrite
fix: correct column names in migration rollback
```

Dzięki temu historia gita wygląda profesjonalnie i CodeRabbit ma co analizować.
