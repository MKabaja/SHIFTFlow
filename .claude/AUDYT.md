# Audyt bezpieczeństwa i jakości kodu — SHIFTFlow Backend

**Data audytu:** 2026-04-22  
**Zakres:** `backend/app/`, `backend/routes/`, `backend/config/`, `backend/database/`, `backend/.env`, `docker-compose.yml`  
**Łączna liczba problemów:** 22

---

## Legenda

| Poziom | Znaczenie |
|--------|-----------|
| KRYTYCZNY | Działa nieprawidłowo na produkcji lub umożliwia przejęcie konta/systemu |
| WYSOKI | Poważny błąd logiki, luka bezpieczeństwa lub dane mogą ulec uszkodzeniu |
| ŚREDNI | Problem wpływający na bezpieczeństwo, wydajność lub spójność danych |
| NISKI | Dług techniczny, zła praktyka, ryzyko przyszłych bugów |

---

## Problemy krytyczne

### 1. ReportService odpytuje zły model — wszystkie endpointy raportów rzucają wyjątek runtime

- **Plik:** `backend/app/Services/ReportService.php`, linie 73–135
- **Poziom:** KRYTYCZNY

`ReportService` wywołuje `Schedule::with('position')`, `Schedule::active()`, `Schedule::where('user_id', ...)`, `Schedule::whereMonth('date', ...)` itd. Model `Schedule` nie posiada żadnej z tych relacji ani scope'ów — należą one do modelu `Shift`. W efekcie każde wywołanie `generateHourlyReport`, `generatePayrollReport` i `generateCoverageReport` rzuca `BadMethodCallException` lub `QueryException`, ponieważ kolumny `user_id`, `date`, `hours_worked` nie istnieją w tabeli `schedules`.

**Wszystkie trzy endpointy raportów (`GET /reports/{user}`, `GET /reports/payroll`, `GET /reports/coverage`) są niefunkcjonalne i zwracają błąd 500.**

---

### 2. Hardcoded PIN `1234` dla wszystkich importowanych pracowników

- **Plik:** `backend/app/Repositories/EmployeeRepository.php`, linia 142
- **Poziom:** KRYTYCZNY

Podczas importu pracowników z CSV pole `pin_hashed` jest ustawiane na stałą wartość `1234`:

```php
'pin_hashed' => 1234,
```

PIN ten jest następnie bcrypt-haszowany przez cast modelu, ale wartość źródłowa jest zawsze taka sama. Każdy zaimportowany pracownik ma PIN `1234`, co oznacza że dowolna osoba znająca login pracownika może się zalogować.

---

### 3. `APP_DEBUG=true` — ujawnianie wewnętrznych szczegółów aplikacji

- **Plik:** `backend/.env`, linia 4
- **Poziom:** KRYTYCZNY

Tryb debugowania jest włączony. Każdy błąd aplikacji zwraca pełny stack trace z nazwami plików, zmiennymi środowiskowymi i strukturą katalogów. Jest to krytyczne naruszenie w środowisku produkcyjnym lub staging.

---

### 4. Brak sprawdzenia `is_active` podczas logowania

- **Plik:** `backend/app/Http/Controllers/Api/AuthController.php`, linie 16–71
- **Poziom:** KRYTYCZNY

Oba endpointy logowania (`login` i `loginPin`) nie weryfikują, czy użytkownik ma `is_active = true`. Deaktywowany pracownik może się zalogować i uzyskać ważny token JWT. Deaktywacja użytkownika nie ma żadnego efektu na zdolność do uwierzytelnienia.

---

### 5. Brak rate limiting na endpointach logowania — możliwość brute-force

- **Plik:** `backend/routes/api.php`, linie 18–20
- **Poziom:** KRYTYCZNY

Endpointy `POST /api/auth/login` i `POST /api/auth/login-pin` nie mają żadnego throttlingu. Czterocyfrowy PIN ma tylko 10 000 kombinacji. Atakujący może sprawdzić wszystkie możliwe PINy bez żadnych ograniczeń czasowych ani blokad.

---

## Problemy wysokie

### 6. JWT_SECRET potencjalnie dostępny w repozytorium git

- **Plik:** `backend/.env`, linia 79
- **Poziom:** WYSOKI

`JWT_SECRET` jest ustawiony bezpośrednio w pliku `.env`. Jeśli plik `.env` jest (lub był) śledzony przez git, klucz jest skompromitowany. Posiadanie tego sekretu pozwala sfabrykować ważny JWT token dla dowolnego użytkownika i ominąć całą autoryzację. Należy zweryfikować historię gita i w razie potrzeby wygenerować nowy sekret oraz unieważnić wszystkie aktywne tokeny.

---

### 7. Walidacja unikalności dostępności nie działa dla pracowników

- **Plik:** `backend/app/Http/Requests/StoreAvailabilityRequest.php`, linie 34–36
- **Poziom:** WYSOKI

Reguła `Rule::unique` używa `$this->user_id`, ale pracownicy nie wysyłają `user_id` w żądaniu (ID jest pobierane z zalogowanego użytkownika w kontrolerze). Dla pracowników `$this->user_id` ma wartość `null`, przez co walidacja unikalności odpytuje bazę o `WHERE user_id IS NULL` — zawsze zwraca brak duplikatów. Duplikaty są blokowane dopiero przez constraint bazy danych, ale zwracają nieczytelny błąd 500 zamiast walidacyjnego 422.

---

### 8. Rollback migracji wysypuje się z powodu błędnych nazw kolumn

- **Plik:** `backend/database/migrations/2025_11_24_200342_add_fields_to_users_table.php`, linie 47–57
- **Poziom:** WYSOKI

Metoda `down()` próbuje usunąć kolumny o nazwach `max_hours_per_month`, `max_hours_per_quarter` i `min_break_hours`, które nie istnieją. Faktyczne nazwy kolumn to `max_minutes_per_month`, `max_minutes_per_quarter` i `min_break_minutes`. Wykonanie `php artisan migrate:rollback` na tej migracji rzuci wyjątek i może przerwać cały rollback.

---

### 9. Hardcoded dane logowania do bazy w docker-compose.yml

- **Plik:** `docker-compose.yml`, linie 19–32
- **Poziom:** WYSOKI

Hasła bazy danych (`shiftflow_pass`, `root`) są hardcoded bezpośrednio w `docker-compose.yml`. Jeśli ten plik jest w repozytorium git, dane dostępowe są ujawnione każdemu z dostępem do kodu. Należy używać pliku `.env` z `docker-compose` zamiast wpisywać sekrety wprost.

---

### 10. `AvailabilityPolicy` — metody create/update/view zwracają `false` na stałe

- **Plik:** `backend/app/Policies/AvailabilityPolicy.php`, linie 13–44
- **Poziom:** WYSOKI

Metody `viewAny`, `view`, `create` i `update` zawsze zwracają `false`. Polityka jest zarejestrowana w systemie, ale jej metody blokują każdą operację. Jeśli w przyszłości zostanie dodane `$this->authorize('create', Availability::class)`, dostęp będzie zawsze odmówiony. Tylko metoda `delete` ma poprawną implementację. Polityka jest de facto martwym kodem, który wprowadza fałszywe poczucie bezpieczeństwa.

---

### 11. `PositionController::show()` ładuje wszystkie shifts bez paginacji

- **Plik:** `backend/app/Http/Controllers/Api/PositionController.php`, linia 43
- **Poziom:** WYSOKI

```php
return $position->load(['creator', 'shifts']);
```

Endpoint `GET /api/positions/{position}` ładuje do pamięci wszystkie zmiany przypisane do danej pozycji. W kopalni z latami historycznych danych może to zwrócić dziesiątki tysięcy rekordów w jednym żądaniu, powodując timeout lub wyczerpanie pamięci.

---

## Problemy średnie

### 12. Brak indeksów na kolumnach często filtrowanych w tabeli `schedules`

- **Plik:** `backend/database/migrations/2025_11_25_210000_create_schedules_table.php`
- **Poziom:** ŚREDNI

Tabela `schedules` indeksuje tylko `[month, year]`. Brakuje indeksów na:
- `created_by` (foreign key bez indeksu — wolne JOINy z użytkownikami)
- `status` (filtrowane w wielu zapytaniach)
- `published_at` (brak indeksu przy filtrowaniu po dacie publikacji)

---

### 13. Brak walidacji parametrów w `ReportController::coverageSummary` i `payrollSummary`

- **Plik:** `backend/app/Http/Controllers/Api/ReportController.php`, linie 35–58
- **Poziom:** ŚREDNI

Parametry `month`, `year`, `date` są pobierane bez walidacji formatu:

```php
$date = (string) $request->query('date', now()->format('Y-m-d'));
$month = (int) $request->query('month', now()->month);
```

Przekazanie nieprawidłowej daty (np. `date=invalid-date`) może spowodować błąd 500. Brakuje `FormRequest` z walidacją.

---

### 14. Brak weryfikacji `is_active` przy tworzeniu zmian

- **Plik:** `backend/app/Services/Validation/ValidationService.php` i walidatory
- **Poziom:** ŚREDNI

Żaden z 7 walidatorów w łańcuchu nie sprawdza, czy pracownik (`is_active`) jest aktywny. Deaktywowany pracownik może mieć przypisane nowe zmiany przez endpoint `POST /api/schedules/{schedule}/shifts/batch` lub `POST /api/shifts`.

---

### 15. CORS skonfigurowany zbyt permisywnie

- **Plik:** `backend/config/cors.php`
- **Poziom:** ŚREDNI

Konfiguracja `allowed_origins: ['*']` i `allowed_methods: ['*']` pozwala na żądania cross-origin z dowolnej domeny i dowolną metodą HTTP. W docelowym wdrożeniu należy ograniczyć do konkretnej domeny frontendu React.

---

### 16. `AuthController::me()` — N+1 na relacji `positions`

- **Plik:** `backend/app/Http/Controllers/Api/AuthController.php`, linia 82
- **Poziom:** ŚREDNI

```php
'positions' => $user->positions,
```

Relacja `positions` jest ładowana leniwie (lazy loading). Nie jest to problem N+1 w klasycznym sensie (dotyczy jednego użytkownika), ale konfiguracja `Model::preventLazyLoading()` w trybie strict mode wyrzuci wyjątek. Należy użyć `$user->load('positions')` przed zwróceniem odpowiedzi.

---

### 17. Brak paginacji w `AvailabilityController::index()`

- **Plik:** `backend/app/Http/Controllers/Api/AvailabilityController.php`, linie 23–34
- **Poziom:** ŚREDNI

```php
->get();
```

Endpoint `GET /api/availabilities` dla admina/managera bez podania `user_id` zwraca **wszystkie** rekordy dostępności bez paginacji. Przy wielu pracownikach i dłuższym horyzoncie może to być bardzo duży zestaw danych.

---

## Problemy niskie

### 18. Komentarz z `dd()` pozostawiony w kodzie produkcyjnym

- **Plik:** `backend/app/Http/Controllers/Api/AuthController.php`, linia 47
- **Poziom:** NISKI

```php
// dd($validated);
```

Zakomentowany debug dump w metodzie `loginPin`. Wskazuje na tymczasowy kod debugowania — należy usunąć.

---

### 19. `ShiftResource` — dzielenie przez null zamiast zwrócenia null

- **Plik:** `backend/app/Http/Resources/ShiftResource.php`, linia 31
- **Poziom:** NISKI

```php
'hours_worked' => round($this->minutes_worked / 60, 2),
```

Kolumna `minutes_worked` jest nullable. Gdy jej wartość to `null`, PHP wykona `null / 60 = 0`, zwracając `0` zamiast `null`. Klient otrzymuje `hours_worked: 0` dla zmian bez obliczonego czasu pracy, co może być mylące. Poprawna wersja: `$this->minutes_worked !== null ? round($this->minutes_worked / 60, 2) : null`.

---

### 20. Hardcoded string `'TAK'` w walidatorze CSV

- **Plik:** `backend/app/Services/Import/EmployeeCsvValidator.php` (okolice linii 131)
- **Poziom:** NISKI

Wykrywanie przypisanych pozycji w CSV bazuje na porównaniu z hardcoded stringiem `'TAK'`. Zmiana formatu CSV (np. na `tak` lub `Yes`) złamie import bez żadnego komunikatu o błędzie. Wartość powinna być stałą lub konfigurowalna.

---

### 21. Niespójny format odpowiedzi API

- **Pliki:** różne kontrolery
- **Poziom:** NISKI

Odpowiedzi API nie mają jednolitej struktury:
- `PositionController::store()` zwraca surowy model bez wrappera (`response()->json($position, 201)`)
- `EmployeeController::store()` opakowuje w `UserResource` z kluczem `message`
- `ScheduleController::destroy()` zwraca literówkę `'Schedule deleted Successfully'` (wielka `S`)

Brak spójnego standardu odpowiedzi utrudnia obsługę po stronie klienta.

---

### 22. `PositionController::index()` — brak paginacji

- **Plik:** `backend/app/Http/Controllers/Api/PositionController.php`, linia 17
- **Poziom:** NISKI

```php
$positionsQuery = Position::with('creator')->get();
```

Endpoint zwraca wszystkie pozycje jednocześnie bez paginacji. Przy obecnym zakresie projektu (20+ pozycji) nie jest krytyczne, ale warto zastosować `paginate()` dla spójności.

---

## Podsumowanie

| Poziom | Liczba |
|--------|--------|
| KRYTYCZNY | 5 |
| WYSOKI | 6 |
| ŚREDNI | 6 |
| NISKI | 5 |
| **RAZEM** | **22** |

### Priorytety działań

1. **Natychmiast:** Naprawić `ReportService` (zły model) — endpointy raportów nie działają.
2. **Natychmiast:** Usunąć hardcoded PIN `1234` z importu CSV.
3. **Natychmiast:** Wyłączyć `APP_DEBUG` w środowisku niedeweloperckim.
4. **Priorytet 1:** Dodać sprawdzenie `is_active` w `AuthController::login()` i `loginPin()`.
5. **Priorytet 1:** Dodać rate limiting (`throttle:5,1`) na `/api/auth/login` i `/api/auth/login-pin`.
6. **Priorytet 2:** Naprawić metodę `down()` migracji users (złe nazwy kolumn).
7. **Priorytet 2:** Naprawić `StoreAvailabilityRequest` — używać `$this->user()->id` zamiast `$this->user_id`.
8. **Priorytet 3:** Dodać paginację w `PositionController::show()` dla relacji `shifts`.
9. **Priorytet 3:** Dodać brakujące indeksy w migracji `schedules`.
