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
        
        $userIsEmployee = $user->role === 'employee';

        $requestUserId = $request->input('user_id');
        $requestDate = $request->input('date');

        
        if($userIsEmployee) {
         $this->applyUserFilter($query,$user->id);
        }    

        elseif($requestUserId) {
            $this->applyUserFilter($query, $requestUserId);
        }

        

        $query->when($requestDate,fn($q)=>
            $q->whereDate('date',$requestDate));

        return response()->json(
                $query->latest('date')->get()
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
    private function applyUserFilter($query, $id)
    {
        return $query->where('user_id', $id);
    }
}
