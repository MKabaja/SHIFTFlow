<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{

    public function index(): JsonResponse
    {
        $positionsQuery = Position::with('creator')->get();

        return response()->json($positionsQuery);
    }
    /**
     * Summary of store
     * @param StorePositionRequest $request custom request
     * @return JsonResponse
     */
    public function store(StorePositionRequest $request): JsonResponse
    {   //validated data
        $data = $request->validated();
        //Giving 'created_by' tab value of user id
        $data['created_by'] = $request->user()->id;

        $position = Position::create($data);

        return response()->json($position, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        return $position->load(['creator', 'schedules']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $data = $request->validated();
        $position->update($data);
        return response()->json([


            'message' => 'Position updated Successfully',
            'position' => $position
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): JsonResponse
    {
        if ($position->schedules()->exists()) {
            Log::info('Delete blocked:Position ID ' . $position->id . ' is linked to active schedule.');

            return response()->json([
                'error' => 'INTEGRITY_VIOLATION: Cannot delete position, it is currently linked to one or more schedules.'
            ], 409);
        }
        $position->delete();


        return response()->json([
            'message' => 'Position deleted Successfully',
        ]);
    }
}
