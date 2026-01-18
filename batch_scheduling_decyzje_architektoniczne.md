# Batch scheduling w Schedule APP (Laravel)

## Cel

Umożliwić tworzenie **całego grafiku (miesiąca)** w jednym widoku (excel-like) w sposób:

- spójny (all-or-nothing),
- bez fałszywych konfliktów,
- zgodny z Clean Code i SRP,
- bez przepisywania istniejących validatorów.

---

## Kluczowe założenie

> **Batch NIE dotyczy wszystkich validatorów.**
>
> Dotyczy **wyłącznie tych validatorów, które porównują shifta z innymi shiftami w bazie danych** i mogą wykryć fałszywy konflikt wewnątrz jednego batcha.

---

## Obecna architektura (stan wyjściowy)

- `ShiftController` – obsługa pojedynczego shifta
- `ValidationService` – orkiestrator validatorów
- Każdy validator:
    - ma jedną odpowiedzialność
    - jest przetestowany unitowo
- Dane wejściowe są przekazywane przez `ShiftValidationData` (DTO)

```php
class ShiftValidationData
{
    public function __construct(
        public int $userId,
        public string $date,
        public string $shiftStart,
        public string $shiftEnd,
        public int $positionId,
        public array $allowedPositionIds,
        public ?int $maxHoursPerMonth,
        public ?int $minBreakHours,
        public ?int $maxHoursPerQuarter,
        public ?int $ignoreShiftId = null,
    ) {}
}
```

---

## Podział validatorów względem batcha

### 🟥 VALIDATORY, które MUSZĄ obsłużyć batch

Powód: porównują aktualny shift z innymi shiftami w DB.

#### `TimeConflictValidator`

- sprawdza overlapping czasowy
- **musi ignorować shifty z tego samego batcha**

#### `MinimumBreakValidator`

- sprawdza odstęp między sąsiednimi shiftami
- **musi ignorować shifty z batcha**

#### (warunkowo) `AvailabilityValidator`

- jeśli availability liczone jest na podstawie shiftów → **TAK**
- jeśli na osobnej tabeli availability → **NIE**

➡️ **Rozwiązanie**: rozszerzyć DTO o `ignoreShiftIds: array` i użyć scope typu:

```php
->excludingMany($shift->ignoreShiftIds)
```

---

### 🟨 VALIDATORY, które NIE DOTYCZĄ batcha

Powód: nie porównują z innymi shiftami.

- `PositionPermissionValidator`
- `PositionUniquenessValidator` (jeśli dotyczy tylko pojedynczego shifta)

➡️ **Bez zmian**

---

### 🟦 VALIDATORY LIMITÓW GODZIN (ważne rozróżnienie)

#### `MaxHoursPerMonthValidator`

#### `MaxHoursPerQuarterValidator`

To **NIE są konflikty**, tylko **agregacje**.

❌ `ignoreShiftIds` tu NIE rozwiązuje problemu.

✅ Poprawne podejście:

- policzyć godziny z DB (bez batcha)
- **dodać godziny z batcha**
- porównać z limitem

Batch wpływa tu na **sumę**, a nie na konflikty.

---

## Dlaczego zbieramy shifty do tablicy (batch)

1. Front tworzy cały miesiąc naraz (UX)
2. Backend waliduje **spójność całego grafiku**
3. Możliwe:
    - wykrycie konfliktów wewnętrznych (dzień 10 vs dzień 12)
    - poprawne liczenie limitów godzin
4. Całość zapisywana w **jednej transakcji**:

```php
DB::transaction(function () {
    foreach ($shifts as $shift) {
        // validate + save
    }
});
```

➡️ albo **wszystko**, albo **nic**.

---

## ScheduleController – plan implementacji

### Rola

- przyjmuje dane całego grafiku
- NIE zawiera logiki biznesowej

### Co robi:

1. walidacja requesta (shape danych)
2. przekazanie danych do serwisu aplikacyjnego

---

## ScheduleService (nowy serwis)

### Odpowiedzialności:

- iterowanie po shiftach
- budowa DTO (z `ignoreShiftIds`)
- wywołanie `ValidationService`
- zapis w transakcji

➡️ Controller pozostaje **skinny**

---

---

## Testy

- Unit testy validatorów – już są
- Dodać:
    - test batch bez konfliktów
    - test batch z konfliktem wewnętrznym
    - test przekroczenia limitu godzin w batchu

---

## TL;DR

- Batch dotyczy **tylko validatorów konfliktowych**
- Limity godzin → agregacja, nie ignorowanie
- DTO rozszerzamy **minimalnie i celowo**
- ScheduleController = cienki
- Cała logika w ScheduleService
