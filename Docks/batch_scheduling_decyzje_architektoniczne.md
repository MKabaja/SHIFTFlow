# PLAN IMPLEMENTACJI BATCH SCHEDULING (TDD + Postman)

## SESJA 1: ScheduleController CRUD (2h)

---

## Zadania:

### 1.1 Generator Kontrolera

`php artisan make:controller Api/ScheduleController --api --tests`
Spodziewany output: ScheduleController.php + ScheduleTest.php

**Gdzie szukać:** `https://laravel.com/docs/12.x/controllers#resource-controllers`

### 1.2 Model Schedule (sprawdź czy masz)

```php
app/Models/Schedule.php
- $fillable: name, description, month, year, status, published_at, created_by
- $casts: month→integer, year→integer, published_at→datetime
- relacje: shifts()→hasMany(Shift), creator()→belongsTo(User, 'created_by')
```

`Test:` **php artisan tinker**

```php
$s = App\Models\Schedule::factory()->create();
$s->shifts()->count() === 0
$s->creator_id === null
```

### 1.3 Index Method

ScheduleController@index():

```php
- Query: Schedule::with('creator')->paginate(20)
- Filtry (query params):
  | month | year |
  | where('month', $month) & where('year', $year) |
- Authorization: middleware('auth:api', 'role:manager,admin')
```

Postman Test:

```json
GET /api/schedules?month=1&year=2026
Status: 200
Response: {data: [...], links: {...}}
```

**Pest Test:** `tests/Feature/ScheduleTest.php`

```php
test_manager_can_list_schedules()
test_manager_can_filter_by_month_year()
test_employee_cannot_list_schedules() // 403
```

### 1.4 Store Method

ScheduleController@store():

```php
- Request: StoreScheduleRequest (w sesji 2)
- auth()->id() → created_by
- status = 'draft' (domyślnie)
- Response: ScheduleResource::make($schedule), 201
```

**Postman Test:**

```json
POST /api/schedules
{
  "name": "Styczeń 2026",
  "month": 1,
  "year": 2026,
  "description": "Grafik na styczeń"
}
Status: 201
Response: {id: 1, status: "draft", created_by: YOUR_ID}
```

### 1.5 Show Method

```php
- Schedule::with(['creator', 'shifts.user', 'shifts.position'])->findOrFail($id)
- Response: ScheduleResource::make()
```

**Postman: GET** `/api/schedules/1 → 200 z pustymi shiftami`

### 1.6 Update Method

```text
- Tylko name + description (month/year immutable!)
- Response: 200 ScheduleResource
```

**Postman: PUT** `/api/schedules/1`

```json
{"name": "Styczeń 2026 v2"}
→ status nadal "draft"
```

### 1.7 Destroy Method

```php
- $schedule->delete() (cascade usuwa shifty)
- Response: {"message": "Schedule deleted"}
```

**Postman: DELETE** `/api/schedules/1`

### 1.8 Routes

`routes/api.php`

```php
Route::middleware(['auth:api', 'role:manager,admin'])
    ->group(function () {
        Route::apiResource('schedules', ScheduleController::class);
    });
```

### 1.9 Walidacja (tymczasowa)

- W kontrolerze (do sesji 2):

```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'month' => 'required|integer|between:1,12',
    'year' => 'required|integer|min:2026',
]);
```

Postman Test błędów: Brak name → 422 {"name": ["The name field is required."]}

#### ✅ SESJA 1 GOTOWA Gdy:

```php
[x] php artisan serve
[x] Postman: GET/POST/PUT/DELETE /api/schedules wszystkie 2xx
[x] php artisan test tests/Feature/ScheduleTest.php → 4 testy PASS

Commit: `:calendar: feat(api): ScheduleController CRUD + tests`
```

## SESJA 2: ScheduleService + Form Requests (2h)

---

## Zadania:

### 2.1 Form Request

- `php artisan make:request StoreScheduleRequest`
- `php artisan make:request UpdateScheduleRequest`

**StoreScheduleRequest:**

```php
authorize(): return auth()->user()?->role === 'manager' || 'admin'
rules():
'name' => 'required|string|max:255'
'month' => 'required|integer|between:1,12'
'year' => 'required|integer|min:2026'
'description' => 'nullable|string'
messages(): custom messages po polsku
```

**UpdateScheduleRequest:**

```php
'name' => 'sometimes|required|string|max:255'
'description' => 'nullable|string'
```

### 2.2 ScheduleService

- `php artisan make:class Services/ScheduleService`

**ScheduleService:**

```php
__construct(private ValidationService $validationService) {} // już masz

create(array $data): Schedule
{
    return Schedule::create([
        ...$data,
        'status' => 'draft',
        'created_by' => auth()->id(),
    ]);
}
```

### 2.3 Połącz z Kontrolerem

**ScheduleController@store():**

```php
public function __construct(private ScheduleService $scheduleService) {}

public function store(StoreScheduleRequest $request) {
    $schedule = $this->scheduleService->create($request->validated());
    return response()->json(ScheduleResource::make($schedule), 201);
}
```

### 2.4 ScheduleResource

`php artisan make:resource ScheduleResource`

pola

```php
- id, name, description, month, year
- status, published_at, created_by → creator_name
- total_shifts → $this->shifts->count()
- shifts → ShiftResource::collection($this->shifts)
```

- Postman: **GET** `/api/schedules/1 → pokazuje total_shifts: 0`

Fields:

text

- id, name, description, month, year
- status, published_at, created_by → creator_name
- total_shifts → $this->shifts->count()
- shifts → ShiftResource::collection($this->shifts)
  Postman: GET /api/schedules/1 → pokazuje total_shifts: 0

✅ SESJA 2 GOTOWA Gdy:
text
[x] php artisan test → 6 testów (index + store + update)
[x] Postman: StoreScheduleRequest walidacja (brak name → 422)
[x] ScheduleResource działa z eager loading
Commit: :hammer: refactor(services): ScheduleService + FormRequests

🎯 SESJA 3: BatchPreprocessor (1.5h)
📋 Zadania:
3.1 Klasa
text
php artisan make:class Services/BatchPreprocessor
3.2 prepare(array $shiftsData, Schedule $schedule): Collection
text
Input: array z requesta + Schedule (do walidacji miesiąca)

Walidacja inputu:

- każdy shift: required user_id, position_id, date, shift_start, shift_end
- date w miesiącu Schedule: Carbon::parse($date)->month === $schedule->month
- user_id exists:users,id, position_id exists:positions,id

Processing:
collect($shiftsData)
->groupBy('user_id')
->map(fn($userShifts) => $userShifts
->sortBy('date')
->sortBy('shift_start')
->values()
)

Output: Collection[user_id → Collection[shifts]]
3.3 Tinker Test
text
php artisan tinker
$preprocessor = app(App\Services\BatchPreprocessor::class);
$schedule = App\Models\Schedule::find(1);
$shifts = [
    ['user_id'=>1, 'date'=>'2026-01-15', ...],
    ['user_id'=>1, 'date'=>'2026-01-16', ...],
];
$result = $preprocessor->prepare($shifts, $schedule);
$result[1]->count() === 2
✅ SESJA 3 GOTOWA Gdy:
text
[x] Tinker: BatchPreprocessor działa
[x] Walidacja inputu rzuca wyjątki na złe dane
Commit: :package: feat(services): BatchPreprocessor input validation + sorting

🎯 SESJA 4: BatchResult Value Object (30min)
📋 Zadania:
4.1 Klasa
text
php artisan make:class ValueObjects/BatchResult
4.2 Implementacja
text
private function \_\_construct(
private readonly array $shifts = [],
private readonly array $errors = [],
) {}

public static function success(array $shifts): self { return new self($shifts); }
public static function withErrors(array $errors): self { return new self([], $errors); }

public function hasErrors(): bool { return !empty($this->errors); }
public function errors(): array { return $this->errors; }
public function shifts(): array { return $this->shifts; }
public function count(): int { return count($this->shifts); }
4.3 Tinker Test
text
$success = App\ValueObjects\BatchResult::success([1,2]);
$success->hasErrors() === false
$success->count() === 2

$error = App\ValueObjects\BatchResult::withErrors(['row_1' => ['error']]);
$error->hasErrors() === true
✅ SESJA 4 GOTOWA Gdy:
text
[ ] Tinker: BatchResult działa poprawnie
Commit: :recycle: feat(vo): BatchResult immutable Value Object

🎯 SESJA 5: DTO + Validators Batch Support (2h)
📋 Zadania:
5.1 DTO Rozszerzenie
text
app/DataTransferObjects/ShiftValidationData.php
public readonly int $accumulatedBatchHours = 0;
5.2 MaxHoursPerMonthValidator
text
public function validate(ShiftValidationData $shift): void
{
// ... istniejący kod ...

    $minutesInMonth = $this->retrieveWorkedMinutesInRange(...);

    // 🆕 DODAJ:
    $minutesInMonth += $shift->accumulatedBatchHours * 60;

    // ... reszta bez zmian

}
Analogicznie: MaxHoursPerQuarterValidator

5.3 Unit Testy Validatorów
text
php artisan make:test Unit/MaxHoursPerMonthValidatorTest --pest

test_accumulates_batch_hours_correctly()
test_exceeds_limit_with_batch_accumulation()
test_does_not_affect_single_shift_validation()
Przykład testu:

text
$validator = new MaxHoursPerMonthValidator();
$dto = new ShiftValidationData(
userId: 1,
date: '2026-01-15',
// ... inne pola
accumulatedBatchHours: 20, // 20h z poprzednich shiftów w batchu
);

$validator->validate($dto); // NIE rzuca błędu (jeśli suma OK)
✅ SESJA 5 GOTOWA Gdy:
text
[x] php artisan test Unit/\*ValidatorTest.php → PASS
[x] Tinker: DTO z accumulatedBatchHours działa
Commit: :mag: feat(validation): DTO accumulatedBatchHours + validators batch support

🎯 SESJA 6: BatchValidationService Core (3h)
📋 Zadania (najtrudniejsza sesja):
6.1 Klasa
text
php artisan make:class Services/BatchValidationService
6.2 Konstruktor
text
public function \_\_construct(
private readonly ValidationService $validationService
) {}
6.3 validate(Collection $groupedShifts): array
text
$errors = [];
$globalIndex = 0;

foreach ($groupedShifts as $userId => $userShifts) {
$accumulatedHours = 0;
$previousShiftsInBatch = [];

    foreach ($userShifts as $shiftIndex => $shiftData) {
        $clientTempId = $shiftData['client_temp_id'];

        try {
            // 1️⃣ Pre-validation: konflikty W BATCHU
            $this->validateInternalConflicts($shiftData, $previousShiftsInBatch);

            // 2️⃣ DTO z akumulacją
            $dto = ShiftValidationData::from([
                ...$shiftData,
                'accumulatedBatchHours' => $accumulatedHours,
                'ignoreShiftId' => null,
            ]);

            // 3️⃣ Istniejące validatory vs DB
            $this->validationService->validate($dto);

            // 4️⃣ Aktualizuj stan
            $accumulatedHours += TimeHelper::hoursBetween($shiftData['shift_start'], $shiftData['shift_end']);
            $previousShiftsInBatch[] = $shiftData;

        } catch (ValidationException $e) {
            $errors[$clientTempId] = $e->errors();
        }

        $globalIndex++;
    }

}

return $errors;
6.4 validateInternalConflicts(array $current, array $previous)
text
foreach ($previous as $prevShift) {
    // Time overlap w batchu
    if ($this->shiftsOverlap($current, $prevShift)) {
throw ValidationException::withMessages([
'conflict' => ['Time overlap within batch'],
]);
}

    // Min break w batchu
    if ($this->insufficientBreak($current, $prevShift)) {
        throw ValidationException::withMessages([
            'break' => ['Insufficient break within batch'],
        ]);
    }

}
6.5 Helper Methods
text
private function shiftsOverlap(array $a, array $b): bool
private function insufficientBreak(array $current, array $prev): bool
6.6 StoreBatchShiftsRequest
text
php artisan make:request StoreBatchShiftsRequest

rules():
'shifts' => 'required|array|min:1'
'shifts._.client_temp_id' => 'required|string'
'shifts._.user*id' => 'required|exists:users,id'
'shifts.*.position*id' => 'required|exists:positions,id'
'shifts.*.date' => 'required|date'
'shifts._.shift_start' => 'required|date_format:H:i'
'shifts._.shift_end' => 'required|date_format:H:i|after:shifts.\*.shift_start'
✅ SESJA 6 GOTOWA Gdy:
text
[x] Tinker: BatchValidationService::validate() → zwraca błędy z client_temp_id
[ ] php artisan test Unit/BatchValidationTest.php → 6 testów
Commit: :test_tube: feat(services): BatchValidationService + internal conflicts

🎯 SESJA 7: ScheduleService Batch + Endpoint (2h)
📋 Zadania:
7.1 ScheduleService::addShiftsBatch()
text
public function addShiftsBatch(Schedule $schedule, array $shiftsData): BatchResult
{
    $preprocessor = app(BatchPreprocessor::class);
    $prepared = $preprocessor->prepare($shiftsData, $schedule);

    $batchValidator = app(BatchValidationService::class);
    $errors = $batchValidator->validate($prepared);

    if (!empty($errors)) {
        return BatchResult::withErrors($errors);
    }

    return DB::transaction(function () use ($prepared, $schedule) {
        $createdShifts = [];

        foreach ($prepared->flatten(1) as $shiftData) {
            $hoursWorked = TimeHelper::calculateMinutesDifference(
                $shiftData['date'] . ' ' . $shiftData['shift_start'],
                $shiftData['date'] . ' ' . $shiftData['shift_end']
            );

            $shift = Shift::create([
                ...$shiftData,
                'schedule_id' => $schedule->id,
                'hours_worked' => $hoursWorked,
                'status' => $shiftData['status'] ?? 'scheduled',
            ]);

            $createdShifts[] = $shift;
        }

        return BatchResult::success($createdShifts);
    });

}
7.2 ScheduleController::addShiftsBatch()
text
public function addShiftsBatch(Schedule $schedule, StoreBatchShiftsRequest $request) {
    $result = $this->scheduleService->addShiftsBatch($schedule, $request->shifts);

    if ($result->hasErrors()) {
        return response()->json([
            'message' => 'Batch validation failed',
            'errors' => $result->errors(),
        ], 422);
    }

    return response()->json([
        'message' => 'Batch created successfully',
        'count' => $result->count(),
        'shifts' => ShiftResource::collection($result->shifts()),
    ], 201);

}
7.3 Route
text
routes/api.php:
Route::post('/schedules/{schedule}/shifts/batch', [ScheduleController::class, 'addShiftsBatch'])
->middleware(['auth:api', 'role:manager,admin']);
7.4 Postman Collection
text

1. POST /api/schedules → zapisz schedule_id
2. POST /api/schedules/{id}/shifts/batch → sukces 201
3. GET /api/schedules/{id} → total_shifts > 0
4. POST z błędami → 422 z errors[client_temp_id]
   ✅ SESJA 7 GOTOWA Gdy:
   text
   [ ] Postman: full flow create → batch success → show z shiftami
   [ ] Postman: batch z błędami → mapped errors
   Commit: :sparkles: feat(api): Schedule batch shifts endpoint + ScheduleService

🎯 SESJA 8: Publish + ShiftController Cleanup (1.5h)
📋 Zadania:
8.1 ScheduleController::publish()
text
public function publish(Schedule $schedule) {
    // Opcjonalnie: ponowna walidacja shiftów
    // $this->scheduleService->validateShifts($schedule);

    $schedule->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    return response()->json(ScheduleResource::make($schedule));

}
8.2 Route
text
POST /api/schedules/{schedule}/publish
8.3 ShiftController Cleanup
text

- Zablokuj store(): abort(410, 'Use ScheduleController instead');
- Lub usuń metodę całkowicie
  8.4 Postman Full Flow
  text

1. POST /api/schedules → draft
2. POST /api/schedules/{id}/shifts/batch → shifty
3. POST /api/schedules/{id}/publish → published
4. GET /api/shifts (jako employee) → widzi shifty
   ✅ SESJA 8 GOTOWA Gdy:
   text
   [ ] Postman: full flow draft → batch → publish → employee widzi
   Commit: :rocket: feat(api): Schedule publish endpoint + cleanup

🎯 SESJA 9: Kompletne Testy Pest (3h)
📋 Testy Feature:
text
tests/Feature/ScheduleTest.php
test_manager_can_crud_schedules()
test_employee_cannot_crud_schedules()
test_schedule_cascade_deletes_shifts()
test_add_batch_shifts_success()
test_add_batch_returns_mapped_errors()
test_batch_internal_time_conflict()
test_batch_accumulated_hours_exceed()
test_publish_changes_status()
test_employee_sees_only_published_shifts()
📋 Testy Unit:
text
tests/Unit/BatchPreprocessorTest.php
test_groups_and_sorts_shifts()
test_validates_month_match()
test_validates_required_fields()

tests/Unit/BatchValidationServiceTest.php
test_internal_time_overlap()
test_internal_min_break_violation()
test_accumulates_hours_correctly()
test_reuses_validation_service()
test_maps_errors_by_client_temp_id()

tests/Unit/BatchResultTest.php
test_success_state()
test_error_state()
✅ SESJA 9 GOTOWA Gdy:
text
[ ] php artisan test → 100% PASS
[ ] php artisan test --coverage → 90%+ coverage nowych klas
Commit: :white_check_mark: test: Complete Schedule + Batch test suite
