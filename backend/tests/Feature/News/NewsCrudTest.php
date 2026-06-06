<?php

declare(strict_types=1);

use App\Models\NewsPost;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin();
});

test('admin can list news posts', function () {
    /** @var \Tests\TestCase $this */
    NewsPost::factory()->count(3)->byUser($this->admin)->create();

    $this->getJson('/api/news')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'is_important', 'author', 'created_at'],
            ],
        ]);
});

test('admin can search news posts by title', function () {
    /** @var \Tests\TestCase $this */
    NewsPost::factory()->byUser($this->admin)->create(['title' => 'Zmiana grafiku lipiec']);
    NewsPost::factory()->byUser($this->admin)->create(['title' => 'Szkolenie BHP']);

    $this->getJson('/api/news?search=grafik')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Zmiana grafiku lipiec']);
});

test('admin can search news posts by content', function () {
    /** @var \Tests\TestCase $this */
    NewsPost::factory()->byUser($this->admin)->create(['content' => 'Treść z frazą harmonogram dyżurów.']);
    NewsPost::factory()->byUser($this->admin)->create(['content' => 'Inny tekst bez szukanej frazy tutaj.']);

    $this->getJson('/api/news?search=harmonogram')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('admin can view a single news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->byUser($this->admin)->create();

    $this->getJson("/api/news/{$newsPost->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $newsPost->id]);
});

test('admin can create a news post', function () {
    /** @var \Tests\TestCase $this */
    $payload = [
        'title' => 'Nowy wpis testowy',
        'content' => 'Treść wpisu testowego z wystarczającą ilością znaków.',
        'is_important' => true,
    ];

    $this->postJson('/api/news', $payload)
        ->assertCreated()
        ->assertJsonFragment([
            'title' => 'Nowy wpis testowy',
            'is_important' => true,
        ]);

    $this->assertDatabaseHas('news_posts', [
        'title' => 'Nowy wpis testowy',
        'author_id' => $this->admin->id,
        'is_important' => true,
    ]);
});

test('new news post defaults is_important to false', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/news', [
        'title' => 'Zwykły wpis',
        'content' => 'Treść zwykłego wpisu bez flagi ważności dla tego testu.',
    ])
        ->assertCreated()
        ->assertJsonFragment(['is_important' => false]);
});

test('author is set to the authenticated user on create', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/news', [
        'title' => 'Wpis z autorem',
        'content' => 'Treść z autorem ustawionym automatycznie przez backend.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.author.id', $this->admin->id);

    $this->assertDatabaseHas('news_posts', [
        'title' => 'Wpis z autorem',
        'author_id' => $this->admin->id,
    ]);
});

test('admin can update a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->byUser($this->admin)->create();

    $this->patchJson("/api/news/{$newsPost->id}", [
        'title' => 'Zaktualizowany tytuł wpisu',
    ])
        ->assertOk()
        ->assertJsonFragment(['title' => 'Zaktualizowany tytuł wpisu']);

    $this->assertDatabaseHas('news_posts', [
        'id' => $newsPost->id,
        'title' => 'Zaktualizowany tytuł wpisu',
    ]);
});

test('admin can delete a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->byUser($this->admin)->create();

    $this->deleteJson("/api/news/{$newsPost->id}")
        ->assertOk()
        ->assertJson(['message' => 'News post deleted successfully']);

    $this->assertDatabaseMissing('news_posts', ['id' => $newsPost->id]);
});

test('news posts are returned newest first', function () {
    /** @var \Tests\TestCase $this */
    $older = NewsPost::factory()->byUser($this->admin)->create(['created_at' => now()->subDay()]);
    $newer = NewsPost::factory()->byUser($this->admin)->create(['created_at' => now()]);

    $response = $this->getJson('/api/news')->assertOk();

    expect($response->json('data.0.id'))->toBe($newer->id);
    expect($response->json('data.1.id'))->toBe($older->id);
});

test('title is required when creating a news post', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/news', [
        'content' => 'Treść bez tytułu — walidacja powinna zwrócić błąd.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

test('content must be at least 10 characters', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/news', [
        'title' => 'Tytuł wpisu',
        'content' => 'Za krótko',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});
