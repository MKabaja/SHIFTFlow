<?php

namespace App\Repositories;

use App\DataTransferObjects\EmployeeImportData;
use App\Models\Position;
use App\Models\User;
use App\Services\LoginGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            'pin_hashed' => Hash::make('1234'),
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
}
