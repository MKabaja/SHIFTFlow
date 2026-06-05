<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPinRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\JwtBlacklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
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

        $cookie = cookie(
            'jwt_token',
            $token,
            config('jwt.ttl'),
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Lax'
        );

        return response()->json(['user' => new UserResource($user)])->withCookie($cookie);
    }

    public function loginPin(LoginPinRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('login', $validated['login'])->first();

        if (! $user || ! Hash::check($validated['pin'], $user->pin_hashed ?? '')) {
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

        $cookie = cookie(
            'jwt_token',
            $token,
            config('jwt.ttl'),
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Lax'
        );

        return response()->json(['user' => new UserResource($user)])->withCookie($cookie);
    }

    public function logout(): JsonResponse
    {
        $token = JWTAuth::getToken();

        if (! $token) {
            return response()->json(['message' => 'No token provided'], 401);
        }
        $tokenInfo = $this->getTokenInfo();
        $this->jwtBlacklistService->setBlacklist($tokenInfo['jti'], $tokenInfo['ttl']);

        return response()->json(['message' => 'Logged out successfully'], 200)
            ->withCookie(Cookie::forget('jwt_token'));

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
