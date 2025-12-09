<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Services\ValidationService;

use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Summary of __construct
     * @param ValidationService $service My validation service
     */
    public function __construct(protected ValidationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Schedule::with(['user', 'position']);

        $userIsEmployee = $user->role === 'employee';

        $requestUserId = $request->input('user_id');
        $requestDate = $request->input('date');


        if ($userIsEmployee) {
            $this->applyUserFilter($query, $user->id);
        } elseif ($requestUserId) {
            $this->applyUserFilter($query, $requestUserId);
        }



        $query->when($requestDate, fn($q) =>
        $q->whereDate('date', $requestDate));

        return response()->json(
            $query->latest('date')->get()
        );
    }







    /**
     * Summary of store
     * @param StoreScheduleRequest $request My custom request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = null;

        $uId = $data['user_id'];
        $start = $data['shift_start'];
        $end = $data['shift_end'];
        $p = $data['position_id'];
        $date = $data['date'];

        $user = User::findOrFail($uId);

        $this->service->validateScheduleCreation(
            $user,
            $p,
            $date,
            $start,
            $end,
        );

        $result = $this->calculateMinutes($date, $start, $end);

        $schedule = Schedule::create([
            ...$data,
            'hours_worked' => $result ?? null,
            'status' => 'scheduled'
        ]);

        return response()->json([


            'message' => 'Schedule created Successfully',
            'schedule' => $schedule
        ], 201);
    }



    public function show(Schedule $schedule): Schedule
    {
        return $schedule->load(['user', 'position']);
    }

    /**
     * Summary of update
     * @param UpdateScheduleRequest $request my custom update request
     * @param Schedule $schedule schedule that was created
     * @return JsonResponse
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $data = $request->validated();
        $result = null;

        $oldDate = $schedule->date;

        //make sure right format to avoid Carbon issues
        if ($oldDate instanceof \Carbon\Carbon) {
            $oldDate = $oldDate->format('Y-m-d');
        }

        $dataWithFallbacks =
            [
                'user_id' => $data['user_id'] ?? $schedule->user_id,
                'shift_start' => $data['shift_start'] ?? $schedule->shift_start,
                'shift_end' => $data['shift_end'] ?? $schedule->shift_end,
                'position_id' => $data['position_id'] ?? $schedule->position_id,
                'date' => $data['date'] ?? $oldDate,
                'status' => $data['status'] ?? $schedule->status,
            ];



        $targetUser = $schedule->user;


        $this->service->validateScheduleCreation(
            $targetUser,
            $dataWithFallbacks['position_id'],
            $dataWithFallbacks['date'],
            $dataWithFallbacks['shift_start'],
            $dataWithFallbacks['shift_end'],
            $schedule->id,
        );

        $result = $this->calculateMinutes($dataWithFallbacks['date'], $dataWithFallbacks['shift_start'], $dataWithFallbacks['shift_end']);

        $schedule->update([
            ...$dataWithFallbacks,
            'hours_worked' => $result,

        ]);
        return response()->json([


            'message' => 'Schedule updated Successfully',
            'schedule' => $schedule
        ], 200);
    }


    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json([
            'message' => 'Schedule deleted Successfully',
        ]);
    }
    private function applyUserFilter($query, $id)
    {
        return $query->where('user_id', $id);
    }




    /**
     * Summary of calculateMinutes
     * @param string $date date form data
     * @param string $start shift start
     * @param string $end shift end
     * @return int
     */
    private function calculateMinutes(string $date, string $start, string $end): int
    {
        $startTime = $this->service->getFullDateTime($date, $start);
        $endTime = $this->service->getFullDateTime($date, $end);


        if ($endTime->lessThan($startTime)) {
            $endTime->addDay();
        }
        return $startTime->diffInMinutes($endTime);
    }
}
