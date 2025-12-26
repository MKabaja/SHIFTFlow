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

        DB::transaction(function () use ($employeeDataCollection): void {

            foreach ($employeeDataCollection as $data) {
                $positionIDs = $this->getPositionIds($data->positions);

                $payload = $this->prepareEmployeePersistenceData($data);
                $user = $this->saveEmployee($payload, $positionIDs);

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

    private function getPositionIds(array $positionNames): Collection
    {
        return Position::whereIn('name', $positionNames)->pluck('id');
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
