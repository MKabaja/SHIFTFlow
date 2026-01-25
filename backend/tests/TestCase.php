<?php

namespace Tests;

use App\Models\Availability;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

uses(RefreshDatabase::class);
abstract class TestCase extends BaseTestCase
{
    public User $manager;

    public User $employee;

    public User $admin;

    public Position $position;

    public Schedule $schedule;

    public Availability $availability;

    public string $csvHeader;

    public Position $pd;

    public Position $b1;

    public Position $k1;

    public Position $ptg;

    public function actingAsAdmin(): self
    {
        $this->admin = User::factory()
            ->admin()
            ->create();

        return $this->actingAs($this->admin, 'api');
    }

    public function seedPositions(): void
    {

        $this->pd = Position::factory()
            ->PD()
            ->create();

        $this->b1 = Position::factory()
            ->B1()
            ->create();
        $this->k1 = Position::factory()
            ->K1()
            ->create();
        $this->ptg = Position::factory()
            ->PTG()
            ->create();
    }

    public function getValidEmployeePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jan Kowalski',
            'pin' => '1234',
            'hourly_rate' => 22,
            'role' => 'employee',
            'positions' => [$this->b1->id, $this->pd->id],
        ], $overrides);
    }
}
