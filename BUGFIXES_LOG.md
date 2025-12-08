# 🐛 BUGFIX LOG - Backend MVP

> Dokumentacja krytycznych błędów napotkanych podczas developmentu.
> Cel: Nauka i szybsze rozwiązywanie podobnych problemów w przyszłości.

---

## 🚨 Bug #1: InvalidFormatException during Schedule Update

**Data:** 2025-12-08
**Komponent:** `ScheduleController` / `ValidationService`
**Typ:** `Type Error / Carbon Parsing`

### 1. Opis Problemu

Podczas implementacji metody `update` dla grafików (`ScheduleController`), testy automatyczne zwracały błąd 500.
**Komunikat błędu:** `InvalidFormatException: Trailing data` w `ValidationService::getFullDateTime`.
**Scenariusz:** Próba aktualizacji godzin pracy (PATCH) bez wysyłania daty (tylko `shift_start`/`end`).

### 2. Objawy

-   Test `test_manager_can_update_schedule` failował.
-   `ValidationService` rzucał wyjątek przy próbie parsowania daty.
-   `dump()` w serwisie pokazywał dziwne wartości wejściowe:
    -   Zamiast daty `'2025-10-08'`, wpadało `'2025-10-08 00:00:00'`.
    -   Zamiast czasu `'08:00'`, wpadało `'2025-12-07 18:00:00'` (przy `shift_end`).

### 3. Diagnoza (Dlaczego to wybuchło?)

Problem wynikał ze splotu trzech mechanizmów Laravela:

1.  **Eloquent Casts:** Model `Schedule` rzutuje pola `date`, `shift_start`, `shift_end` na obiekty `Carbon` (`datetime:H:i`).
2.  **Controller Fallbacks:** W metodzie `update` używaliśmy fallbacku `$data['date'] ?? $schedule->date`.
3.  **Implicit String Casting:**
    -   Gdy `$schedule->date` (obiekt Carbon) trafiał do tablicy lub był sklejany ze stringiem, PHP zamieniał go na domyślny format: `Y-m-d H:i:s`.
    -   To generowało string z "ogonem" (trailing data), np. `00:00:00`.
    -   Metoda `Carbon::createFromFormat('Y-m-d H:i', ...)` jest ścisła i nie akceptuje sekund, jeśli format ich nie przewiduje.

**Flow błędu:**
`DB (date) -> Model (Carbon Object) -> Controller ($schedule->date) -> String Cast ("... 00:00:00") -> Service -> CreateFromFormat (CRASH)`

### 4. Rozwiązanie (Fix)

Zastosowaliśmy strategię **Defensive Programming** w serwisie oraz **Explicit Formatting** w kontrolerze.

#### A. W `ValidationService.php` (Główny fix)

Uodporniliśmy metodę helpera na różne typy danych (Carbon vs String) oraz na "brudne" stringi z sekundami.

**KOD PRZED:**
public function getFullDateTime(string $date, string $time): Carbon
{
return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
}

**KOD PO (Final Version):**
public function getFullDateTime($date, $time): \Carbon\Carbon
{

if ($date instanceof \Carbon\Carbon) {
$date = $date->format('Y-m-d');
}

if (is_string($date) && strlen($date) > 10) {
$date = substr($date, 0, 10);
}
if ($time instanceof \Carbon\Carbon) {
$time = $time->format('H:i');
}
if (is_string($time) && strlen($time) > 5) {
    $time = substr($time, 0, 5);
}
return \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
}

#### B. W `ScheduleController.php` (Prewencja)

Wyczyściliśmy dane zanim trafią do serwisu.

public function update(UpdateScheduleRequest $request, Schedule $schedule)
{
$data = $request->validated();
// Explicit Format: Zamień Carbon na string zanim trafi do fallbacka
$oldDate = $schedule->date;
if ($oldDate instanceof \Carbon\Carbon) {
$oldDate = $oldDate->format('Y-m-d');
}

$dataWithFallbacks = [
'user_id' => $data['user_id'] ?? $schedule->user_id,
// Używamy sformatowanej daty (string), a nie obiektu Carbon
'date' => $data['date'] ?? $oldDate,
// ...
'shift_start' => $data['shift_start'] ?? $schedule->shift_start, // To obsłuży serwis (pkt A)
];

// ... reszta kodu
}

### 5. Lekcja na przyszłość 🎓

1.  **Nie ufaj `$casts` przy stringowaniu:** Obiekty Carbon z modelu po zamianie na string (np. w logach, tablicach, konkatenacji) dają format pełny (`Y-m-d H:i:s`), a nie taki, jak zdefiniowałeś w cast (`date` czy `H:i`).
2.  **Polimorfizm w Serwisach:** Metody narzędziowe (helpers) powinny być odporne na typy (`string|Carbon`), bo nigdy nie wiesz, czy dostaniesz surowy input z Requesta (string), czy obiekt z Bazy (Carbon).
3.  **createFromFormat jest strict:** Trailing data (nawet spacje czy sekundy) zawsze wyrzucą błąd. Używaj `substr` lub formatowania, by wyczyścić input.

---
