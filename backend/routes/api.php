<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

// ===============================================

// Login
Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/login', 'login');

        Route::post('/login-pin', 'loginPin');

        Route::middleware('auth:api')
            ->group(function () {
                Route::get('/me', 'me');

                Route::post('/logout', 'logout');
            });
    });

// Shift Crud

Route::middleware(['auth:api', 'role:admin,manager'])
    ->prefix('shifts')
    ->controller(ShiftController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{shift}', 'update');
        Route::delete('/{shift}', 'destroy');
        Route::get('/{shift}', 'show');
    });
// Schedule Crud
Route::middleware(['auth:api', 'role:admin,manager'])
    ->prefix('schedules')
    ->controller(ScheduleController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{schedule}', 'update');
        Route::delete('/{schedule}', 'destroy');
        Route::get('/{schedule}', 'show');
    });

// Positions G1
Route::middleware(['auth:api', 'role:admin,manager'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{position}', 'show');
    });
// Positions G2
Route::middleware(['auth:api', 'role:admin'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{position}', 'update');
        Route::delete('/{position}', 'destroy');
    });

// Employee Management (CRUD)
Route::middleware(['auth:api', 'role:admin'])
    ->prefix('employees')
    ->controller(EmployeeController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::post('/import', 'importFromCsv');
        Route::get('/', 'index');
        Route::match(['put', 'patch'], '/{employee}', 'update');
        Route::delete('/{employee}', 'destroy');
        Route::get('/{employee}', 'show');
    });

// Availabilities
Route::middleware(['auth:api'])
    ->prefix('availabilities')
    ->controller(AvailabilityController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/{availability}', 'destroy');
    });

// Reports
Route::middleware(['auth:api', 'role:admin,manager'])
    ->prefix('reports')
    ->controller(ReportController::class)
    ->group(function () {
        Route::get('/coverage', 'coverageSummary');
        Route::get('/payroll', 'payrollSummary');
        Route::get('/{user}', 'employeeHours');

    });
