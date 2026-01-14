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

Chiałem umówic jeszcze pewną kestię, potencjalnych zmian w systemie, otóż po rozmowie z żoną odnosnie Limitów uzgodnilismy że:

1 - MUsimy dodac LIMIT GODZIN KWartalny- kopalnia ma system pracy oparty osystem kwartalny, wiec to co ona aktualnie pilnuje to limity kwartalne.

To ja proponuje tak:

-Każdemu Pracownikowi mozesz przypisac Jakąś liczbę godzin powiedzmy że np 168 /msc, tutej to co juz mamy w user:

$table->unsignedSmallInteger('max_hours_per_month')
                ->nullable();

-Gdy na GRAFIKU Wstawisz kogos tak ze tą liczbe przekroczy, grafik tego nie zablokuje ale pokążę Ci ostrzeżenie-czyli to co sobie ustawisz to info dla Ciebie aby trzymac sie jakiejś sredniej.

-   Blokująca będzie liczba kwartalna np ustawiasz limit kwartalny na 550H

tutaj musimy dodac limit KWARTALNY, tez nullable jesli ktos nie ma przypisane limitu system go pomija(bo to nie dotyczy kazdego tylko tych co mają umowę o prace)

kwartał = 3MSC czyli 3 GRAFIKI czyli jesli ktos przekroczy Limit kwartalny to ostaniego grafiku ww kwartale nie dasz rade opublikowac, pokaze sie naczerowno kto przekroczył i o ile,

2 - ten limit 11h - minimum break:
obecnie mamy $table->unsignedSmallInteger('min_break_hours')
->default(11);

i tutaj :
Wszyscy pracownicy mają pole przerwa miedzy zmianami,Domysnie puste, jesli sobie komus dodasz 11h,8h, ile sobie wybierzesz -Wtedy system bedzie tego pilnował, jesli pole zostanie puste- wtedy system pomiją tą regułe dla danej osoby.

bo wszystkie limity GODZINOWE Dotyczą osób które mają UMOWE o PRACę,

ZLECENIE, lub inne nie mają limitów

moj user table ma aktualnie :
$table->enum('contract_type', ['employment_contract', 'mandate_contract'])
->default('employment_contract');
