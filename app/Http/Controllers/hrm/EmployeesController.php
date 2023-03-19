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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class EmployeesController extends Controller
{

    //------------ GET ALL employees -----------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Employee::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
           
        );
        $columns = array(
            0 => 'username',
            1 => 'employment_type',
            2 => 'company_id',
        );
        $data = array();

        $employees = Employee::with('company:id,name','office_shift:id,name','department:id,department','designation:id,designation')
            ->where('deleted_at', '=', null)
            ->where('leaving_date' , NULL);

         //Multiple Filter
        $Filtred = $helpers->filter($employees, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('firstname', 'LIKE', "%{$request->search}%")
                        ->orWhere('lastname', 'LIKE', "%{$request->search}%")
                        ->orWhere('username', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $employees->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $employees = $employees->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($employees as $employee) {

            $item['id'] = $employee->id;
            $item['firstname'] = $employee->firstname;
            $item['lastname'] = $employee->lastname;
            $item['phone'] = $employee->phone;
            $item['company_name'] = $employee['company']->name;
            $item['department_name'] = $employee['department']->department;
            $item['designation_name'] = $employee['designation']->designation;
            $item['office_shift_name'] = $employee['office_shift']->name;
          
            $data[] = $item;
        }

        $companies = Company::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'employees' => $data,
            'companies' => $companies,
            'totalRows' => $totalRows,
        ]);

    }

      //---------------- Show Form Create Employee ---------------\\

      public function create(Request $request)
      {
  
          $this->authorizeForUser($request->user('api'), 'create', Employee::class);
  
          $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
  
          return response()->json([
              'companies' => $companies,
          ]);
      }


  //----------------Store  Employee ---------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Employee::class);

            $this->validate($request, [
                'firstname'      => 'required|string',
                'lastname'       => 'required|string',
                'gender'         => 'required',
                'company_id'     => 'required',
                'department_id'  => 'required',
                'designation_id' => 'required',
                'office_shift_id'=> 'required',
            ]);
          
            $data = [];
            $data['firstname'] = $request['firstname'];
            $data['lastname'] = $request['lastname'];
            $data['username'] = $request['firstname'] .' '.$request['lastname'];
            $data['country'] = $request['country'];
            $data['email'] = $request['email'];
            $data['gender'] = $request['gender'];
            $data['phone'] = $request['phone'];
            $data['birth_date'] = $request['birth_date'];
            $data['company_id'] = $request['company_id'];
            $data['department_id'] = $request['department_id'];
            $data['designation_id'] = $request['designation_id'];
            $data['office_shift_id'] = $request['office_shift_id'];
            $data['joining_date'] = $request['joining_date'];
            
            Employee::create($data);
            
            return response()->json(['success' => true]);
    }

   
     //------------ function show -----------\\

     public function show(Request $request, $id)
     {

        $this->authorizeForUser($request->user('api'), 'view', Employee::class);

        $employee = Employee::where('deleted_at', '=', null)->findOrFail($id);
        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        $office_shifts = OfficeShift::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        $departments = Department::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','department']);
        $designations = Designation::where('department_id' , $employee->department_id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','designation']);

        return response()->json([
            'employee' => $employee,
            'companies' => $companies,
            'office_shifts' => $office_shifts,
            'departments' => $departments,
            'designations' => $designations,
        ]);   
    
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Employee::class);

        $employee = Employee::where('deleted_at', '=', null)->findOrFail($id);
        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        $office_shifts = OfficeShift::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->get(['id','name']);
        $departments = Department::where('company_id' , $employee->company_id)->where('deleted_at', '=', null)->get(['id','department']);
        $designations = Designation::where('department_id' , $employee->department_id)->where('deleted_at', '=', null)->get(['id','designation']);
        
        return response()->json([
            'employee' => $employee,
            'companies' => $companies,
            'office_shifts' => $office_shifts,
            'departments' => $departments,
            'designations' => $designations,
        ]);     
    }

     //---------------- UPDATE Employee -------------\\

     public function update(Request $request, $id)
     {
 
         $this->authorizeForUser($request->user('api'), 'update', Employee::class);
 
         $this->validate($request, [
            'firstname'      => 'required|string',
            'lastname'       => 'required|string',
            'country'        => 'required|string',
            'gender'         => 'required',
            'phone'          => 'required',
            'total_leave'    => 'required|numeric|min:0',
            'company_id'     => 'required',
            'department_id'  => 'required',
            'designation_id' => 'required',
            'office_shift_id'=> 'required',
            'basic_salary'   => 'nullable|numeric',
            'hourly_rate'     => 'nullable|numeric',
        ]);

       
        $data = [];
        $data['firstname'] = $request['firstname'];
        $data['lastname'] = $request['lastname'];
        $data['username'] = $request['firstname'] .' '.$request['lastname'];
        $data['country'] = $request['country'];
        $data['email'] = $request['email'];
        $data['gender'] = $request['gender'];
        $data['phone'] = $request['phone'];
        $data['birth_date'] = $request['birth_date'];
        $data['company_id'] = $request['company_id'];
        $data['department_id'] = $request['department_id'];
        $data['designation_id'] = $request['designation_id'];
        $data['office_shift_id'] = $request['office_shift_id'];
        $data['joining_date'] = $request['joining_date'];
        $data['role_users_id'] = $request['role_users_id'];
        $data['leaving_date'] = $request['leaving_date']?$request['leaving_date']:NULL;
        $data['marital_status'] = $request['marital_status'];
        $data['employment_type'] = $request['employment_type'];
        $data['city'] = $request['city'];
        $data['province'] = $request['province'];
        $data['zipcode'] = $request['zipcode'];
        $data['address'] = $request['address'];
        $data['basic_salary'] = $request['basic_salary'];
        $data['hourly_rate'] = $request['hourly_rate'];

        //calculation of total_leave & remaining_leave
        $employee_leave_info = Employee::find($id);
        if($employee_leave_info->total_leave == 0)
        {
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->total_leave;
        }
        elseif($request->total_leave > $employee_leave_info->total_leave ){
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->remaining_leave + ($request->total_leave - $employee_leave_info->total_leave);
        }
         elseif($request->total_leave < $employee_leave_info->total_leave ){
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $request->remaining_leave - ($employee_leave_info->total_leave - $request->total_leave);

        }else{
            $data['total_leave'] = $request->total_leave;
            $data['remaining_leave'] = $employee_leave_info->remaining_leave;
        }
        
        Employee::find($id)->update($data);

         return response()->json(['success' => true]);
     }

    //------------ Delete Employee -----------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Employee::class);

        Employee::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Employee::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $employee_id) {
            Employee::whereId($employee_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return response()->json(['success' => true]);

    }


    public function Get_employees_by_department(Request $request)
    {
        $employees = Employee::where('department_id' , $request->id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','username']);

        return response()->json($employees);
    }


    public function Get_office_shift_by_company(Request $request)
    {
        $office_shifts = OfficeShift::where('company_id' , $request->id)->where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);

        return response()->json($office_shifts);
    }



    //details employee


    public function update_social_profile(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Employee::class);

        $data = [];
        $data['skype'] = $request['skype'];
        $data['facebook'] = $request['facebook'];
        $data['whatsapp'] = $request['whatsapp'];
        $data['twitter'] = $request['twitter'];
        $data['linkedin'] = $request['linkedin'];

        Employee::whereId($id)->update($data);

        return response()->json(['success' => true]);
    }



       //-------------------- get_experiences_by_employee -------------\\

       public function get_experiences_by_employee(request $request)
       {
   
           $this->authorizeForUser($request->user('api'), 'view', Employee::class);
           // How many items do you want to display.
           $perPage = $request->limit;
           $pageStart = \Request::get('page', 1);
           // Start displaying items from this number;
           $offSet = ($pageStart * $perPage) - $perPage;
   
            $experiences = EmployeeExperience::where('employee_id' , $request->id)
            ->where('deleted_at', '=', null)
            ->orderBy('id', 'desc');

   
           $totalRows = $experiences->count();
           if($perPage == "-1"){
               $perPage = $totalRows;
           }
           $experiences = $experiences->offset($offSet)
               ->limit($perPage)
               ->orderBy('id', 'desc')
               ->get();
   
          
           return response()->json([
               'totalRows' => $totalRows,
               'experiences' => $experiences,
           ]);
   
       }

         //-------------------- get_accounts_by_employee -------------\\

         public function get_accounts_by_employee(request $request)
         {
     
             $this->authorizeForUser($request->user('api'), 'view', Employee::class);
             // How many items do you want to display.
             $perPage = $request->limit;
             $pageStart = \Request::get('page', 1);
             // Start displaying items from this number;
             $offSet = ($pageStart * $perPage) - $perPage;
     
              $accounts_bank = EmployeeAccount::where('employee_id' , $request->id)
              ->where('deleted_at', '=', null)
              ->orderBy('id', 'desc');
  
     
             $totalRows = $accounts_bank->count();
             if($perPage == "-1"){
                 $perPage = $totalRows;
             }
             $accounts_bank = $accounts_bank->offset($offSet)
                 ->limit($perPage)
                 ->orderBy('id', 'desc')
                 ->get();
     
            
             return response()->json([
                 'totalRows' => $totalRows,
                 'accounts_bank' => $accounts_bank,
             ]);
     
         }


}
