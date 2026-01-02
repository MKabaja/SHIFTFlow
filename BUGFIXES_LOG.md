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

### Baza testowa vs produkcyjna (SQLite vs MySQL)

**Problem:**

-   Testy feature nie działały, bo Laravel próbował korzystać z SQLite (:memory:), mimo że projekt był skonfigurowany pod MySQL.

-   Pojawiał się błąd w stylu: brak pliku bazy shiftflow_db / nieistniejąca baza testowa.

**Jak zostało rozwiązane:**

-   W pliku phpunit.xml jawnie ustawiono zmienne środowiskowe dla testów:

```
DB_CONNECTION=mysql

DB_HOST=db

DB_DATABASE=shiftflow_test

DB_USERNAME, DB_PASSWORD
```

pod testową bazę.

Wyczyszczono cache konfiguracji i usunięto stary config cache.

Dzięki temu testy korzystają z osobnej bazy testowej MySQL, zgodnie z konfiguracją.

2. N+1 zapytań przy imporcie (wydajność)
   Problem:

Naturalnym odruchem było sprawdzanie loginów / użytkowników w pętli (dla każdego rekordu CSV):

To prowadziłoby do wielu zapytań SQL przy większym imporcie (N+1 problem).

Jak zostało rozwiązane:

Przed pętlą pobierane są wszystkie potrzebne dane jednym strzałem:

User::pluck('login')->toArray() – wszystkie istniejące loginy.

User::whereIn('name', $employeeDataCollection->pluck('name'))->pluck('login', 'name') – mapa istniejących użytkowników po name.

W pętli:

Operacje wykonywane są wyłącznie na kolekcjach i tablicach w pamięci.

Nowo wygenerowany login jest dopisywany do tablicy, aby uniknąć kolizji w tym samym imporcie.

Efekt: 2 zapytania przed pętlą + updateOrCreate dla każdego rekordu, brak dodatkowych zapytań w pętli.

3. Zmiana loginu przy ponownym imporcie (regresja danych)
   Problem:

Przy drugim imporcie tego samego pracownika:

Login potrafił się zmienić z np. jkowal na jkowal1.

Wynikało to z tego, że przy każdym imporcie generowano login na nowo, zamiast zachować istniejący.

Jak zostało rozwiązane:

Przed pętlą pobierana jest mapa istniejących użytkowników:

['jan kowalski' => 'jkowal', ...].

W prepareEmployeePersistenceData():

Jeśli existingUsersByName zawiera danego pracownika po name, używany jest jego dotychczasowy login.

Jeśli pracownik nie istnieje – wywoływany jest mechanizm generowania loginu.

Dzięki temu:

Istniejący pracownik po ponownym imporcie zachowuje swój login.

Statystyki importu prawidłowo rozróżniają created vs updated.

4. Kolizje loginów (różne osoby, ten sam prefix)
   Problem:

Dwie różne osoby mogły wygenerować ten sam bazowy login, np.:

Jan Kowalski → jkowal

Jan Kowalczyk → też jkowal.

Bez odpowiedniego mechanizmu powstawałyby duplikaty na kolumnie login (unikalny indeks).

Jak zostało rozwiązane:

Wprowadzono LoginGeneratorService z logiką:

Wyznacz bazowy login na podstawie imienia i nazwiska.

Filtrowanie loginów, które zaczynają się od bazowego (jkowal, jkowal1, jkowal2, …).

Wyciąganie sufiksów numerycznych z końca loginu (regex).

Zwracanie loginu w postaci baseLogin lub baseLogin.(max+1):

Przykład: istnieje jkowal, jkowal1, jkowal5 → nowy login to jkowal6.

Dzięki temu:

Każdy nowy użytkownik otrzymuje unikalny login, nawet jeśli bazowy prefix się powtarza.

5. Polskie znaki w loginach (problemy z unikalnością)
   Problem:

Imiona/nazwiska z polskimi znakami powodowały loginy typu śzuzan.

To:

Było mało czytelne.

Potencjalnie mogło powodować problemy z unikalnością i porównywaniem stringów.

Jak zostało rozwiązane:

W LoginGeneratorService dodano transliterację:

Str::ascii(mb_strtolower($login)).

Przykłady:

Ślusarek Zuzanna → szuzan.

Łukasz → lukasz.

Dzięki temu:

Loginy są ASCII-only, czytelne, stabilne w bazie i przy porównaniach.

6. Błąd przy ponownym imporcie – unikalny indeks na loginie
   Problem:

Przy imporcie z polskimi znakami i bez transliteracji:

Próba utworzenia użytkownika z loginem, który już istniał (np. śzuzan) kończyła się błędem SQL Integrity constraint violation: 1062 Duplicate entry.

Jak zostało rozwiązane:

Po dodaniu transliteracji i mechanizmu max+1:

Pierwszy użytkownik dostaje np. szuzan.

Kolejny o tym samym bazowym loginie – szuzan1, itd.

Dodatkowo:

Dzięki sprawdzaniu po name (mapa existingUsersByName) istniejący użytkownik nie próbuje dostać nowego loginu, więc nie generuje konfliktu unikalności przy ponownym imporcie.

7. Relacje user–positions (poprawne przypisanie stanowisk)
   Problem:

Na początku test nie sprawdzał, czy pracownicy faktycznie mają przypisane odpowiednie stanowiska (PD, PW).

Istniało ryzyko, że import tworzy userów, ale relacje w tabeli pivot nie są poprawne.

Jak zostało rozwiązane:

Rozbudowano testy feature:

Dla Jan Kowalski sprawdzane jest, że ma PD, a nie ma PW.

Dla Anna Nowak – odwrotnie.

W repozytorium:

saveEmployee() po updateOrCreate wywołuje $user->positions()->sync($positionIDs);, co gwarantuje spójność relacji.

```

```
