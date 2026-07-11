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
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;
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

        $user->load('positions');

        return UserResource::make($user)->response()->withCookie($this->jwtCookie($token));
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

        $user->load('positions');

        return UserResource::make($user)->response()->withCookie($this->jwtCookie($token));
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
     * Build the JWT auth cookie carrying the session token.
     *
     * Single source of truth for the cookie's security attributes, shared by
     * login and login-pin. Wraps Cookie::make(), whose positional arguments are:
     *
     *  1. name     — 'jwt_token', the cookie key the auth middleware reads.
     *  2. value    — the signed JWT string.
     *  3. minutes  — lifetime; mirrors config('jwt.ttl') so cookie and token expire together.
     *  4. path     — '/', valid across the whole app.
     *  5. domain   — null → host-only: NO Domain attribute, so it is NOT shared with sibling
     *                subdomains (required by the same-origin architecture).
     *  6. secure   — true only in production, so it still works over plain http locally.
     *  7. httpOnly — true: unreadable from JavaScript (XSS mitigation).
     *  8. raw      — false: the value is URL-encoded.
     *  9. sameSite — 'Lax': sent on top-level navigation, blocks cross-site sends (CSRF mitigation).
     *
     * @param  string  $token  Signed JWT to store in the cookie value.
     * @return HttpCookie Host-only, HttpOnly, SameSite=Lax cookie; Secure on production.
     */
    private function jwtCookie(string $token): HttpCookie
    {
        return Cookie::make(
            'jwt_token',                       // 1. name
            $token,                            // 2. value: signed JWT
            (int) config('jwt.ttl'),           // 3. minutes: same lifetime as the token
            '/',                               // 4. path: whole app
            null,                              // 5. domain: host-only (no Domain attribute)
            app()->environment('production'),  // 6. secure: HTTPS-only on prod, http-ok locally
            true,                              // 7. httpOnly: not readable by JavaScript
            false,                             // 8. raw: URL-encode the value
            'Lax'                              // 9. sameSite: CSRF mitigation
        );
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
