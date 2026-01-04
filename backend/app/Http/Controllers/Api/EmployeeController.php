<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Services\Import\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    protected $employeeRepository;

    protected $importService;

    public function __construct(EmployeeRepository $employeeRepository, ImportService $importService)
    {
        $this->employeeRepository = $employeeRepository;
        $this->importService = $importService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $employees = User::where('role', 'employee')
            ->with('positions')
            ->paginate(10);

        return UserResource::collection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();

        $employee = $this->employeeRepository->createEmployee($data);
        $employee->load('positions');

        return (new UserResource($employee))
            ->additional(['message' => 'Employee created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $employee): UserResource
    {
        if ($employee->role !== 'employee') {
            abort(404);
        }

        return new UserResource($employee->load('positions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, User $employee): JsonResponse
    {
        if ($employee->role !== 'employee') {
            abort(404);
        }
        $data = $request->validated();
        $updatedEmployee = $this->employeeRepository->updateEmployee($employee, $data);
        $updatedEmployee->load('positions');

        return (new UserResource($updatedEmployee))
            ->additional(['message' => 'Employee updated successfully'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee): JsonResponse
    {
        if ($employee->role !== 'employee') {
            abort(404);
        }
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }

    public function importFromCsv(Request $request): JsonResponse
    {

        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $stats = $this->importService->import($request->file('csv'));

        $statusCode = $stats['success'] ? 200 : 422;

        return response()->json($stats, $statusCode);
    }
}
