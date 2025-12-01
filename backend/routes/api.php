<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScheduleController;


//===============================================
//prefix('auth') — wszystkie routy zaczynają się od /api/auth

//Logowanie
Route::prefix('auth')
->controller(AuthController::class)
->group(function() {
    Route::post('/login','login');

    Route::post('/login-pin','loginPin');

    Route::middleware('auth:api')
    ->group(function() {
        Route::get('/me', 'me');

        Route::post('/logout','logout');
    });
});

        
//Crud Grafiku
Route::middleware('auth:api')
->prefix('schedules')
->controller(ScheduleController::class)
->group(function() {
    Route::get('/','index');
});




Route::middleware('auth:api') -> group(function() {
    Route::get('/admin-only',function() {
            return ['message' => 'welcome admin!'];
        })->middleware('role:admin');
});

   



    

    
