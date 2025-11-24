<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


//===============================================
//prefix('auth') — wszystkie routy zaczynają się od /api/auth

//group() — grupujemy razem
Route::prefix('auth')->group(function() {
    Route::get('/test',[AuthController::class,'test']);

    Route::post('/login',[AuthController::class,'login']);

    Route::post('/logout',[AuthController::class,'logout'])->middleware('auth:api');

    // middleware('auth:api')-Wymaga JWT tokenu w headzie Authorization: Bearer <token>
});
