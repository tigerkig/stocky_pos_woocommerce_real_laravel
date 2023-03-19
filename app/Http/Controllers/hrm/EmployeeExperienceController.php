<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\EmployeeExperience;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeExperienceController extends Controller
{


    public function index(Request $request)
    {
        //
    }

    //----------- Store new Employee Experience --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Employee::class);

        request()->validate([
            'title'           => 'required|string',
            'company_name'    => 'required|string',
            'start_date'      => 'required',
            'end_date'        => 'required',
            'employment_type' => 'required',
        ]);

        EmployeeExperience::create([
            'company_name'    => $request['company_name'],
            'employee_id'     => $request['employee_id'],
            'title'           => $request['title'],
            'start_date'      => $request['start_date'],
            'end_date'        => $request['end_date'],
            'employment_type' => $request['employment_type'],
            'location'        => $request['location'],
            'description'     => $request['description'],
        ]);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //-----------Update Employee Experience --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Employee::class);

        request()->validate([
            'title'           => 'required|string',
            'company_name'    => 'required|string',
            'start_date'      => 'required',
            'end_date'        => 'required',
            'employment_type'=> 'required',
        ]);

        EmployeeExperience::whereId($id)->update([
            'company_name'    => $request['company_name'],
            'employee_id'     => $request['employee_id'],
            'title'           => $request['title'],
            'start_date'      => $request['start_date'],
            'end_date'        => $request['end_date'],
            'employment_type' => $request['employment_type'],
            'location'        => $request['location'],
            'description'     => $request['description'],
        ]);
    
        return response()->json(['success' => true]);
    }

    //----------- Delete  Employee --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Employee::class);

        EmployeeExperience::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }



}
