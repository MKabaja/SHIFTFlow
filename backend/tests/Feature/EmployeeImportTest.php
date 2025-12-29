<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_csv_with_employees(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        Position::create(['name' => 'PD', 'description' => 'Dispatcher Assistant']);
        Position::create(['name' => 'PW',  'description' => 'Wisła Route Assistant']);

        $csvContent = "ID,Imię i Nazwisko,Typ umowy,PD,PW\n".
                      "1,Jan Kowalski,UOP,TAK,\n".
                      '2,Anna Nowak,ZLEC,,TAK';

        $csv = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $resposne = $this->actingAs($admin, 'api')->postJson('/api/employees/import', ['csv' => $csv]);
        $resposne->assertOk();
        $resposne->assertJson([
            'success' => true,
            'created' => 2,
            'updated' => 0,
            'total' => 2,
        ]);

        $resposne->assertJsonPath('validation_issues', []);

        $this->assertDatabaseCount('users', 3);

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

        $user = User::where('name', 'jan kowalski')->first();
        $this->assertNotNull($user->login);
        $this->assertEquals(6, strlen($user->login));
    }

    public function test_reimport_does_not_change_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $existingUser = User::factory()->create([
            'name' => 'jan kowalski',
            'login' => 'jkowal',
            'role' => 'employee',
        ]);

        $csv = UploadedFile::fake()->createWithContent('employees.csv',
            "ID,Imię i Nazwisko,Typ umowy,PD,PW\n1,Jan Kowalski,UOP,TAK,"
        );

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/employees/import', ['csv' => $csv]);

        $this->assertDatabaseHas('users', [
            'name' => 'jan kowalski',
            'login' => 'jkowal',
        ]);

        $response->assertJson([
            'created' => 0,
            'updated' => 1,
        ]);
    }

    public function test_import_csv_with_duplicate_names(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $csvContent = "ID,Imię i Nazwisko,Typ umowy,PD,PW\n".
                      "1,Jan Kowalski,UOP,TAK,\n".
                      '2,Jan Kowalski,ZLEC,TAK,';

        $csv = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/employees/import', ['csv' => $csv]);

        $this->assertDatabaseCount('users', 2);

        $response->assertJson([
            'created' => 1,
            'updated' => 1,
        ]);
    }

    public function test_transliterates_polish_characters_in_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $csvContent = "ID,Imię i Nazwisko,Typ umowy,PD,PW\n".
                      '1,Łukasz Ślusarek,UOP,TAK,';

        $csv = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/employees/import', ['csv' => $csv]);

        $this->assertDatabaseHas('users', [
            'name' => 'łukasz ślusarek',
            'login' => 'lslusa',
        ]);
    }

    public function test_handles_login_collisions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        User::factory()->create([
            'name' => 'jan kowalczyk',
            'login' => 'jkowal',
        ]);

        $csvContent = "ID,Imię i Nazwisko,Typ umowy,PD,PW\n".
                      '1,Jan Kowalski,UOP,TAK,';

        $csv = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/employees/import', ['csv' => $csv]);

        $this->assertDatabaseHas('users', [
            'name' => 'jan kowalski',
            'login' => 'jkowal1',
        ]);
    }

    public function test_assigns_positions_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $pd = Position::create(['name' => 'PD', 'description' => 'Dispatcher']);
        $pw = Position::create(['name' => 'PW', 'description' => 'Wisła']);

        $csvContent = "ID,Imię i Nazwisko,Typ umowy,PD,PW\n".
                      "1,Jan Kowalski,UOP,TAK,\n".
                      '2,Anna Nowak,ZLEC,,TAK';

        $csv = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/employees/import', ['csv' => $csv]);

        $jan = User::where('name', 'jan kowalski')->first();
        $this->assertTrue($jan->positions->contains('name', 'PD'));
        $this->assertFalse($jan->positions->contains('name', 'PW'));

        $anna = User::where('name', 'anna nowak')->first();
        $this->assertTrue($anna->positions->contains('name', 'PW'));
        $this->assertFalse($anna->positions->contains('name', 'PD'));
    }
}
