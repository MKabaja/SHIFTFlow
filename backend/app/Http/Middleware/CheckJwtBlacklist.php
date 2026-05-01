<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\JwtBlacklistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckJwtBlacklist
{
    protected JwtBlacklistService $jwtBlacklistService;

    public function __construct(JwtBlacklistService $jwtBlacklistService)
    {
        $this->jwtBlacklistService = $jwtBlacklistService;
    }

    public function handle(Request $request, Closure $next): Response
    {

        try {
            $jti = JWTAuth::parseToken()->getPayload()->get('jti');

            if ($this->jwtBlacklistService->isBlacklisted($jti)) {
                return response()->json(['error' => 'Token is blacklisted'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
