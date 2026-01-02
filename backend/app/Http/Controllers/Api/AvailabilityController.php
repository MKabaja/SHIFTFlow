<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->query('user_id') && $user->role === 'employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $availabilities = Availability::query()

            ->when($user->role === 'employee', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($request->query('user_id') && in_array($user->role, ['admin', 'manager']), function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->get();

        return AvailabilityResource::collection($availabilities);

    }

    public function store(StoreAvailabilityRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $userId = $user->role === 'employee'
             ? $user->id
             : $validated['user_id'];

        $availability = Availability::updateOrCreate(
            [

                'user_id' => $userId,
                'date' => $validated['date'],
            ],

            [
                'is_available' => $validated['is_available'],
                'notes' => $validated['notes'] ?? null,
            ]);
        if ($availability->wasRecentlyCreated) {

            $availability->submission_date = now();
            $availability->save();
        }

        $statusCode = $availability->wasRecentlyCreated
            ? 201
            : 200;

        $message = $availability->wasRecentlyCreated
            ? 'Availability created successfully'
            : 'Availability updated successfully';

        return (new AvailabilityResource($availability))
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode($statusCode);

    }

    public function destroy(Availability $availability): JsonResponse
    {
        $this->authorize('delete', $availability);
        $availability->delete();

        return response()->json([
            'message' => 'Availability deleted successfully',
        ], 200);
    }
}
