<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsPostSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        NewsPost::create([
            'title' => 'Zmiana harmonogramu dyżurów — lipiec 2026',
            'content' => 'Informujemy, że harmonogram dyżurów na lipiec 2026 został opublikowany. Prosimy o zapoznanie się z grafikiem i zgłoszenie ewentualnych uwag do swojego przełożonego do 10 czerwca.',
            'is_important' => true,
            'author_id' => $admin?->id,
        ]);

        NewsPost::create([
            'title' => 'Szkolenie BHP — 15 czerwca',
            'content' => 'Przypominamy o obowiązkowym szkoleniu BHP zaplanowanym na 15 czerwca 2026. Szkolenie odbędzie się w sali konferencyjnej nr 3 o godzinie 9:00. Obecność obowiązkowa dla wszystkich pracowników zatrudnionych po 1 stycznia 2026.',
            'is_important' => false,
            'author_id' => $admin?->id,
        ]);

        NewsPost::create([
            'title' => 'Nowy system zgłaszania dyspozycyjności',
            'content' => 'Z przyjemnością informujemy, że od 1 lipca 2026 roku wszystkie dyspozycyjności należy zgłaszać przez nową aplikację SHIFTFlow. Dotychczasowe formularze papierowe zostają wycofane. W razie pytań prosimy o kontakt z działem HR.',
            'is_important' => false,
            'author_id' => $admin?->id,
        ]);

        NewsPost::create([
            'title' => 'Wakacyjne zmiany w obsadzie',
            'content' => 'W związku z sezonem urlopowym informujemy o tymczasowych zmianach w obsadzie stanowisk. Szczegółowy grafik zastępstw dostępny jest w dziale kadr. Prosimy o zgłaszanie planowanych urlopów z co najmniej 2-tygodniowym wyprzedzeniem.',
            'is_important' => false,
            'author_id' => $admin?->id,
        ]);

        NewsPost::create([
            'title' => 'Aktualizacja przepisów przeciwpożarowych',
            'content' => 'Informujemy, że zgodnie z nowym zarządzeniem Kierownika Kopalni, wszystkie punkty ewakuacyjne zostały zaktualizowane. Mapy ewakuacyjne są dostępne przy każdym zejściu pod ziemię. Prosimy o zapoznanie się z nowymi trasami ewakuacyjnymi.',
            'is_important' => false,
            'author_id' => $admin?->id,
        ]);
    }
}
