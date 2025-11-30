<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;


class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query= Schedule::with(['user','position']);
        
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);

        } elseif ($request->has('user_id')) {
            
                $query->where('user_id', $request ->user_id);
            }

        
        if ($request ->has('date')) {
            $query->whereDate('date', $request->date);
        }
         

        return response()->json(
            $query->orderBy('date','desc')->get()
        );
}

    public function store(Request $request)
    {
        //
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
