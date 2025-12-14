<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreEmployeeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $emplyees = User::where('role',  'employee')
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
            $baseLogin = $this->createLogin($request->name, 5);
            $checkedLogin = $this->findUniqueLogin($baseLogin);

            $payload = [
                'pin_hashed' => Hash::make($request->pin),
                'role' => 'employee',
                'login' => $checkedLogin,
                'name' =>  $request->name,
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function createLogin($fullName, $length)
    {

        $length = max(1, $length);

        $parts = explode(' ', trim($fullName));

        if (count($parts) < 2) {
            $maxLoginLength = 10;
            return mb_substr($parts[0], 0, $maxLoginLength, 'UTF-8');
        }

        $firstName = $parts[0];
        $lastName = $parts[1];

        $firstLetter = mb_substr($firstName, 0, 1, 'UTF-8');

        $lastNameLength = mb_strlen($lastName, 'UTF-8');



        if ($lastNameLength <= $length) {
            $lastNameFragment = $lastName;
        } else {
            $lastNameFragment = mb_substr($lastName, 0, $length, 'UTF-8');
        }
        $login = $firstLetter . $lastNameFragment;

        return mb_strtolower($login, 'UTF-8');
    }

    private function findUniqueLogin(string $baseLogin): string
    {
        if (User::where('login', $baseLogin)->doesntExist()) {
            return $baseLogin;
        }


        $i = 1;
        $uniqueLogin = $baseLogin . $i;

        while (User::where('login', $uniqueLogin)->exists()) {
            $i++;
            $uniqueLogin = $baseLogin . $i;
        }
        return $uniqueLogin;
    }
}
