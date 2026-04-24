<?php

namespace App\Repositories;

use App\DataTransferObjects\EmployeeImportData;
use App\Models\Position;
use App\Models\User;
use App\Services\LoginGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    public function __construct(
        private readonly LoginGeneratorService $loginGeneratorService
    ) {}

    public function saveMany(Collection $employeeDataCollection): array
    {
        $positionsMap = Position::pluck('id', 'name');

        return DB::transaction(function () use ($employeeDataCollection, $positionsMap) {
            return $this->persistImportedEmployees($employeeDataCollection, $positionsMap);
        });
    }

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $login = $this->generateUniqueLogin($data['name']);

            $payload = [
                'pin_hashed' => $data['pin'],
                'role' => 'employee',
                'login' => $login,
                'name' => $data['name'],
                'hourly_rate' => $data['hourly_rate'],
                'email' => null,
                'max_minutes_per_month' => $data['max_minutes_per_month'] ?? null,
                'max_minutes_per_quarter' => $data['max_minutes_per_quarter'] ?? null,
                'min_break_minutes' => $data['min_break_minutes'] ?? null,
                'contract_type' => $data['contract_type'] ?? 'employment_contract',
            ];
            $user = User::create($payload);
            $user->positions()->attach($data['positions']);

            return $user;
        });
    }

    public function updateEmployee(User $user, array $data): User
    {
        return DB::transaction(function () use ($data, $user) {

            if (isset($data['pin'])) {
                $data['pin_hashed'] = $data['pin'];
                unset($data['pin']);
            }

            if (isset($data['positions'])) {
                $positionsData = $data['positions'];
                unset($data['positions']);
            } else {
                $positionsData = null;
            }

            $user->update($data);

            if ($positionsData !== null) {
                $user->positions()->sync($positionsData);
            }

            return $user;
        });
    }

    private function persistImportedEmployees(Collection $employeeDataCollection, Collection $positionsMap): array
    {
        $created = 0;
        $updated = 0;

        $allExistingLogins = User::pluck('login')->toArray();

        $existingUsersByName = $this->getExistingUsers($employeeDataCollection);

        foreach ($employeeDataCollection as $data) {
            $positionIDs = $this->getPositionIds($data->positions, $positionsMap);

            $payload = $this->prepareEmployeePersistenceData($data, $allExistingLogins, $existingUsersByName);

            $user = $this->saveEmployee($payload, $positionIDs);

            $allExistingLogins[] = $payload['login'];

            if ($user->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    private function saveEmployee(array $employeeData, Collection $positionIDs): User
    {
        $user = User::updateOrCreate([
            'name' => $employeeData['name'],
        ], $employeeData);

        $user->positions()->sync($positionIDs);

        return $user;

    }

    private function getPositionIds(array $positionNames, Collection $positionsMap): Collection
    {
        return collect($positionNames)
            ->map(fn ($name) => $positionsMap[$name] ?? null)
            ->filter()
            ->values();
    }

    private function prepareEmployeePersistenceData(EmployeeImportData $data, array $allExistingLogins, Collection $existingUsersByName): array
    {
        if ($existingUsersByName->has($data->name)) {
            $login = $existingUsersByName->get($data->name);

        } else {

            $login = $this->loginGeneratorService->generate($data->name, $allExistingLogins);
        }

        return [
            'name' => $data->name,
            'login' => $login,
            'contract_type' => $data->contractType,
            'pin_hashed' => config('app.default_employee_pin'), // Onboarding default — employee changes PIN on first login
            'role' => 'employee',
            'email' => null,
        ];
    }

    private function getExistingUsers(Collection $collection): Collection
    {
        return User::whereIn('name', $collection
            ->pluck('name'))
            ->pluck('login', 'name');
    }

    private function generateUniqueLogin(string $name): string
    {
        $allLogins = User::pluck('login')->toArray();

        return $this->loginGeneratorService->generate($name, $allLogins);
    }
}
