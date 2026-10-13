<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAvailabilityRequest;
use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    public function index(IndexAvailabilityRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Availability::class);

        $user = $request->user();
        $requestedUserId = $request->validated('user_id');

        if ($requestedUserId && $user->role === 'employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $monthStart = Carbon::create($request->integer('year'), $request->integer('month'))->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $availabilities = Availability::query()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])

            ->when($user
                ->role === 'employee', fn ($q) => $q
                ->where('user_id', $user->id))

            ->when(
                $requestedUserId
                && in_array($user->role, ['admin', 'manager']),
                fn ($q) => $q->where('user_id', $requestedUserId)
            )
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        return AvailabilityResource::collection($availabilities)
            ->response();

    }

    public function store(StoreAvailabilityRequest $request): JsonResponse
    {
        $this->authorize('create', Availability::class);

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
            ]
        );
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

        return AvailabilityResource::make($availability)
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
