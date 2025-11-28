<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;




//===============================================
//prefix('auth') — wszystkie routy zaczynają się od /api/auth

//group() — grupujemy razem
Route::prefix('auth')->controller(AuthController::class)->group(function() {
    Route::post('/login','login');

    Route::post('/login-pin','loginPin');

    Route::middleware('auth:api')->group(function() {
        Route::get('/me', 'me');

        Route::post('/logout','logout');
    });
});
   


    

    
