# 📋 PLAN REFAKTORU: SHIFTS vs SCHEDULES + VALIDATION SERVICE REFACTOR

---

## ⚡ EXECUTIVE SUMMARY

Dokument opisuje kompletny refaktor w następującej kolejności:

1. **Database** - zmiana nomenklatury (schedules → shifts) + nowa tabela schedules (1:N relacja)
2. **Models** - rename + nowe relacje
3. **ValidationService** - refactor z funkcji na klasę koordynatora + 5 dedykowanych klas validacyjnych
4. **Resources** - transformatory dla API
5. **Controllers** - update i nowe metody
6. **Tests** - wszystko testujemy

---

## 📊 FAZA 1: DATABASE MIGRATIONS

### 1.1: Plan zmian w bazie

**STARA STRUKTURA (ZŁO):**
```
schedules (zawiera pojedyncze shifty - mylące nazewnictwo)
├── id, user_id, position_id, date, shift_start, shift_end
├── hours_worked, status, notes
└── created_at, updated_at
```

**NOWA STRUKTURA (DOBRE):**
```
shifts (pojedyncze wpisy zmian - jasne)
├── id, schedule_id (FK), user_id, position_id, date
├── shift_start, shift_end, hours_worked, status, notes
└── created_at, updated_at

schedules (zbiorczy grafik - nowa tabela)
├── id, name, description, month, year
├── status (draft/published), published_at, created_by
└── created_at, updated_at
```

**Zmiana relacji:** M:N (złe) → 1:N (dobre)
- Jeden shift należy do JEDNEGO schedule'a (nie wielu!)
- Brak tabeli pośredniej `schedule_entries`

### 1.2: Kroki migracyjne (w kolejności)

#### **KROK 1: Rename tabeli `schedules` na `shifts`**

Słownie:
1. Utwórz migrację: `php artisan make:migration rename_schedules_to_shifts`
2. W `up()`: `Schema::rename('schedules', 'shifts');`
3. W `down()`: `Schema::rename('shifts', 'schedules');`
4. Uruchom: `php artisan migrate`
5. Sprawdź w bazie

---

#### **KROK 2: Utwórz tabelę `schedules` (nowa)**

Słownie:
1. Utwórz migrację: `php artisan make:migration create_schedules_table`
2. W `up()` stwórz tabelę z kolumnami:
   - `id` - primary key
   - `name` - string np. "Marzec 2026"
   - `description` - nullable text
   - `month` - unsignedTinyInteger (1-12)
   - `year` - year (2026, 2027 itd)
   - `status` - enum('draft', 'published') domyślnie draft
   - `published_at` - nullable timestamp
   - `created_by` - foreignId na users (nullable)
   - `timestamps()` - created_at, updated_at
3. Dodaj indeksy:
   - Composite index na `(month, year)` - szybkie wyszukiwanie
   - Index na `created_by` - szybkie wyszukiwanie grafików managera
4. Uruchom: `php artisan migrate`
5. Sprawdź w bazie

---

#### **KROK 3: Dodaj kolumnę `schedule_id` do `shifts`**

Słownie:
1. Utwórz migrację: `php artisan make:migration add_schedule_id_to_shifts`
2. W `up()` dodaj kolumnę:
   - `schedule_id` - foreignId na schedules (nullable na start, bo migrujemy dane)
   - relacja: `constrained('schedules')->onDelete('cascade')`
3. W `down()` dropnij kolumnę
4. Uruchom: `php artisan migrate`
5. Sprawdź w bazie

---

#### **KROK 4: Dodaj constraint FK na `position_id` w `shifts` (jeśli brakuje)**

Słownie:
1. Utwórz migrację: `php artisan make:migration add_position_fk_to_shifts`
2. W `up()` sprawdź czy FK istnieje, jeśli nie - dodaj
3. W `down()` dropnij FK
4. Uruchom: `php artisan migrate`

---

#### **KROK 5: Migruj istniejące dane**

Słownie:
1. Utwórz migrację data-fix: `php artisan make:migration migrate_old_schedules_to_new_structure`
2. W `up()`:
   - Dla każdego miesiąca (1-12 i każdego roku w bazie)
   - Utwórz Schedule z name="Styczeń 2026" itd, status='draft'
   - Pobierz wszystkie shifty z tego miesiąca
   - Update każdy shift: set `schedule_id` na nowo utworzony Schedule
3. W `down()`:
   - Usuń wszystkie Schedule'y
   - Update shifty: set `schedule_id` na NULL
4. Uruchom: `php artisan migrate`
5. Zweryfikuj dane

---

## 🤖 FAZA 2: MODELS

### 2.1: Rename modelu Schedule → Shift

Słownie:
1. Zmień nazwę pliku: `app/Models/Schedule.php` → `app/Models/Shift.php`
2. Otwórz plik i zmień:
   - Class declaration: `class Shift extends Model`
   - Property: `protected $table = 'shifts'`
   - `$fillable`: user_id, position_id, schedule_id, date, shift_start, shift_end, hours_worked, status, notes
   - `$casts`: date (date), shift_start (time), shift_end (time), hours_worked (integer)
3. Relacje (w metodach):
   - `user()` - belongsTo User ✅ (już jest?)
   - `position()` - belongsTo Position ✅ (już jest?)
   - `schedule()` - NEW: belongsTo Schedule ← dodaj tę!

---

### 2.2: Utwórz nowy model Schedule

Słownie:
1. `php artisan make:model Schedule`
2. Otwórz `app/Models/Schedule.php` i skonfiguruj:
   - `$fillable`: name, description, month, year, status, published_at, created_by
   - `$casts`: month (integer), year (integer), published_at (datetime), status (string)
3. Relacje (w metodach):
   - `shifts()` - NEW: hasMany Shift ← dodaj tę! (jeden schedule ma wiele shifty)
   - `creator()` - NEW: belongsTo User, foreignKey 'created_by' ← dodaj tę! (kto stworzył)

---

### 2.3: Update User model

Słownie:
1. Otwórz `app/Models/User.php`
2. Update istniejące relacje:
   - Jeśli masz `schedules()` - zmień na `shifts()` z `hasMany Shift`
3. Dodaj nową relację:
   - `createdSchedules()` - hasMany Schedule, foreignKey 'created_by'
4. Update importy na górze: `use App\Models\Shift;`

---

### 2.4: Update Position model

Słownie:
1. Otwórz `app/Models/Position.php`
2. Zmień relację:
   - STARA: `schedules()` - hasMany Schedule
   - NOWA: `shifts()` - hasMany Shift
3. Update import: `use App\Models\Shift;`

---

## ✨ FAZA 3: VALIDATION SERVICE REFACTOR

### 3.1: Architektura nowej struktury

**Cel:** Zmienić z loose function na klasy w stylu CleanCode

**Struktura:**
```
ValidationService (Coordinator)
├── PositionPermissionValidator (klasa)
├── AvailabilityValidator (klasa)
├── TimeConflictValidator (klasa)
├── MinimumBreakValidator (klasa)
├── MaxHoursPerMonthValidator (klasa)
└── PositionUniquenessValidator (NEW - klasa)
```

**Flow:**
```
Controller
  ↓
ValidationService->validate($user, $positionId, $date, $shiftStart, $shiftEnd, $ignoreShiftId)
  ├→ PositionPermissionValidator->validate()
  ├→ AvailabilityValidator->validate()
  ├→ TimeConflictValidator->validate()
  ├→ MinimumBreakValidator->validate()
  ├→ MaxHoursPerMonthValidator->validate()
  └→ PositionUniquenessValidator->validate()
```

**Reguła nowa:** PositionUniquenessValidator
- W danym dniu, tylko JEDNA osoba może pracować na danej pozycji
- Jeśli na dzień 15.01 już jest ktoś na B1, inna osoba NIE może być na B1 tego samego dnia
- Exception: jeśli edytujesz istniejący shift (ignoreShiftId), to się pomija

---

### 3.2: Plany poszczególnych klas

#### **Klasa 1: TimeHelper (utility - opcjonalna)**

Słownie:
- Private klasa do konwersji date/time formátów
- Metoda: `formatDateForQuery(string $date): string` - normalizuje do Y-m-d
- Metoda: `formatTimeForQuery(string $time): string` - normalizuje do H:i
- Metoda: `createFullDateTime(string $date, string $time): Carbon` - łączy
- Metoda: `adjustTimeIfNextDay(string $startTime, string $endTime): string` - jeśli shift nocny (end < start), dodaj dzień do end

---

#### **Klasa 2: PositionPermissionValidator**

Słownie:
- Public metoda: `validate(User $user, int $positionId): void` - wyrzuca exception jeśli brak uprawnień
- Private metoda: `getUserPositionIds(User $user): array` - pobiera IDs pozycji użytkownika
- Private metoda: `throwIfNotPermitted(int $positionId, array $allowedIds): void` - wyrzuca ValidationException

---

#### **Klasa 3: AvailabilityValidator**

Słownie:
- Public metoda: `validate(int $userId, string $date): void`
- Private metoda: `getAvailabilityRecord(int $userId, string $date): ?Availability` - zapytanie do bazy
- Private metoda: `throwIfUnavailable(bool $isAvailable, string $date): void`

---

#### **Klasa 4: TimeConflictValidator**

Słownie:
- Public metoda: `validate(int $userId, string $date, string $shiftStart, string $shiftEnd, ?int $ignoreShiftId): void`
- Private metoda: `findConflictingShift(...)` - SQL query z whenIgnore logic
- Private metoda: `timeRangesOverlap(string $s1, string $e1, string $s2, string $e2): bool` - helper do porównywania czasów
- Private metoda: `throwIfConflict(bool $hasConflict): void`

---

#### **Klasa 5: MinimumBreakValidator**

Słownie:
- Public metoda: `validate(int $userId, string $date, string $shiftStart, int $minBreakHours): void`
- Private metoda: `getLastShift(int $userId, string $date): ?Shift` - ostatni shift przed nowym
- Private metoda: `calculateBreakHours(Carbon $prevEnd, Carbon $newStart): float` - w godzinach
- Private metoda: `handleNightShift(Carbon $endTime, string $endTimeStr): void` - jeśli nocny, addDay
- Private metoda: `throwIfBreakTooShort(float $breakHours, int $minBreak): void`

---

#### **Klasa 6: MaxHoursPerMonthValidator**

Słownie:
- Public metoda: `validate(int $userId, string $date, string $shiftStart, string $shiftEnd, int $maxHours, ?int $ignoreShiftId): void`
- Private metoda: `getMonthBoundaries(string $date): array` - [startOfMonth, endOfMonth]
- Private metoda: `getTotalHoursInMonth(int $userId, string $date, ?int $ignoreShiftId): float` - sum z bazy
- Private metoda: `calculateNewShiftHours(string $date, string $start, string $end): float`
- Private metoda: `throwIfExceeded(float $total, int $max): void`

---

#### **Klasa 7: PositionUniquenessValidator (NOWA)**

Słownie:
- Public metoda: `validate(int $positionId, string $date, ?int $ignoreShiftId): void`
- Private metoda: `shiftExistsForPositionOnDate(int $positionId, string $date, ?int $ignoreShiftId): bool` - SQL query
- Private metoda: `throwIfPositionTaken(bool $exists, int $positionId, string $date): void`

---

#### **Klasa 8: ValidationService (Coordinator)**

Słownie:
- Constructor: wstrzyknąć dependency injection dla wszystkich 6 validatorów (lub sami je instancjować)
- Public metoda: `validate(User $user, int $positionId, string $date, string $shiftStart, string $shiftEnd, ?int $ignoreShiftId = null): void`
  - Sequence: Position → Availability → TimeConflict → MinBreak → MaxHours → PositionUniqueness
  - Każdy validator.validate() - jeśli throws ValidationException, się propaguje do controllera
- Private helper: `extractUserConstraints(User $user): array` - pobiera maxHoursPerMonth, minBreakHours z user obiektu

---

### 3.3: Zasady CleanCode dla każdej klasy

1. **Single Responsibility Principle** - każda klasa validuje ONE rzecz
2. **Method Naming** - publiczna metoda zawsze `validate()`, private metody to `calculateXxx()`, `getXxx()`, `throwIfXxx()`
3. **Early Returns** - jeśli walidacja pass, nie wyrzucaj exception
4. **No God Methods** - każda metoda <20 linii kodu
5. **Dependency Injection** - konstruktor przyjmuje co trzeba (bazy, helpy)
6. **Private Methods** - wszystkie helpery to private, pokaż tylko `public validate()`
7. **Immutability** - nie mutuj input'y, zwracaj nowe obiekty (Carbon obiekty to OK mutować bo value objects)

---

## 📨 FAZA 4: RESOURCES (TRANSFORMERS)

### 4.1: Struktura Resources

```
Resources/
├── ShiftResource.php (minimal, bez pełnych user/position objects)
├── ScheduleResource.php (szczegółowy, ze shifty wewnątrz)
├── ScheduleListResource.php (zwięzły, bez shifty)
├── PositionResource.php
└── UserResource.php
```

### 4.2: Każdy Resource słownie

**ShiftResource:**
- Zwraca: id, user_id, user_name, position_id, position_name, date, shift_start, shift_end, hours_worked, status, notes, created_at
- Nie zwraca: full user/position objects, pin_hashed
- Format: daty ISO8601, time HH:mm

**ScheduleResource:**
- Zwraca: id, name, month, year, status, published_at, created_by (imię), total_shifts, shifts (transformowane przez ShiftResource), created_at
- Używany: GET /api/schedules/{id} (szczegółowy widok)

**ScheduleListResource:**
- Zwraca: id, name, month, year, status, published_at, created_by, total_shifts, created_at
- Nie zwraca: shifts (bo to lista, byłoby dużo danych)
- Używany: GET /api/schedules (lista)

**PositionResource & UserResource:**
- Analogicznie, minimalne dane

---

## 🎮 FAZA 5: CONTROLLERS

### 5.1: Rename + Update ShiftController

Słownie:
1. Rename: `ScheduleController.php` → `ShiftController.php`
2. Update: class declaration, imports, model references
3. Każda metoda (index, store, show, update, destroy):
   - Zamiast zwracać model, zwracaj przez Resource
   - Eager load `with(['user', 'position', 'schedule'])`
   - Validuj przed create/update: `$this->validationService->validate(...)`

---

### 5.2: Utwórz nowy ScheduleController

Słownie - metody:

**`index()`**
- Query: `Schedule::with(['creator'])->get()`
- Filter: jeśli query params month/year, dodaj where
- Response: `ScheduleListResource::collection()`

**`store()`**
- Validuj: name (required), description (nullable), month (1-12), year (>= 2026)
- Create: Schedule z created_by = auth()->id()
- Response: `ScheduleResource::make()` + HTTP 201

**`show(int $id)`**
- Query: `Schedule::with(['creator', 'shifts.user', 'shifts.position'])->find($id)`
- Response: `ScheduleResource::make()`

**`update(int $id)`**
- Validuj: name, description (tylko te pola, nie month/year!)
- Update Schedule
- Response: `ScheduleResource::make()`

**`destroy(int $id)`**
- Delete Schedule (cascade usunie shifts)
- Response: JSON message

**`addShifts(int $id)` (CUSTOM)**
- Body: array `shift_ids`
- Validuj: shift_ids istnieją, wszystkie należą do tego miesiąca
- Update: `$schedule->shifts()->syncWithoutDetaching($shift_ids)`
- Response: `ScheduleResource::make()` ze shifty

**`publish(int $id)` (CUSTOM)**
- Validuj: każdy shift w schedule'ie (deleguj do ValidationService)
- Jeśli OK: update Schedule → status='published', published_at=now()
- Jeśli błąd: zwróć 422 z listą błędów
- Response: `ScheduleResource::make()`

---

### 5.3: Update routes w `routes/api.php`

Słownie:
```
Route::apiResource('shifts', ShiftController::class);
Route::apiResource('schedules', ScheduleController::class);
Route::post('/schedules/{schedule}/shifts', [ScheduleController::class, 'addShifts']);
Route::post('/schedules/{schedule}/publish', [ScheduleController::class, 'publish']);
```

---

## ✅ FAZA 6: DATABASE SEEDERS

### 6.1: Rename ScheduleSeeder → ShiftSeeder

Słownie:
1. Rename plik
2. Update class name, imports
3. Logika: tworzysz shifty dla każdego pracownika

---

### 6.2: Utwórz nowy ScheduleSeeder

Słownie:
1. Create 2-3 Schedule'y (Styczeń, Luty, Marzec 2026)
2. Dla każdego Schedule:
   - Pobierz wszystkie shifty z tego miesiąca
   - Update ich schedule_id na nowo utworzony Schedule

---

### 6.3: Update DatabaseSeeder

Słownie:
1. Zadbaj aby ShiftSeeder był przed ScheduleSeeder (najpierw shifty, potem schedule'y)

---

## 🧪 FAZA 7: TESTS

### 7.1: Rename ShiftTest

Słownie:
1. Rename pliku, class declaration
2. Update wszystkie testy - zmień Schedule na Shift, /api/schedules na /api/shifts

---

### 7.2: Utwórz ScheduleTest

Słownie - test cases:
```
test_manager_can_create_schedule()
test_can_list_schedules()
test_can_view_schedule_with_shifts()
test_can_add_shifts_to_schedule()
test_can_publish_schedule()
test_cannot_publish_invalid_schedule()
```

---

### 7.3: Utwórz ValidationServiceTest

Słownie - test cases (po jednym dla każdego validator'a):
```
test_position_permission_validation()
test_availability_validation()
test_time_conflict_validation()
test_minimum_break_validation()
test_max_hours_per_month_validation()
test_position_uniqueness_validation()
```

---

## 📋 QUICK CHECKLIST - KOLEJNOŚĆ PRAC

```
FAZA 1: DATABASE (1-2h)
├─ [ ] Migracja 1: rename schedules → shifts
├─ [ ] Migracja 2: create schedules (nowa tabela)
├─ [ ] Migracja 3: add schedule_id to shifts
├─ [ ] Migracja 4: add position_fk to shifts (jeśli brakuje)
├─ [ ] Migracja 5: data migration (stare dane do nowej struktury)
└─ [ ] Sprawdzenie w bazie ✅

FAZA 2: MODELS (1h)
├─ [ ] Rename Schedule.php → Shift.php
├─ [ ] Shift model: relacje (user, position, schedule)
├─ [ ] Schedule model: nowy (relacje: shifts, creator)
├─ [ ] User model: update (shifts, createdSchedules)
├─ [ ] Position model: update (shifts)
└─ [ ] php artisan tinker - test relacji ✅

FAZA 3: VALIDATION SERVICE (3-4h)
├─ [ ] TimeHelper klasa (utility)
├─ [ ] PositionPermissionValidator klasa
├─ [ ] AvailabilityValidator klasa
├─ [ ] TimeConflictValidator klasa
├─ [ ] MinimumBreakValidator klasa
├─ [ ] MaxHoursPerMonthValidator klasa
├─ [ ] PositionUniquenessValidator klasa (NEW)
├─ [ ] ValidationService klasa (coordinator)
└─ [ ] Testuj każdy validator ✅

FAZA 4: RESOURCES (2h)
├─ [ ] ShiftResource
├─ [ ] ScheduleResource
├─ [ ] ScheduleListResource
├─ [ ] PositionResource
├─ [ ] UserResource
└─ [ ] Test w Postmanie: JSON struktura OK ✅

FAZA 5: CONTROLLERS (2-3h)
├─ [ ] Rename ScheduleController → ShiftController
├─ [ ] Update ShiftController (wszystkie metody zwracają Resources)
├─ [ ] Create ScheduleController (index, store, show, update, destroy, addShifts, publish)
├─ [ ] Update routes w routes/api.php
└─ [ ] Postman: testy wszystkich endpointów ✅

FAZA 6: SEEDERS (1h)
├─ [ ] Rename ScheduleSeeder → ShiftSeeder
├─ [ ] Create ScheduleSeeder
├─ [ ] Update DatabaseSeeder (kolejność)
└─ [ ] php artisan migrate:fresh --seed ✅

FAZA 7: TESTS (2-3h)
├─ [ ] Rename ShiftTest
├─ [ ] Create ScheduleTest
├─ [ ] Create ValidationServiceTest
└─ [ ] php artisan test → ALL PASS ✅

RAZEM: ~14-18h (2-2,5 dni roboczych)
```

---

## ⚠️ RZECZY NA KTÓRE UWAŻAĆ

1. **Kolejność migracji** - najpierw rename, potem create schedules, potem schedule_id do shifts
2. **Data migration** - zadbaj aby istniejące shifty zostały przypisane do schedules
3. **Foreign keys** - cascade delete, aby usunięcie schedule'a nie usunęło shifty
4. **Eager loading** - zawsze `with()` aby uniknąć N+1 problem
5. **ValidationService** - dependency injection każdego validator'a w konstruktorze
6. **PositionUniquenessValidator** - NEW RULE - sprawdzaj czy pozycja już zajęta w danym dniu
7. **Resources format** - ISO8601 dla dates, HH:mm dla times
8. **Tests** - każdy validator testuj osobno, potem integracyjnie ValidationService

---

## 🎯 DEFINICJA GOTOWOŚCI

**Projekt uważam za gotowy gdy:**
1. ✅ Wszystkie migracje przechodzą
2. ✅ Relacje między modelami działają (tinker testy)
3. ✅ Każda klasa walidatora ma public `validate()` method
4. ✅ ValidationService koordynuje wszystkie validatory w response flow
5. ✅ Każdy controller zwraca Response transformowany przez Resource
6. ✅ Postman tests: wszystkie endpointy zwracają 2xx statusy
7. ✅ `php artisan test` - ALL PASS
8. ✅ Code review pod kątem CleanCode (method naming, SRP, no god methods)

---

**Gotowy? Zaczynamy od FAZY 1! 🚀**