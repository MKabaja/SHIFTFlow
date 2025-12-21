<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\Import\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    protected $employeeService;

    protected $importService;

    public function __construct(EmployeeService $employeeService, ImportService $importService)
    {
        $this->employeeService = $employeeService;
        $this->importService = $importService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $emplyees = User::where('role', 'employee')
            ->with('positions')
            ->get();

        return response()->json($emplyees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = DB::transaction(function () use ($request) {
            $baseLogin = $this->employeeService->createLogin($request->name, 5);
            $checkedLogin = $this->employeeService->findUniqueLogin($baseLogin);

            $payload = [
                'pin_hashed' => Hash::make($request->pin),
                'role' => 'employee',
                'login' => $checkedLogin,
                'name' => $request->name,
                'hourly_rate' => $request->hourly_rate,
                'email' => null,
            ];

            $user = User::create($payload);
            $user->positions()->attach($request->positions);

            return $user;
        });

        return response()->json([
            'user' => $employee->load('positions'),
            'message' => 'Employee created succesfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $employee): User
    {
        return $employee->load('positions');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, User $employee): JsonResponse
    {
        $data = $request->validated();

        if ($request->filled('pin')) {
            $data['pin_hashed'] = Hash::make($data['pin']);
            unset($data['pin']);
        }
        if (isset($data['positions'])) {
            $positionsData = $data['positions'];
            unset($data['positions']);
        } else {
            $positionsData = null;
        }

        $employee->update($data);

        if ($positionsData !== null) {
            $employee->positions()->sync($positionsData);
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee->load('positions'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee)
    {
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }

    public function import(Request $request)
    {
        // 1. Walidacja, czy plik w ogóle jest
        // $request->validate([
        //     'file' => 'required|file|mimes:csv,txt'
        // ]);

        // 2. Wywołanie serwisu (który zaraz napiszesz)
        $stats = $this->importService->import($request->file('file'));

        return response()->json($stats);
    }
}
