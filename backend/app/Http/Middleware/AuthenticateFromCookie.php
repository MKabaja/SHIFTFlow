<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie('jwt_token') && ! $request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer '.$request->cookie('jwt_token'));
        }

        return $next($request);
    }
}
