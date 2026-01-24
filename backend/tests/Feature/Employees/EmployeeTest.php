<?php

use App\Models\User;

beforeEach(function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $this->admin
     */
    $this->actingAsAdmin();
    $this->seedPositions();

});

test('admin can create employee', function () {
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
