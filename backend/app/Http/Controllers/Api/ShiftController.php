<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\ShiftValidationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Helpers\TimeHelper;
use App\Services\Validation\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function __construct(protected ValidationService $validationService) {}

    public function index(IndexShiftRequest $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->validated('per_page') ?? 50;

        $shifts = Shift::query()
            ->with(['user', 'position', 'schedule'])

            ->when($request
                ->user_id, fn ($q, $userId) => $q
                ->where('user_id', $userId))

            ->when($request
                ->date, fn ($q, $date) => $q
                ->where('date', $date))

            ->dateRange($request->from, $request->to)
            ->when($user
                ->role === 'employee', fn ($q) => $q
                ->whereHas('schedule', fn ($sq) => $sq
                    ->where('status', 'published')))

            ->orderBy('date')
            ->paginate($perPage);

        return ShiftResource::collection($shifts)
            ->response();

    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['user', 'position', 'schedule']);

        return ShiftResource::make($shift)
            ->response();
    }

    public function store(): JsonResponse
    {
        abort(410, 'Use POST /api/schedules/{id}/shifts/batch instead');
    }

    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shiftData = $request->validated();

        $user = User::with('positions')
            ->findOrFail($shiftData['user_id']);

        $dto = new ShiftValidationData(
            userId: $shiftData['user_id'],
            date: $shiftData['date'],
            shiftStart: $shiftData['shift_start'],
            shiftEnd: $shiftData['shift_end'],
            isUserActive: $user->is_active,
            positionId: $shiftData['position_id'],
            allowedPositionIds: $user->positions->pluck('id')->toArray(),
            maxMinutesPerMonth: $user->max_minutes_per_month,
            minBreakMinutes: $user->min_break_minutes,
            maxMinutesPerQuarter: $user->max_minutes_per_quarter,
            ignoreShiftId: $shift->id
        );
        $this->validationService->validate($dto);

        $minutesWorked = TimeHelper::calculateMinutesDifference(
            "{$dto->date} {$dto->shiftStart}",
            "{$dto->date} {$dto->shiftEnd}"
        );

        $shift->update([
            'user_id' => $shiftData['user_id'],
            'position_id' => $shiftData['position_id'],
            'date' => $shiftData['date'],
            'shift_start' => $shiftData['shift_start'],
            'shift_end' => $shiftData['shift_end'],
            'minutes_worked' => $minutesWorked,
            'schedule_id' => $shiftData['schedule_id'] ?? $shift->schedule_id,
            'status' => $shiftData['status'] ?? $shift->status,
            'notes' => $shiftData['notes'] ?? $shift->notes,
        ]);

        $shift->refresh()->load(['user', 'position', 'schedule']);

        return ShiftResource::make($shift)
            ->additional(['message' => 'Shift updated successfully'])
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json([
            'message' => 'Shift deleted successfully',
        ], 200);
    }
}
