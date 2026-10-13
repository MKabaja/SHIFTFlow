<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->travelTo(Carbon::parse('2026-10-15'));
    $this->actingAsEmployee();
});

test('employee sees only their own availabilities from the requested month', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    /** @var Availability $ownInMonth */
    $ownInMonth = Availability::factory()->forUser($this->employee->id)->forDate('2026-10-10')->create();
    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-10')->create();
    Availability::factory()->forUser($this->employee->id)->forDate('2026-11-10')->create();

    $this->getJson('/api/availabilities?year=2026&month=10')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownInMonth->id);
});

test('index includes the first and last day of the month and excludes neighbouring months', function () {
    /** @var \Tests\TestCase $this */
    foreach (['2026-01-31', '2026-02-01', '2026-02-28', '2026-03-01'] as $date) {
        Availability::factory()->forUser($this->employee->id)->forDate($date)->create();
    }

    $dates = $this->getJson('/api/availabilities?year=2026&month=2')
        ->assertOk()
        ->json('data.*.date');

    expect($dates)->toBe(['2026-02-01', '2026-02-28']);
});

test('index returns every availability of the month without pagination', function () {
    /** @var \Tests\TestCase $this */
    Availability::factory()
        ->forUser($this->employee->id)
        ->count(25)
        ->sequence(fn ($sequence) => ['date' => sprintf('2026-10-%02d', $sequence->index + 1)])
        ->create();

    $this->getJson('/api/availabilities?year=2026&month=10')
        ->assertOk()
        ->assertJsonCount(25, 'data')
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('links');
});

test('index sorts availabilities by date across users', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    Availability::factory()->forUser($this->employee->id)->forDate('2026-10-20')->create();
    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-12')->create();
    Availability::factory()->forUser($this->employee->id)->forDate('2026-10-05')->create();

    $dates = $this->actingAsAdmin()
        ->getJson('/api/availabilities?year=2026&month=10')
        ->assertOk()
        ->json('data.*.date');

    expect($dates)->toBe(['2026-10-05', '2026-10-12', '2026-10-20']);
});

test('index sorts availabilities of the same date by user', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-10')->create();
    Availability::factory()->forUser($this->employee->id)->forDate('2026-10-10')->create();

    $userIds = $this->actingAsAdmin()
        ->getJson('/api/availabilities?year=2026&month=10')
        ->assertOk()
        ->json('data.*.user_id');

    expect($userIds)->toBe([$this->employee->id, $otherUser->id]);
});

test('index rejects invalid year and month', function (string $query, array $invalidFields) {
    /** @var \Tests\TestCase $this */
    $this->getJson("/api/availabilities{$query}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidFields);
})->with([
    'missing year and month' => ['', ['year', 'month']],
    'missing month' => ['?year=2026', ['month']],
    'month above 12' => ['?year=2026&month=13', ['month']],
    'month below 1' => ['?year=2026&month=0', ['month']],
    'year two years back' => ['?year=2024&month=10', ['year']],
]);

test('employee can open the previous year in January', function () {
    /** @var \Tests\TestCase $this */
    $this->travelTo(Carbon::parse('2027-01-10'));

    /** @var Availability $december */
    $december = Availability::factory()->forUser($this->employee->id)->forDate('2026-12-15')->create();

    $this->getJson('/api/availabilities?year=2026&month=12')
        ->assertOk()
        ->assertJsonPath('data.0.id', $december->id);
});

test('admin and manager see availabilities of all users', function (string $role) {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    Availability::factory()->forUser($this->employee->id)->forDate('2026-10-10')->create();
    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-11')->unavailable()->create();

    $role === 'admin' ? $this->actingAsAdmin() : $this->actingAsManager();

    $userIds = $this->getJson('/api/availabilities?year=2026&month=10')
        ->assertOk()
        ->json('data.*.user_id');

    expect($userIds)->toBe([$this->employee->id, $otherUser->id]);
})->with(['admin', 'manager']);

test('admin can filter availabilities by user', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    Availability::factory()->forUser($this->employee->id)->forDate('2026-10-10')->create();
    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-11')->create();

    $userIds = $this->actingAsAdmin()
        ->getJson("/api/availabilities?year=2026&month=10&user_id={$otherUser->id}")
        ->assertOk()
        ->json('data.*.user_id');

    expect($userIds)->toBe([$otherUser->id]);
});

test('employee cannot view other users availability', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    Availability::factory()->forUser($otherUser->id)->forDate('2026-10-10')->create();

    $this->getJson("/api/availabilities?year=2026&month=10&user_id={$otherUser->id}")
        ->assertForbidden()
        ->assertJsonMissingPath('data');
});

test('employee can declare their own availability', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/availabilities', [
        'date' => '2026-10-20',
        'is_available' => true,
        'notes' => 'Chętnie przyjdę',
    ])
        ->assertCreated()
        ->assertJsonPath('data.date', '2026-10-20')
        ->assertJsonPath('data.is_available', true);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-10-20',
        'is_available' => true,
        'notes' => 'Chętnie przyjdę',
        'submission_date' => '2026-10-15',
    ]);
});

test('employee cannot declare availability for another user', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();

    $this->postJson('/api/availabilities', [
        'user_id' => $otherUser->id,
        'date' => '2026-10-21',
        'is_available' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $this->employee->id);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-10-21',
    ]);

    $this->assertDatabaseMissing('availabilities', [
        'user_id' => $otherUser->id,
    ]);
});

test('availability is updated if exists for the same date', function () {
    /** @var \Tests\TestCase $this */
    Availability::factory()
        ->forUser($this->employee->id)
        ->forDate('2026-10-20')
        ->create(['submission_date' => '2026-10-01']);

    $this->postJson('/api/availabilities', [
        'date' => '2026-10-20',
        'is_available' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.is_available', false);

    expect(Availability::where('user_id', $this->employee->id)->count())
        ->toBe(1);

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => '2026-10-20',
        'is_available' => false,
        'submission_date' => '2026-10-01',
    ]);
});

test('employee can declare availability from the first day of the previous month', function (string $today, string $date, int $expectedStatus) {
    /** @var \Tests\TestCase $this */
    $this->travelTo(Carbon::parse($today));

    $response = $this->postJson('/api/availabilities', [
        'date' => $date,
        'is_available' => true,
    ])->assertStatus($expectedStatus);

    if ($expectedStatus === 422) {
        $response->assertJsonValidationErrors(['date']);
        $this->assertDatabaseCount('availabilities', 0);

        return;
    }

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $this->employee->id,
        'date' => $date,
    ]);
})->with([
    'first day of previous month' => ['2026-10-15', '2026-09-01', 201],
    'last day before the window' => ['2026-10-15', '2026-08-31', 422],
    'Mar 31: Feb 1 is still allowed' => ['2026-03-31', '2026-02-01', 201],
    'Mar 31: Jan 31 is too early' => ['2026-03-31', '2026-01-31', 422],
]);

test('admin can declare availability for an employee without a date limit', function () {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->create();

    $this->actingAsAdmin()
        ->postJson('/api/availabilities', [
            'user_id' => $employee->id,
            'date' => '2025-10-15',
            'is_available' => false,
            'notes' => 'Urlop',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('availabilities', [
        'user_id' => $employee->id,
        'date' => '2025-10-15',
        'is_available' => false,
        'notes' => 'Urlop',
    ]);
});

test('admin must specify the user', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAsAdmin()
        ->postJson('/api/availabilities', [
            'date' => '2026-10-20',
            'is_available' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);

    $this->assertDatabaseCount('availabilities', 0);
});

test('manager cannot create availability', function (bool $withUserId) {
    /** @var \Tests\TestCase $this */
    /** @var User $employee */
    $employee = User::factory()->employee()->create();

    $payload = [
        'date' => '2026-10-20',
        'is_available' => true,
    ];

    if ($withUserId) {
        $payload['user_id'] = $employee->id;
    }

    $this->actingAsManager()
        ->postJson('/api/availabilities', $payload)
        ->assertForbidden();

    $this->assertDatabaseCount('availabilities', 0);
})->with([
    'with user_id' => [true],
    'without user_id' => [false],
]);

test('availability date must be a Y-m-d calendar date', function (string $date) {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/availabilities', [
        'date' => $date,
        'is_available' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);

    $this->assertDatabaseCount('availabilities', 0);
})->with([
    'relative date' => ['tomorrow'],
    'date with time' => ['2026-11-10 23:00'],
    'day-month-year order' => ['10-11-2026'],
    'non-existent day' => ['2026-02-30'],
]);

test('employee can delete their own availability', function () {
    /** @var \Tests\TestCase $this */
    /** @var Availability $availability */
    $availability = Availability::factory()->forUser($this->employee->id)->forDate('2026-10-20')->create();

    $this->deleteJson("/api/availabilities/{$availability->id}")
        ->assertOk();

    $this->assertModelMissing($availability);
});

test('employee and manager cannot delete another users availability', function (string $role) {
    /** @var \Tests\TestCase $this */
    /** @var User $otherUser */
    $otherUser = User::factory()->employee()->create();
    /** @var Availability $availability */
    $availability = Availability::factory()->forUser($otherUser->id)->forDate('2026-10-20')->create();

    if ($role === 'manager') {
        $this->actingAsManager();
    }

    $this->deleteJson("/api/availabilities/{$availability->id}")
        ->assertForbidden();

    $this->assertModelExists($availability);
})->with(['employee', 'manager']);

test('admin can delete another users availability', function () {
    /** @var \Tests\TestCase $this */
    /** @var Availability $availability */
    $availability = Availability::factory()->forUser($this->employee->id)->forDate('2026-10-20')->create();

    $this->actingAsAdmin()
        ->deleteJson("/api/availabilities/{$availability->id}")
        ->assertOk();

    $this->assertModelMissing($availability);
});

test('deleting a non-existent availability returns not found', function () {
    /** @var \Tests\TestCase $this */
    $this->deleteJson('/api/availabilities/999999')
        ->assertNotFound();
});
