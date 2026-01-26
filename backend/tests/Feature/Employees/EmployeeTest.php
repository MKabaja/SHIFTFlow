<?php

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $this->admin
     */
    $this->actingAsAdmin();
    $this->seedPositions();

});
// store
test('admin can create an employee', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    $payload = $this->getValidEmployeePayload();

    $this->postJson('/api/employees', $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'login' => 'jkowal',
        'role' => 'employee',
    ]);

    $user = User::where('login', 'jkowal')
        ->firstOrFail();

    $this->assertDatabaseHas('position_user', [
        'user_id' => $user->id,
        'position_id' => $this->b1->id,
    ]);
    expect($user->positions)->toHaveCount(2);
});
// update
test('admin can update an employee with valid data', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    $employee = User::factory()->create([
        'name' => 'bad name',
        'login' => 'jkowal',
        'role' => 'employee',
        'max_minutes_per_month' => null,
        'max_minutes_per_quarter' => null,
        'min_break_minutes' => null,
        'hourly_rate' => null,
    ]);
    $payload = $this->getValidEmployeePayload([
        'name' => 'Jan Kowalski',
        'login' => 'jkowal',
        'role' => 'employee',
        'max_minutes_per_month' => 10080,
        'max_minutes_per_quarter' => 30240,
        'min_break_minutes' => 660,
        'hourly_rate' => 25,
    ]);

    $this->putJson("/api/employees/{$employee->id}", $payload)
        ->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $employee->id,
        'name' => 'Jan Kowalski',
        'login' => 'jkowal',
        'role' => 'employee',
        'max_minutes_per_month' => 10080,
        'max_minutes_per_quarter' => 30240,
        'min_break_minutes' => 660,
        'hourly_rate' => 25,

    ]);

});
// index
test('it respects the per_page query parameter', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    $this->withoutExceptionHandling();
    User::factory()
        ->employee()
        ->count(15)
        ->create();

    $this->getJson('/api/employees?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 15)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.current_page', 1);

});

test('it can search for employees by name', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    User::factory()->create([
        'name' => 'Jan Kowalski',
        'login' => 'jkowal',
        'role' => 'employee',
    ]);
    User::factory()->create([
        'name' => 'Anna Nowak',
        'login' => 'anowak',
        'role' => 'employee',
    ]);
    $this->getJson('/api/employees?search=Kowal')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Jan Kowalski')
        ->assertJsonPath('meta.total', 1);

});
test('it returns an empty list when search yields no results', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    User::factory()->create([
        'name' => 'Anna Nowak',
        'login' => 'anowak',
        'role' => 'employee',
    ]);
    $this->getJson('/api/employees?search=XYZ')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);

});
test('pagination links contain the search and per_page parameters', function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $admin
     */
    User::factory()
        ->employee()
        ->count(15)
        ->create([
            'name' => 'Jan Kowalski',
            'role' => 'employee',
        ]);

    $searchTerm = 'Kowalski';
    $perPage = 5;
    $response = $this->getJson("/api/employees?search={$searchTerm}&per_page={$perPage}");

    $response->assertOk();

    $nextPageUrl = $response->json('links.next');

    expect($nextPageUrl)
        ->toContain("search={$searchTerm}")
        ->toContain("per_page={$perPage}")
        ->toContain('page=2');
});
// delete
test('admin can delete an employee', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()
        ->employee()
        ->create();

    $this->deleteJson("/api/employees/{$employee->id}")
        ->assertStatus(200);

    $this->assertSoftDeleted('users', ['id' => $employee->id]);

});
test('non-admin user cannot delete an employee', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()
        ->employee()
        ->create();

    $otherEmployee = User::factory()
        ->employee()
        ->create();

    /** @var \App\Models\User $otherEmployee */
    $this->actingAs($otherEmployee, 'api');

    $this->deleteJson("/api/employees/{$employee->id}")
        ->assertStatus(403);
});
// show
test('admin can show an employee', function () {
    /** @var \Tests\TestCase $this */
    $employee = User::factory()
        ->employee()
        ->create([
            'name' => 'Sebastian Witczak',
            'login' => 'switcz',
            'is_active' => true,
            'max_minutes_per_month' => 10080,
            'max_minutes_per_quarter' => 30240,
            'min_break_minutes' => 660,
            'hourly_rate' => 25,

        ]);

    $this->getJson("/api/employees/{$employee->id}")
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $employee->id,
                'name' => 'Sebastian Witczak',
                'login' => 'switcz',
                'is_active' => true,
                'monthly_hour_limit' => 168,
                'quarter_hour_limit' => 504,
                'break_limit' => 11,
                'hourly_rate' => 25,
            ],
        ]);

});
