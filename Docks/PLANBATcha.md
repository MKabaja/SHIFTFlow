# 📋 PLAN IMPLEMENTACJI BATCH SCHEDULING

**Projekt:** Backend MVP - Kopalnia Soli Wieliczka  
**Cel:** Excel-like tworzenie całego grafiku (Schedule + wiele Shiftów) z pełną walidacją [file:1][file:2][file:13]  
**Estymacja:** 16–20h (8–9 sesji po ~2h)

---

## 🎯 Biznesowa Logika

### Flow użytkownika (manager):

1. Tworzy pusty grafik (Schedule) w statusie `draft`.
2. W widoku excel-like wypełnia komórki (shifty) dla danego miesiąca.
3. Wysyła **batch** wszystkich shiftów do backendu.
4. Dostaje albo:
   - 201 + lista utworzonych shiftów, albo
   - 422 + mapa błędów przypięta do `client_temp_id` (wiersze/komórki).
5. Po naprawieniu błędów i poprawnym batchu – publikuje grafik (`published`).

### Założenia domenowe:

- Każdy **Shift musi należeć do Schedule** (`schedule_id` jest wymagane biznesowo).
- `Schedule.status`:
  - `draft` – roboczy, widoczny tylko dla managerów.
  - `published` – graf opublikowany, widoczny dla pracowników.
- Pracownik widzi tylko shifty z `Schedule.status = 'published'`.
- Batch jest **atomiczny** – albo wszystkie shifty zostają utworzone, albo żaden.

---

## 🔌 Kontrakt API – Batch Endpoint

### `POST /api/schedules/{schedule}/shifts/batch`

**Request body (JSON):**

```json
{
  "shifts": [
    {
      "client_temp_id": "row_1",
      "user_id": 5,
      "position_id": 2,
      "date": "2026-01-15",
      "shift_start": "08:00",
      "shift_end": "16:00",
      "status": "scheduled",
      "notes": "opcjonalne"
    },
    {
      "client_temp_id": "row_2",
      "user_id": 7,
      "position_id": 3,
      "date": "2026-01-15",
      "shift_start": "14:00",
      "shift_end": "22:00",
      "status": "scheduled"
    }
  ]
}
```

**Response 201 (sukces):**

```json
{
  "message": "Batch created successfully",
  "count": 2,
  "shifts": [
    /* ShiftResource[] */
  ]
}
```

**Response 422 (błędy biznesowe):**

```json
{
  "message": "Batch validation failed",
  "errors": {
    "row_1": {
      "conflict": ["Time conflict with existing shift"],
      "hours": ["Monthly limit exceeded by 4.0h"]
    },
    "row_2": {
      "permission": ["User has no permission for position"]
    }
  }
}
```

- Frontend dzięki client_temp_id może przypiąć błędy do konkretnych wierszy/komórek.

🏗 Architektura (SRP + Reuse)

```text
ScheduleController (API, skinny)
    ↓
ScheduleService (logika wokół Schedule)
    ↓
BatchPreprocessor (walidacja kształtu, sortowanie, grupowanie)
    ↓
BatchValidationService (walidacja batcha)
    ↓
ValidationService (JUŻ ISTNIEJE – walidacja pojedynczego shifta)
    ↓
Poszczególne validatory (TimeConflict, MinBreak, MaxHours..., itp.)
Dodatkowo:

ShiftValidationData (DTO) – rozszerzone o accumulatedBatchHours.

BatchResult – Value Object na wynik batcha: sukces lub błędy.
```

### SESJE IMPLEMENTACJI (ok. 2h każda)

- Każda sesja kończy się testami (Pest/Postman) i commitem.

### SESJA 1 – ScheduleController CRUD

**Cel: pełny CRUD dla Schedule, bez batcha.**

Kroki:

Wygeneruj kontroler:

`php artisan make:controller Api/ScheduleController --api`

- Upewnij się, że model Schedule ma:

* `$fillable = ['name','description','month','year','status','published_at','created_by']`

* `relacje: shifts(), creator().`

Zaimplementuj metody:

index():

pobiera listę Schedule z creator, z paginacją.

opcjonalne filtry month, year.

store():

użyje w kolejnej sesji Form Requestu (StoreScheduleRequest).

tworzy Schedule ze statusem draft i created_by = auth()->id().

show():

zwraca Schedule z relacjami shifts.user, shifts.position, creator.

update():

pozwala edytować tylko name, description.

destroy():

usuwa Schedule (kaskadowe usunięcie shiftów przez FK).

Dodaj routes:

php
Route::middleware(['auth:api', 'role:manager,admin'])
->apiResource('schedules', ScheduleController::class);
Testy (Pest – Feature):

test_manager_can_crud_schedules()

test_employee_cannot_access_schedules()

Sprawdzenie (manualnie/Postman):

POST /api/schedules → tworzy draft.

GET /api/schedules → zwraca listę.

GET /api/schedules/{id} → zwraca graf (jeszcze bez shiftów).

PUT /api/schedules/{id} → zmienia name.

DELETE /api/schedules/{id} → usuwa graf.

Commit:
:calendar: feat(api): ScheduleController CRUD

SESJA 2 – Form Requests + ScheduleService + Resource
Cel: przeniesienie walidacji do FormRequestów, wprowadzenie ScheduleService i ScheduleResource.

Kroki:

Utwórz Form Requests:

php artisan make:request StoreScheduleRequest

php artisan make:request UpdateScheduleRequest

W StoreScheduleRequest:

authorize() – tylko manager/admin.

rules() – name, month 1–12, year >= 2026, description nullable.

W UpdateScheduleRequest:

rules() – name sometimes|required, description nullable.

Utwórz serwis:

php artisan make:class Services/ScheduleService

create(array $data): Schedule → tworzy draft + created_by.

Podłącz serwis w ScheduleController@store():

wstrzyknij ScheduleService przez konstruktor.

Utwórz ScheduleResource:

zwracaj: id,name,description,month,year,status,published_at,creator_name,total_shifts.

Użyj Resource w show(), index().

Sprawdzenie:

POST /api/schedules bez name → 422 z komunikatami z FormRequest.

GET /api/schedules/{id} → JSON z polami z Resource.

Commit:
:hammer: refactor(services): ScheduleService + Schedule FormRequests + Resource

SESJA 3 – BatchPreprocessor
Cel: przygotowanie danych batch – walidacja kształtu, sortowanie, grupowanie po userze.

Kroki:

php artisan make:class Services/BatchPreprocessor

Metoda prepare(array $shifts, Schedule $schedule): Collection:

Waliduj:

że każde entry ma client_temp_id,user_id,position_id,date,shift_start,shift_end.

że date należy do miesiąca danego Schedule (sprawdź przez Carbon).

Grupuj:

collect($shifts)->groupBy('user_id').

Sortuj:

dla każdego usera posortuj po date i shift_start.

Zwróć:

Collection gdzie klucz = user_id, wartość = posortowana kolekcja shiftów.

Sprawdzenie (tinker):

Utwórz testowe dane, wywołaj app(BatchPreprocessor::class)->prepare($shifts, $schedule).

Sprawdź, że:

grupy są po user_id,

shifty posortowane rosnąco po date i shift_start.

Commit:
:package: feat(services): BatchPreprocessor grouping and sorting

SESJA 4 – BatchResult (Value Object)
Cel: ujednolicony typ zwracany przez ScheduleService dla operacji batch.

Kroki:

php artisan make:class ValueObjects/BatchResult

Dodaj:

prywatny konstruktor z polami array $shifts, array $errors.

statyczne konstruktory:

success(array $shifts): self

withErrors(array $errors): self

metody:

hasErrors(): bool

errors(): array

shifts(): array

count(): int

Sprawdzenie (tinker):

BatchResult::success([...]) → hasErrors() === false, count() > 0.

BatchResult::withErrors([...]) → hasErrors() === true.

Commit:
:recycle: feat(vo): BatchResult for batch outcomes

SESJA 5 – DTO rozszerzenie + MaxHours validators
Cel: umożliwić walidatorom limitów godzin rozróżnianie godzin z DB i godzin z batcha.

Kroki:

W ShiftValidationData dodaj pole:

public int $accumulatedBatchHours = 0 (np. jako ostatni parametr konstruktora z domyślną wartością).

W MaxHoursPerMonthValidator:

po pobraniu minutesInMonth z DB dodaj:

- $shift->accumulatedBatchHours \* 60.

Analogicznie w MaxHoursPerQuarterValidator.

Dodaj testy jednostkowe (Pest) dla obu walidatorów:

przypadek bez batcha (stare zachowanie),

przypadek, gdzie limit jest przekroczony dopiero po dodaniu accumulatedBatchHours.

Sprawdzenie:

php artisan test dla testów walidatorów – wszystkie PASS.

Ręcznie w tinker – utwórz DTO z accumulatedBatchHours i upewnij się, że walidator reaguje.

Commit:
:mag: feat(validation): Add accumulatedBatchHours support to hour limit validators

SESJA 6 – BatchValidationService
Cel: logika walidacji batcha (wewnętrzne konflikty + użycie istniejącego ValidationService).

Kroki:

php artisan make:class Services/BatchValidationService

Konstruktor:

wstrzyknij ValidationService.

Metoda validate(Collection $groupedShifts): array:

Dla każdego user_id:

inicjuj accumulatedHours = 0 i previousShiftsInBatch = [].

iteruj po shiftach:

odczytaj client_temp_id.

wywołaj validateInternalConflicts($current, $previousShiftsInBatch):

overlapy czasu w ramach batcha,

minimum break w ramach batcha.

zbuduj ShiftValidationData:

dane shifta + accumulatedBatchHours = $accumulatedHours + ignoreShiftId = null.

wywołaj ValidationService->validate($dto):

walidacja vs DB (re-use istniejących validatorów).

jeśli wyjątek:

zapisz błędy pod errors[$clientTempId].

jeśli OK:

zaktualizuj accumulatedHours o godziny tego shifta,

dodaj obecny shift do previousShiftsInBatch.

Zwróć tablicę $errors (może być pusta, gdy brak błędów).

Metoda validateInternalConflicts(array $current, array $previous):

sprawdza:

overlapping (przedziały czasowe nachodzą się),

min break (odstęp w godzinach).

w razie problemu rzuca ValidationException z komunikatami pod kluczami conflict/break.

Sprawdzenie (Pest – Unit):

test_internal_time_overlap_throws_error()

test_minimum_break_violation_throws_error()

test_accumulated_hours_passed_to_dto()

test_errors_are_mapped_by_client_temp_id()

Commit:
:test_tube: feat(services): BatchValidationService with internal conflicts and error mapping

SESJA 7 – StoreBatchShiftsRequest + ScheduleService::addShiftsBatch + Endpoint
Cel: spięcie pipeline’u: request → preprocessor → batch validation → zapis w transakcji → odpowiedź.

Kroki:

php artisan make:request StoreBatchShiftsRequest

authorize() – manager/admin.

rules():

shifts – required|array|min:1.

shifts.\*.client_temp_id – required|string.

shifts.\*.user_id – required|exists:users,id.

shifts.\*.position_id – required|exists:positions,id.

shifts.\*.date – required|date.

shifts.\*.shift_start – required|date_format:H:i.

shifts.\*.shift_end – required|date_format:H:i.

W ScheduleService dodaj addShiftsBatch(Schedule $schedule, array $shiftsData): BatchResult:

użyj BatchPreprocessor → prepare.

użyj BatchValidationService → validate.

jeśli errors niepuste → BatchResult::withErrors.

jeśli OK:

w DB::transaction:

licz hours_worked (możesz użyć TimeHelper którego już masz),

twórz shifty z schedule_id.

zwróć BatchResult::success($createdShifts).

W ScheduleController dodaj metodę:

addShiftsBatch(Schedule $schedule, StoreBatchShiftsRequest $request).

użyj ScheduleService::addShiftsBatch.

jeśli hasErrors():

zwróć 422 + errors (pod client_temp_id).

jeśli sukces:

201 z count + ShiftResource::collection.

Dodaj route:

php
Route::post('/schedules/{schedule}/shifts/batch', [ScheduleController::class, 'addShiftsBatch'])
->middleware(['auth:api', 'role:manager,admin']);
Sprawdzenie (Postman):

POST /api/schedules/{id}/shifts/batch z poprawnymi danymi → 201, count > 0, shifty mają schedule_id.

Ten sam endpoint z błędami (np. konflikt, brak uprawnień) → 422, errors ma klucze zgodne z client_temp_id.

Commit:
:sparkles: feat(api): Batch shifts endpoint for schedules

SESJA 8 – Publish + ShiftController cleanup
Cel: publikacja grafiku i domknięcie cyklu życia Schedule.

Kroki:

W ScheduleController dodaj metodę publish(Schedule $schedule):

(opcjonalnie) ponowna walidacja shifów z użyciem BatchValidationService – ale tym razem na danych z DB.

ustaw status = 'published', published_at = now().

zwróć ScheduleResource.

Route:

php
Route::post('/schedules/{schedule}/publish', [ScheduleController::class, 'publish'])
->middleware(['auth:api', 'role:manager,admin']);
W ShiftController:

pozostaw index, show, update, destroy.

usuń albo zablokuj store (np. abort(410, 'Use schedule batch endpoint')), żeby nowe shifty powstawały tylko przez Schedule.

Sprawdź, że ShiftController.index() dla roli employee nadal filtruje po schedule.status = 'published'.

Sprawdzenie (Postman – pełen flow):

POST /api/schedules → draft.

POST /api/schedules/{id}/shifts/batch → dodaje shifty.

POST /api/schedules/{id}/publish → zmienia status na published.

Zaloguj się jako employee:

GET /api/shifts → widzisz tylko shifty ze published schedules.

Commit:
:rocket: feat(api): Schedule publish flow + ShiftController creation disabled

SESJA 9 – Testy Pest: scenariusze end-to-end
Cel: upewnić się, że całość działa w TDD, nie tylko w Postmanie.

Kroki:

tests/Feature/ScheduleBatchTest.php:

test_manager_can_create_schedule_and_add_batch_shifts()

test_batch_returns_mapped_errors_for_each_client_temp_id()

test_publish_makes_shifts_visible_for_employees()

tests/Unit/BatchPreprocessorTest.php:

sortowanie + grupowanie + walidacja miesiąca.

tests/Unit/BatchValidationServiceTest.php:

konflikty wewnątrz batcha,

min break wewnątrz batcha,

limit godzin z akumulacją batcha.

Uruchom:

php artisan test

napraw ewentualne błędy.

Commit:
:white_check_mark: test: End-to-end Schedule batch + publish tests

✅ Definition of Done
Projekt batch scheduling jest ukończony, gdy:

Wszystkie sesje są zrealizowane i zacommitowane.

php artisan test przechodzi w całości.

Postman pokazuje poprawne zachowanie:

CRUD Schedule,

batch tworzenia shiftów,

publikacja grafiku,

widoczność tylko published dla pracowników.

Frontend korzysta z client_temp_id do mapowania błędów na komórki.
