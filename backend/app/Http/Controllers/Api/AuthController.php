<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{
    // PIN Token lifetime
    private const TTL_PIN =3600;
    //Password Token lifetime
    private const TTL_PASSWORD = 3600 *9;
   
    public function login(Request $request)
    {
        //walidacja
         $request->validate([
            'email'=> 'required|email',
            'password'=> 'required|min:6',
        ]);
        $user = User::where('email',$request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' =>'Invalid Password or Email!'],401);
        }
        $token = JWTAuth::fromUser($user);  

        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => self::TTL_PASSWORD,
            'user'=> [
                'id'=> $user->id,
                'email'=> $user->email,
                'name'=> $user->name,
                'role'=> $user->role,
                ]

        ]);
    }

    public function loginPin(Request $request)
    {
         $request->validate([
            'employee_id' =>'required|exists:users,id',
            'pin' => 'required|string|min:4',
        ]);
        
        $user = User::find($request->employee_id);
        
        if(!$user || !Hash::check($request->pin,$user->pin_hashed)) {
            return response()->json(['message'=> 'Invalid PIN or ID'],401);
        } 

        $token = JWTAuth::fromUser($user);


           
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => self::TTL_PIN,
            'user'=> [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
    public function me(Request $request)
    {
        $user = $request->user();
       
        return response()->json([
            'id'=> $user->id,
            'name'=> $user->name,
            'email'=> $user->email,
            'role'=> $user->role,
            'positions'=> $user->positions,
            'status'=> $user->is_active,
            'hourly_rate'=>$user->hourly_rate
        ]);
    }

       
        
            




    
    /**
     * Logout endpoint
     * 
     * POST /api/auth/logout
     */
    public function logout()
    {
        $token = JWTAuth::getToken();

        if (!$token) {
            return response()->json(['message'=> 'No token provided'],401);
        }
        JWTAuth::invalidate($token);
        return response()->json(['message'=> 'Logged out successfully'],200);
            
        

    }
} 