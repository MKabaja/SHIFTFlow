<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetSchedulesRequest;
use App\Http\Requests\StoreBatchShiftsRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleListResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\ShiftResource;
use App\Models\Schedule;
use App\Models\Shift;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {}

    public function index(GetSchedulesRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->validated('per_page') ?? 20;

        $schedules = Schedule::with(['creator', 'shifts'])
            ->when($request
                ->input('month'), fn ($q, $m) => $q
                ->where('month', $m))

            ->when($request
                ->input('year'), fn ($q, $y) => $q
                ->where('year', $y))

            ->when($request
                ->validated('search'), fn ($q, $s) => $q
                ->where('name', 'like', '%'.$s.'%'))

            ->paginate($perPage);

        return ScheduleListResource::collection($schedules);

    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = $this->scheduleService->create($request->validated());

        return ScheduleResource::make($schedule)
            ->additional(['message' => 'Schedule created successfully'])
            ->response()
            ->setStatusCode(201);

    }

    public function show(Schedule $schedule): ScheduleListResource
    {
        $schedule->load(['creator', 'shifts']);

        return ScheduleListResource::make($schedule);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $scheduleData = $request->validated();

        $schedule->update($scheduleData);

        return ScheduleResource::make($schedule)
            ->additional(['message' => 'Schedule updated successfully'])
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted Successfully',
        ], 200);
    }

    public function addShiftsBatch(StoreBatchShiftsRequest $request, Schedule $schedule): JsonResponse
    {
        $batchResult = $this->scheduleService
            ->addShiftsBatch($schedule, $request->validated()['shifts']);

        if ($batchResult->hasErrors()) {
            return response()->json([
                'message' => 'Batch validation failed',
                'errors' => $batchResult->errors(),
            ], 422);
        }

        $shiftIds = $batchResult->shifts()->pluck('id');
        $shifts = Shift::with(['user', 'position'])
            ->whereIn('id', $shiftIds)
            ->get();

        return response()->json([
            'message' => 'Batch created successfully',
            'count' => $batchResult->count(),
            'shifts' => ShiftResource::collection($shifts),
        ], 201);
    }

    public function publish(Schedule $schedule): JsonResponse
    {
        if ($schedule->status === 'published') {
            return response()->json([
                'message' => 'Schedule is already published',
            ], 409);
        }

        $schedule->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json(ScheduleResource::make($schedule));
    }
}
