<?php

namespace App\Repositories;

use App\DataTransferObjects\EmployeeImportData;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeRepository
{
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {}

    // EmployeeRepository
    public function saveMany(Collection $employeeDataCollection): void
    {
        $positionsMap = Position::pluck('id', 'name');

        DB::transaction(function () use ($employeeDataCollection, $positionsMap) {
            foreach ($employeeDataCollection as $data) {

                $positionIDs = $this->getPositionIds($data->positions, $positionsMap);
                $payload = $this->prepareEmployeePersistenceData($data);

                $this->saveEmployee($payload, $positionIDs);

            }

        });

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

    private function prepareEmployeePersistenceData(EmployeeImportData $data): array
    {
        $baseLogin = $this->employeeService->createLogin($data->name, 5);
        $checkedLogin = $this->employeeService->findUniqueLogin($baseLogin);

        return [
            'name' => $data->name,
            'login' => $checkedLogin,
            'contract_type' => $data->contractType,
            'pin_hashed' => Hash::make('1234'),
            'role' => 'employee',
            'email' => null,

        ];
    }
}
