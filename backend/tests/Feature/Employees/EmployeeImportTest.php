<?php

use App\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    /** @var \Tests\TestCase $this
     * @var \App\Models\User $this->admin
     */
    $this->admin = User::factory()
        ->admin()
        ->create();
    $this->csvHeader = "ID,Imię i Nazwisko,Typ umowy,PD,B\n";

    $this->actingAs($this->admin, 'api');

    Position::factory()
        ->PD()
        ->create();
    Position::factory()
        ->B1()
        ->create();

});

it('successfully imports employees from csv file', function () {
    /** @var \Tests\TestCase $this */
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            "1,Jan Kowalski,UOP,TAK,\n".
            '2,Anna Nowak,ZLEC,,TAK'
        );

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'created' => 2,
            'updated' => 0,
            'total' => 2,
        ])
        ->assertJsonPath('validation_issues', []);

    $this->assertDatabaseCount('users', 3); // + admin

    $this->assertDatabaseHas('users', [
        'name' => 'jan kowalski',
        'role' => 'employee',
        'contract_type' => 'employment_contract',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'anna nowak',
        'role' => 'employee',
        'contract_type' => 'mandate_contract',
    ]);

    // 4. Dodatkowa weryfikacja loginu
    $user = User::where('name', 'jan kowalski')->first();

    expect($user->login)
        ->not->toBeNull()
        ->and(strlen($user->login))->toBe(6);
});

it('preserves existing login when re-importing user', function () {
    /** @var \Tests\TestCase $this */
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            "1,Jan Kowalski,UOP,TAK,\n".
            '2,Anna Nowak,ZLEC,,TAK'
        );

    User::factory()->create([
        'name' => 'jan kowalski',
        'login' => 'jkowal',
        'role' => 'employee',
    ]);

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk()
        ->assertJson([
            'created' => 1,
            'updated' => 1,
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'jan kowalski',
        'login' => 'jkowal',
    ]);

});

it('updates existing user details instead of creating duplicates', function () {
    /** @var \Tests\TestCase $this */
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            "1,Jan Kowalski,UOP,TAK,\n". // <- create
            '2,Jan Kowalski,ZLEC,TAK,' // <- update
        );

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk()
        ->assertJson([
            'created' => 1,
            'updated' => 1,
        ]);
    $this->assertDatabaseCount('users', 2); // + admin

});

it('transliterates polish characters in generated login', function () {
    /** @var \Tests\TestCase $this */
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            '1,Łukasz Ślusarek,UOP,TAK,'
        );

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk()
        ->assertJson([
            'created' => 1,

        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'łukasz ślusarek',
        'login' => 'lslusa',
    ]);

});

it('appends numeric suffix to login when collision occurs', function () {
    /** @var \Tests\TestCase $this */
    User::factory()->create([
        'name' => 'jan kowalczyk',
        'login' => 'jkowal',
    ]);
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            '1,Jan Kowalski,UOP,TAK,'
        );

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk()
        ->assertJson([
            'created' => 1,
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'jan kowalski',
        'login' => 'jkowal1',
    ]);

});

it('correctly assigns positions based on csv columns', function () {
    /** @var \Tests\TestCase $this */
    $csvFile = UploadedFile::fake()
        ->createWithContent(
            'employees.csv',
            $this->csvHeader.
            "1,Jan Kowalski,UOP,TAK,\n".
            '2,Anna Nowak,ZLEC,,TAK'
        );

    $this->postJson('/api/employees/import', ['csv' => $csvFile])
        ->assertOk();

    $jan = User::where('name', 'jan kowalski')->first();
    $anna = User::where('name', 'anna nowak')->first();

    expect($jan)
        ->not->toBeNull()
        ->positions->pluck('name')
        ->toContain('PD')
        ->not->toContain('B1');

    expect($anna)
        ->not->toBeNull()
        ->positions->pluck('name')
        ->toContain('B1')
        ->not->toContain('PD');

});
