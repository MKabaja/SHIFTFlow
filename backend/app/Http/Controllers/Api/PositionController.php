<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Http\Resources\ShiftResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PositionController extends Controller
{
    public function index(): JsonResponse
    {
        $positionsQuery = Position::with('creator')->paginate(20);

        return PositionResource::collection($positionsQuery)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @param  StorePositionRequest  $request  custom request
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['created_by'] = $request->user()->id;

        $position = Position::create($data);

        return PositionResource::make($position)
            ->additional(['message' => 'Position created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position): JsonResponse
    {
        $position->load('creator');

        return PositionResource::make($position)
            ->response();

    }

    /**
     * Display the shifts for the specified position.
     */
    public function shifts(Position $position): JsonResponse
    {
        return ShiftResource::collection($position->shifts()->with(['user', 'position'])
            ->paginate(20))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $data = $request->validated();
        $position->update($data);

        return PositionResource::make($position)
            ->additional(['message' => 'Position updated successfully'])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): JsonResponse
    {
        if ($position->shifts()->exists()) {
            Log::info('Delete blocked: Position ID '.$position->id.' is linked to active schedule.');

            return response()->json([
                'message' => 'Cannot delete position with linked shifts.',
            ], 409); // 409-> conflict
        }
        $position->delete();

        return response()->json([
            'message' => 'Position deleted successfully',
        ], 200);
    }
}
