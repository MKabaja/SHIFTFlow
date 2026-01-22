<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetSchedulesRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleListResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    public function __construct(private ScheduleService $scheduleService) {}

    public function index(GetSchedulesRequest $request): AnonymousResourceCollection
    {

        $schedules = Schedule::with(['creator', 'shifts'])
            ->when($request
                ->input('month'), fn ($q, $m) => $q
                ->where('month', $m))

            ->when($request
                ->input('year'), fn ($q, $y) => $q
                ->where('year', $y))

            ->paginate(20);

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
}
