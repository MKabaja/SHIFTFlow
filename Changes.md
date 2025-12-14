Decyzja Architektoniczna: Zmiana relacji Users-Positions
Data: 2025-12-10
Status: Zaimplementowano ✅

1. Problem (Kontekst)
   Początkowo relacja między użytkownikiem a stanowiskami była realizowana poprzez kolumnę JSON positions w tabeli users (przechowującą tablicę ID, np. [1, 2]).

Wady rozwiązania JSON:

Brak spójności danych (Referential Integrity): Baza nie weryfikowała, czy ID wpisane w JSON faktycznie istnieją w tabeli positions.

Trudne zapytania: Wyszukanie wszystkich pracowników przypisanych do stanowiska "B1" wymagało wolnych operacji JSON_CONTAINS.

N+1 Problem: Pobieranie nazw stanowisk dla listy pracowników wymagało ręcznego mapowania ID na obiekty.

2. Rozwiązanie (Pivot Table)
   Zdecydowano się na refaktoryzację do klasycznej relacji Many-to-Many zgodnej ze standardami Laravela.

Zmiany w Bazie Danych:

Usunięto kolumnę json('positions') z tabeli users.

Utworzono tabelę pośrednią position_user z kluczami obcymi (user_id, position_id) i kaskadowym usuwaniem (onDelete('cascade')).

Zmiany w Kodzie:

Modele: Zastąpiono $casts metodami relacji belongsToMany() w User i Position.

Logika: W ValidationService zmieniono sprawdzanie uprawnień z in_array na tablicy na pluck('id') z Kolekcji Eloquent.

Testy: Zastąpiono bezpośrednie wstrzykiwanie tablicy ID (create(['positions' => ...])) metodą $user->positions()->attach($id).

3. Korzyści
   Bezpieczeństwo danych: Baza sama pilnuje poprawności relacji (Foreign Keys).

Wydajność: Szybkie joiny i eager loading (User::with('positions')).

Skalowalność: Łatwiejsze rozbudowywanie systemu raportowania i filtrowania.
