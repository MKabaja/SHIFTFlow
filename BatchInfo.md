# PLAN IMPLEMENTACJI: BATCH SHIFTS (Dodawanie zbiorcze)

## Cel Umożliwić dodanie całego miesiąca (lub wielu zmian) w jednym strzale API, zachowując spójność danych i limity.

## ETAP 1: Fundamenty Walidacji (Core & Scopes)

**Cel:** Dostosowanie modelu `Shift` oraz wszystkich validatorów do obsługi wykluczania wielu ID naraz (niezbędne, aby edytowane zmiany nie konfliktowały same ze sobą w bazie).

### Zadania:

1.  [] **Model `Shift`**: Zaktualizować `scopeExcluding`, aby przyjmował `int|array|null`.
2.  [] **DTO `ShiftValidationData`**: Dodać pole `public array $ignoreShiftIds = []`.
3.  [] **Validatory**: Zaktualizować wywołania `excluding()` w:
    - `TimeConflictValidator`
    - `MinimumBreakValidator`
    - `PositionUniquenessValidator`
    - `BaseHourValidator` (dla limitów godzin)
    - _Uwaga:_ `BaseHourValidator` (godziny) oraz `MinimumBreakValidator` (przerwy) **NIE** wymagają tej zmiany w modelu sekwencyjnym, ponieważ muszą "widzieć" nowe wersje zmian dodane w poprzednich krokach pętli transakcyjnej.

### Commit Message:

`feat(core): update Shift scope and validators to support batch exclusion`
