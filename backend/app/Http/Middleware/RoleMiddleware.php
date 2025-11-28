<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */


    private function deny(string $message, int $code = 403):Response{
            Log::warning($message,['user_id' => Auth::id()]);
            return response()->json(['message'=> $message], $code);
        }

    public function handle(Request $request, Closure $next,...$roles): Response
    {
        if(!Auth::check()) {
         return $this->deny('Unauthorized.', 401);
        }  
        
        $role = Auth::user()?->role;
        
        if(empty($role)) return $this->deny('User has no role');

       if (empty($roles)) return $this->deny('Middleware roles are not defined',500);
        
       if(!in_array($role, $roles)) return $this->deny('Role not allowed');
       


           

        

        return $next($request);
    }
    
}
