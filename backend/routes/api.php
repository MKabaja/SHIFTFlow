<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\PositionController;


//===============================================


//Login
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


//Schedule Crud

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

//Positions G1
Route::middleware(['auth:api', 'role:admin,manager'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{position}', 'show');
    });
//Positions G2
Route::middleware(['auth:api', 'role:admin'])
    ->prefix('positions')
    ->controller(PositionController::class)
    ->group(function () {
        Route::post('/', 'store');
        Route::match(['put', 'patch'], '/{position}', 'update');
        Route::delete('/{position}', 'destroy');
    });








Route::middleware('auth:api')->group(function () {
    Route::get('/admin-only', function () {
        return ['message' => 'welcome admin!'];
    })->middleware('role:admin');
});
