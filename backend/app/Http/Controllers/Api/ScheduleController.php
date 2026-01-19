<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetSchedulesRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Resources\ScheduleListResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(GetSchedulesRequest $request)
    {

        $schedules = Schedule::with('creator')
            ->when($request
                ->input('month'), fn ($q, $m) => $q
                ->where('month', $m))

            ->when($request
                ->input('year'), fn ($q, $y) => $q
                ->where('year', $y))

            ->paginate(20);

        return ScheduleListResource::collection($schedules);

    }

    public function store(StoreScheduleRequest $request)
    {
        $scheduleData = $request->validated();

        $schedule = Schedule::create([
            'name' => $scheduleData['name'],
            'month' => $scheduleData['month'],
            'year' => $scheduleData['year'],
            'description' => $scheduleData['description'] ?? null,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return (new ScheduleResource($schedule))
            ->additional(['message' => 'Schedule created successfully'])
            ->response()
            ->setStatusCode(201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
