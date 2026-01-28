<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PositionController extends Controller
{
    public function index(): JsonResponse
    {
        $positionsQuery = Position::with('creator')->get();

        return response()->json($positionsQuery);
    }

    /**
     * Summary of store
     *
     * @param  StorePositionRequest  $request  custom request
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['created_by'] = $request->user()->id;

        $position = Position::create($data);

        return response()->json($position, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        return $position->load(['creator', 'shifts']);
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
            'position' => $position,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): JsonResponse
    {
        if ($position->shifts()->exists()) {
            Log::info('Delete blocked:Position ID '.$position->id.' is linked to active schedule.');

            return response()->json([
                'error' => 'INTEGRITY_VIOLATION: Cannot delete position, it is currently linked to one or more schedules.',
            ], 409);
        }
        $position->delete();

        return response()->json([
            'message' => 'Position deleted Successfully',
        ]);
    }
}
