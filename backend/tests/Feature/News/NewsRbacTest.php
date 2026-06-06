<?php

declare(strict_types=1);

use App\Models\NewsPost;

test('employee can list news posts', function () {
    /** @var \Tests\TestCase $this */
    NewsPost::factory()->count(2)->create();

    $this->actingAsEmployee()
        ->getJson('/api/news')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'content', 'is_important', 'created_at', 'updated_at']]]);
});

test('employee can view a single news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->create();

    $this->actingAsEmployee()
        ->getJson("/api/news/{$newsPost->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $newsPost->id)
        ->assertJsonStructure(['data' => ['id', 'title', 'content', 'is_important', 'created_at', 'updated_at']]);
});

test('manager can list news posts', function () {
    /** @var \Tests\TestCase $this */
    NewsPost::factory()->count(2)->create();

    $this->actingAsManager()
        ->getJson('/api/news')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'content', 'is_important', 'created_at', 'updated_at']]]);
});

test('employee cannot create a news post', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsEmployee()
        ->postJson('/api/news', [
            'title' => 'Wpis pracownika',
            'content' => 'Pracownik nie powinien móc tworzyć wpisów w systemie.',
        ])
        ->assertForbidden();
});

test('manager cannot create a news post', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsManager()
        ->postJson('/api/news', [
            'title' => 'Wpis managera',
            'content' => 'Manager nie powinien móc tworzyć wpisów w systemie.',
        ])
        ->assertForbidden();
});

test('employee cannot update a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->create();

    $this->actingAsEmployee()
        ->patchJson("/api/news/{$newsPost->id}", ['title' => 'Zmiana przez pracownika'])
        ->assertForbidden();
});

test('manager cannot update a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->create();

    $this->actingAsManager()
        ->patchJson("/api/news/{$newsPost->id}", ['title' => 'Zmiana przez managera'])
        ->assertForbidden();
});

test('employee cannot delete a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->create();

    $this->actingAsEmployee()
        ->deleteJson("/api/news/{$newsPost->id}")
        ->assertForbidden();
});

test('manager cannot delete a news post', function () {
    /** @var \Tests\TestCase $this */
    $newsPost = NewsPost::factory()->create();

    $this->actingAsManager()
        ->deleteJson("/api/news/{$newsPost->id}")
        ->assertForbidden();
});

test('unauthenticated user cannot access news posts', function () {
    /** @var \Tests\TestCase $this */
    $this->getJson('/api/news')
        ->assertUnauthorized();
});
