<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return $this->deny('Unauthorized.', 401);
        }

        $role = Auth::user()?->role;

        if (empty($role)) {
            return $this->deny('User has no role');
        }

        if (empty($roles)) {
            return $this->deny('Middleware roles are not defined', 500);
        }

        if (! in_array($role, $roles, true)) {
            return $this->deny('Role not allowed');
        }

        return $next($request);
    }

    private function deny(string $message, int $code = 403): Response
    {
        Log::warning($message, ['user_id' => Auth::id()]);

        return response()->json(['message' => $message], $code);
    }
}
