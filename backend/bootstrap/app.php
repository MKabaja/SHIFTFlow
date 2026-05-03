<?php

declare(strict_types=1);

use App\Http\Middleware\CheckJwtBlacklist;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'jwt.blacklist' => CheckJwtBlacklist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*');
        });

        $exceptions->render(function (TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired'], 401);
        });
        $exceptions->render(function (TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid'], 401);
        });
        $exceptions->render(function (JWTException $e) {
            return response()->json(['message' => 'Token not provided'], 401);
        });
    })->create();
