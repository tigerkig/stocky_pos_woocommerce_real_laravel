<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use Carbon\Carbon;
use DateTime;

class LeaveController extends Controller
{

    //----------- GET ALL Leaves --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Leave::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        $leaves = Leave::
        join('companies','companies.id','=','leaves.company_id')
        ->join('departments','departments.id','=','leaves.department_id')
        ->join('employees','employees.id','=','leaves.employee_id')
        ->join('leave_types','leave_types.id','=','leaves.leave_type_id')
        ->where('leaves.deleted_at' , '=', null)
        ->select('leaves.*',
        'employees.username AS employee_name', 'employees.id AS employee_id',
        'leave_types.title AS leave_type_title', 'leave_types.id AS leave_type_id',
        'companies.name AS company_name', 'companies.id AS company_id',
        'departments.department AS department_name', 'departments.id AS department_id')

       // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('employees.username', 'LIKE', "%{$request->search}%")
                            ->orWhere('leave_types.title', 'LIKE', "%{$request->search}%")
                            ->orWhere('companies.name', 'LIKE', "%{$request->search}%")
                            ->orWhere('departments.department', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $leaves->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $leaves = $leaves->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'leaves' => $leaves,
            'totalRows' => $totalRows,
        ]);
    }


    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Leave::class);

        $leave_types = LeaveType::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();
        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);

        return response()->json([
            'companies'   => $companies,
            'leave_types' => $leave_types,
        ]);

    }


    //----------- Store new Leave --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Leave::class);

        request()->validate([
            'employee_id'      => 'required',
            'company_id'      => 'required',
            'department_id'      => 'required',
            'leave_type_id'    => 'required',
            'start_date'       => 'required',
            'end_date'         => 'required|after_or_equal:start_date',
            'status'           => 'required',
            'attachment'      => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
        ]);

        if ($request->hasFile('attachment')) {


            $image = $request->file('attachment');
            $filename = time().'.'.$image->extension();  
            $image->move(public_path('/images/leaves'), $filename);

        } else {
            $filename = 'no_image.png';
        }

        $start_date = new DateTime($request->start_date);
        $end_date = new DateTime($request->end_date);
        $day     = $start_date->diff($end_date);
        $days_diff    = $day->d +1;
        $leave_type = LeaveType::findOrFail($request['leave_type_id']);

        $leave_data= [];
        $leave_data['employee_id'] = $request['employee_id'];
        $leave_data['company_id'] = $request['company_id'];
        $leave_data['department_id'] = $request['department_id'];
        $leave_data['leave_type_id'] = $request['leave_type_id'];
        $leave_data['start_date'] = $request['start_date'];
        $leave_data['end_date'] = $request['end_date'];
        $leave_data['days'] = $days_diff;
        $leave_data['reason'] = $request['reason'];
        $leave_data['attachment'] = $filename;
        $leave_data['half_day'] = $request['half_day'];
        $leave_data['status'] = $request['status'];

        $employee_leave_info = Employee::find($request->employee_id);
        if($days_diff > $employee_leave_info->remaining_leave)
        {
            return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
        }
        elseif($request->status == 'approved'){
            $employee_leave_info->remaining_leave = $employee_leave_info->remaining_leave - $days_diff;
            $employee_leave_info->update();
        }

        Leave::create($leave_data);

        return response()->json(['success' => true ,'isvalid' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }


    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Leave::class);

        $leave = Leave::where('deleted_at', '=', null)->findOrFail($id);
        $leave_types = LeaveType::where('deleted_at', '=', null)->orderBy('id', 'desc')->get();
        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);

        return response()->json([
            'leave'       => $leave,
            'companies'   => $companies,
            'leave_types' => $leave_types,
        ]);

    }


    //-----------Update Leave --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Leave::class);

        request()->validate([
            'company_id'      => 'required',
            'department_id'      => 'required',
            'employee_id'      => 'required',
            'leave_type_id'    => 'required',
            'start_date'       => 'required',
            'end_date'         => 'required',
            'status'           => 'required',
            'attachment'      => 'nullable|image|mimes:jpeg,png,jpg,bmp,gif,svg|max:2048',
        ]);

        $leave = Leave::findOrFail($id);
        $CurrentAttachement = $leave->attachment;
        if ($request->attachment != null) {
            if ($request->attachment != $CurrentAttachement) {

                $image = $request->file('attachment');
                $filename = time().'.'.$image->extension();  
                $image->move(public_path('/images/leaves'), $filename);
                $path = public_path() . '/images/leaves';
                $LeavePhoto = $path . '/' . $CurrentAttachement;
                if (file_exists($LeavePhoto)) {
                    if ($leave->attachment != 'no_image.png') {
                        @unlink($LeavePhoto);
                    }
                }
            } else {
                $filename = $CurrentAttachement;
            }
        }else{
            $filename = $CurrentAttachement;
        }

        $start_date = new DateTime($request->start_date);
        $end_date = new DateTime($request->end_date);
        $day     = $start_date->diff($end_date);
        $days_diff    = $day->d +1;
        $leave_type = LeaveType::findOrFail($request['leave_type_id']);

        $leave_data= [];
        $leave_data['employee_id'] = $request['employee_id'];
        $leave_data['company_id'] = $request['company_id'];
        $leave_data['department_id'] = $request['department_id'];
        $leave_data['leave_type_id'] = $request['leave_type_id'];
        $leave_data['start_date'] = $request['start_date'];
        $leave_data['end_date'] = $request['end_date'];
        $leave_data['days'] = $days_diff;
        $leave_data['reason'] = $request['reason'];
        $leave_data['attachment'] = $filename;
        $leave_data['half_day'] = $request['half_day'];
        $leave_data['status'] = $request['status'];


        // return the old remaining_leave
        if($leave->status == 'approved'){
           
            $employee_leave_info = Employee::find($request->employee_id);
            if($days_diff > ($employee_leave_info->remaining_leave + $leave->days))
            {
                return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
            }else{
                $employee_leave_info->remaining_leave = $employee_leave_info->remaining_leave + $leave->days;
                $employee_leave_info->update();
            }

        }


        if($leave->status != 'approved'){
            $employee_leave_info = Employee::find($request->employee_id);
            if($days_diff > $employee_leave_info->remaining_leave)
            {
                return response()->json(['remaining_leave' => "remaining leaves are insufficient", 'isvalid' => false]);
            }
        }
        
        if($request->status == 'approved'){
            $employee_leave_info = Employee::find($request->employee_id);
            $employee_leave_info->remaining_leave = $employee_leave_info->remaining_leave - $days_diff;
            $employee_leave_info->update();
        }

    
        Leave::find($id)->update($leave_data);

        return response()->json(['success' => true ,'isvalid' => true]);
    }




    //----------- Delete  Leave --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Leave::class);

        $leave = Leave::findOrFail($id);
        $leave->deleted_at = Carbon::now();
        $leave->save();

        $attachment = $leave->attachment;

        $path = public_path() . '/images/leaves';
        $LeavePhoto = $path . '/' . $attachment;
        if (file_exists($LeavePhoto)) {
            if ($leave->attachment != 'no_image.png') {
                @unlink($LeavePhoto);
            }
        }
      
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Leave::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $leave_id) {

            $leave = Leave::findOrFail($leave_id);
            $leave->deleted_at = Carbon::now();
            $leave->save();

            $attachment = $leave->attachment;

            $path = public_path() . '/images/leaves';
            $LeavePhoto = $path . '/' . $attachment;
            if (file_exists($LeavePhoto)) {
                if ($leave->attachment != 'no_image.png') {
                    @unlink($LeavePhoto);
                }
            }
        }

        return response()->json(['success' => true]);
    }


}
