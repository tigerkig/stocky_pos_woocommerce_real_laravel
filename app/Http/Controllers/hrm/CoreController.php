<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use App\Models\Employee;
use App\Models\Company;
use App\Models\Designation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeDocument;
use App\Models\EmployeeAccount;
use App\Models\Department;
use App\Models\OfficeShift;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Award;
use App\Models\Travel;
use App\Models\Complaint;
use App\Models\Project;
use App\Models\Task;
use App\Models\Training;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class CoreController extends Controller
{

    public function Get_designations_by_department(Request $request)
    {
        $designations = Designation::where('department_id' , $request->id)->where('deleted_at', '=', null)->get();

        return response()->json($designations);
    }

    public function Get_departments_by_company(Request $request)
    {
        $departments = Department::where('company_id' , $request->id)->where('deleted_at', '=', null)->get();

        return response()->json($departments);
    }

    public function Get_office_shift_by_company(Request $request)
    {
        $office_shifts = OfficeShift::where('company_id' , $request->id)->where('deleted_at', '=', null)->get(['id','name']);

        return response()->json($office_shifts);
    }

    public function Get_employees_by_company(Request $request)
    {
        $employees = Employee::where('company_id' , $request->id)->where('deleted_at', '=', null)->get(['id','username']);

        return response()->json($employees);
    }

}
