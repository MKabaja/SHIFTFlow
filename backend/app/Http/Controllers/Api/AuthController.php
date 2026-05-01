<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPinRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\JwtBlacklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected JwtBlacklistService $jwtBlacklistService;

    public function __construct(JwtBlacklistService $jwtBlacklistService)
    {
        $this->jwtBlacklistService = $jwtBlacklistService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('login', $validated['login'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(
                [
                    'message' => 'Invalid password or login!',
                ],
                401
            );
        }

        if (! $user->is_active) {
            return response()->json(
                [
                    'message' => 'Account deactivated.',
                ],
                403
            );
        }
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
                'name' => $user->name,
                'role' => $user->role,
            ],

        ]);
    }

    public function loginPin(LoginPinRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('login', $validated['login'])->first();

        if (! $user || ! Hash::check($validated['pin'], $user->pin_hashed)) {
            return response()->json(
                [
                    'message' => 'Invalid pin or login',
                ],
                401
            );
        }

        if (! $user->is_active) {
            return response()->json(
                [
                    'message' => 'Account deactivated.',
                ],
                403
            );
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('positions');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'positions' => $user->positions,
            'status' => $user->is_active,
            'hourly_rate' => $user->hourly_rate,
            'login' => $user->login,
        ]);
    }

    public function logout(): JsonResponse
    {
        $token = JWTAuth::getToken();

        if (! $token) {
            return response()->json(['message' => 'No token provided'], 401);
        }
        $tokenInfo = $this->getTokenInfo();
        $this->jwtBlacklistService->setBlacklist($tokenInfo['jti'], $tokenInfo['ttl']);

        return response()->json(['message' => 'Logged out successfully'], 200);

    }

    /**
     * @return array{ttl: int, jti: string}
     */
    private function getTokenInfo(): array
    {
        $payload = JWTAuth::parseToken()->getPayload();

        return [
            'jti' => $payload->get('jti'),
            'ttl' => $payload->get('exp') - now()->timestamp,
        ];
    }
}
