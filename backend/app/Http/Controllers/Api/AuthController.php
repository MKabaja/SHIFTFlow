<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{
    /**
     * Test endpoint — zwraca zawsze token
     * 
     * GET /api/auth/test
     * 
     * Cel: Sprawdzić czy JWT działa poprawnie
     */
    public function test()
    {   // Biorę user z ID 1 (powinien istnieć z Breeze)
        $user = User::find(1);

        if(!$user) {
            return response()->json(['error' => 'User not found'],404);
        }

        //generuje token JWT dla tego usera
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'message' => 'JWT token generated successfully',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user' => $user,
        ]);
    }
    /**
     * Login endpoint — autentyfikacja za emailem i hasłem
     * 
     * POST /api/auth/login
     * 
     * Body:
     * {
     *   "email": "user@example.com",
     *   "password": "password"
     * }
     */
    public function login(Request $request)
    {
        //walidacja
        $credentials = $request->validate([
            'email'=> 'required|email',
            'password'=> 'required|min:6',
        ]);

        // Próba Logowania
        if(!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'],401);
        }
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user'=> auth('api') ->user(),
        ]);
    }
    /**
     * Logout endpoint
     * 
     * POST /api/auth/logout
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Logged out successfully']);
    }
}