<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeLocaleRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePinRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('positions');

        return UserResource::make($user)->response();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $newPassword = $request->validated('new_password');

        $user->update(['password' => $newPassword]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function changePin(ChangePinRequest $request): JsonResponse
    {
        $user = $request->user();
        $newPin = $request->validated('new_pin');

        $user->update(['pin_hashed' => $newPin]);

        return response()->json(['message' => 'PIN changed successfully.']);
    }

    public function changeLocale(ChangeLocaleRequest $request): JsonResponse
    {
        $user = $request->user();
        $locale = $request->validated('locale');

        $user->update(['locale' => $locale]);

        return response()->json(['message' => 'Locale changed successfully.']);
    }
}
