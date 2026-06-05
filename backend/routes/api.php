<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

// ===============================================

// Login
Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::middleware('throttle:5,1') // Limit to 5 attempts per minute
            ->group(function () {

                Route::post('/login', 'login');
                Route::post('/login-pin', 'loginPin');
            });

        Route::middleware(['auth:api', 'jwt.blacklist']) // Protect logout and me routes
            ->group(function () {

                Route::get('/me', 'me');
                Route::post('/logout', 'logout');
            });
    });

// Shift Crud

Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin,manager'])
    ->prefix('shifts')
    ->controller(ShiftController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{shift}', 'update');
        Route::delete('/{shift}', 'destroy');
        Route::get('/{shift}', 'show');
    });
Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin,manager,employee']) // Shifts — employee read-only (index only, filtered by role in controller)
    ->prefix('shifts')
    ->controller(ShiftController::class)
    ->group(function () {
        Route::get('/', 'index');

    });
// Schedule Crud + Batch
Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin,manager'])
    ->prefix('schedules')
    ->controller(ScheduleController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{schedule}', 'update');
        Route::delete('/{schedule}', 'destroy');
        Route::get('/{schedule}', 'show');
        Route::post('/{schedule}/shifts/batch', 'addShiftsBatch');
        Route::post('/{schedule}/publish', 'publish');
    });

// Positions G1
Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin,manager'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{position}', 'show');
        Route::get('/{position}/shifts', 'shifts');
    });
// Positions G2
Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{position}', 'update');
        Route::delete('/{position}', 'destroy');
    });

// Employee Management (CRUD)
Route::middleware(['auth:api', 'jwt.blacklist',  'role:admin'])
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

// News — read (all authenticated roles)
Route::middleware(['auth:api', 'jwt.blacklist'])
    ->prefix('news')
    ->controller(NewsController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{newsPost}', 'show');
    });
// News — write (admin only)
Route::middleware(['auth:api', 'jwt.blacklist', 'role:admin'])
    ->prefix('news')
    ->controller(NewsController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{newsPost}', 'update');
        Route::delete('/{newsPost}', 'destroy');
    });

// Availabilities
Route::middleware(['auth:api', 'jwt.blacklist'])
    ->prefix('availabilities')
    ->controller(AvailabilityController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/{availability}', 'destroy');
    });
