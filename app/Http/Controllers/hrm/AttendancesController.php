<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Role;
use Carbon\Carbon;
use DateTime;
use Exception;
use DB;
use Illuminate\Support\Facades\Auth;
use App\utils\helpers;

class AttendancesController extends Controller
{

    //----------- GET ALL  Attendance --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Attendance::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();
        $attendances = Attendance::with('employee','company')
        ->where('deleted_at', '=', null)
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('user_id', '=', Auth::user()->id);
            }
        })
         // Search With Multiple Param
         ->where(function ($query) use ($request) {
            return $query->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('date', 'LIKE', "%{$request->search}%")
                    ->orWhere(function ($query) use ($request) {
                        return $query->whereHas('employee', function ($q) use ($request) {
                            $q->where('username', 'LIKE', "%{$request->search}%");
                        });
                    })
                    ->orWhere(function ($query) use ($request) {
                        return $query->whereHas('company', function ($q) use ($request) {
                            $q->where('name', 'LIKE', "%{$request->search}%");
                        });
                    });
            });
        });
        $totalRows = $attendances->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $attendances = $attendances->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($attendances as $attendance) {

            $item['id'] = $attendance->id;
            $item['date'] = $attendance->date;
            $item['clock_in'] = $attendance->clock_in;
            $item['clock_out'] = $attendance->clock_out;
            $item['total_work'] = $attendance->total_work;
            $item['company_id'] = $attendance['company']->id;
            $item['employee_id'] = $attendance['employee']->id;
            $item['company_name'] = $attendance['company']->name;
            $item['employee_username'] = $attendance['employee']->username;
            
            $data[] = $item;
        }


        return response()->json([
            'attendances' => $data,
            'totalRows'   => $totalRows,
        ]);
    }



    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Attendance::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //----------- Store new Attendance --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Attendance::class);

        $this->validate($request, [
            'company_id'     => 'required',
            'employee_id'    => 'required',
            'date'           => 'required',
            'clock_in'       => 'required',
            'clock_out'      => 'required',
        ]);

        $user_auth = auth()->user();
        $data['user_id'] = $user_auth->id;

        $employee_id  = $request->employee_id;
        $date  = $request->date;
        $company_id  = $request->company_id;
        $clock_in  = $request->clock_in;
        $clock_out  = $request->clock_out;

        try{
            $clock_in  = new DateTime($clock_in);
            $clock_out  = new DateTime($clock_out);
        }catch(Exception $e){
            return $e;
        }

        
        $employee = Employee::with('office_shift')->findOrFail($employee_id);
        
        $day_now = Carbon::parse($request->date)->format('l');
        $day_in_now = strtolower($day_now) . '_in';
        $day_out_now = strtolower($day_now) . '_out';

        $shift_in = $employee->office_shift->$day_in_now;
        $shift_out = $employee->office_shift->$day_out_now;
        if($shift_in == null){
            $data['employee_id'] = $employee_id;
            $data['company_id'] = $company_id;
            $data['date'] = $date;
            $data['clock_in'] = $clock_in->format('H:i');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['status'] = 'present';

            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['depart_early'] = '00:00';
            $data['late_time'] = '00:00';
            $data['overtime'] = '00:00';
            $data['clock_in_out'] = 0;
        }

            try{
                $shift_in  = new DateTime(substr($shift_in, 0, -2));
                $shift_out  = new DateTime(substr($shift_out, 0, -2));
            }catch(Exception $e){
                return $e;
            }

            $data['employee_id'] = $employee_id;
            $data['date'] = $date;

            if($clock_in > $shift_in){
                $time_diff = $shift_in->diff($clock_in)->format('%H:%I');
                $data['clock_in'] = $clock_in->format('H:i');
                $data['late_time'] = $time_diff;
            }else{
                $data['clock_in'] = $shift_in->format('H:i');
                $data['late_time'] = '00:00';
            }


            if($clock_out < $shift_out){
                $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['depart_early'] = $time_diff;

            }elseif($clock_out > $shift_out){
                $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['overtime'] = $time_diff;
                $data['depart_early'] = '00:00';
            }else{
                $data['clock_out'] = $shift_out->format('H:i');
                $data['overtime'] = '00:00';
                $data['depart_early'] = '00:00';
            }

            $data['status'] = 'present';
            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['clock_in_out'] = 0;
            $data['company_id'] = $company_id;

            $data['clock_in_ip'] = '';
            $data['clock_out_ip'] = '';


        Attendance::create($data);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Attendance::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //-----------Update Attendance --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Attendance::class);

        $this->validate($request, [
            'company_id'      => 'required',
            'employee_id'      => 'required',
            'date'           => 'required',
            'clock_in'      => 'required',
            'clock_out'      => 'required',
        ]);

        $employee_id  = $request->employee_id;
        $date  = $request->date;
        $clock_in  = $request->clock_in;
        $clock_out  = $request->clock_out;

        try{
            $clock_in  = new DateTime($clock_in);
            $clock_out  = new DateTime($clock_out);
        }catch(Exception $e){
            return $e;
        }

        $day_now = Carbon::parse($request->date)->format('l');
    
        $employee = Employee::with('office_shift')->findOrFail($employee_id);
        
        $day_in_now = strtolower($day_now) . '_in';
        $day_out_now = strtolower($day_now) . '_out';

        $shift_in = $employee->office_shift->$day_in_now;
        $shift_out = $employee->office_shift->$day_out_now;

        if($shift_in ==null){
            $data['employee_id'] = $employee_id;
            $data['date'] = $date;
            $data['clock_in'] = $clock_in->format('H:i');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['status'] = 'present';

            $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
            $data['total_work'] = $work_duration;
            $data['depart_early'] = '00:00';
            $data['late_time'] = '00:00';
            $data['overtime'] = '00:00';
            $data['clock_in_out'] = 0;

            return $data;

        }

        try{
            $shift_in  = new DateTime(substr($shift_in, 0, -2));
            $shift_out  = new DateTime(substr($shift_out, 0, -2));
        }catch(Exception $e){
            return $e;
        }

        $data['employee_id'] = $employee_id;
        $data['date'] = $date;

        if($clock_in > $shift_in){
            $time_diff = $shift_in->diff($clock_in)->format('%H:%I');
            $data['clock_in'] = $clock_in->format('H:i');
            $data['late_time'] = $time_diff;
        }else{
            $data['clock_in'] = $shift_in->format('H:i');
            $data['late_time'] = '00:00';
        }


        if($clock_out < $shift_out){
            $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['depart_early'] = $time_diff;

        }elseif($clock_out > $shift_out){
            $time_diff = $shift_out->diff($clock_out)->format('%H:%I');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['overtime'] = $time_diff;
            $data['depart_early'] = '00:00';
        }else{
            $data['clock_out'] = $shift_out->format('H:i');
            $data['overtime'] = '00:00';
            $data['depart_early'] = '00:00';
        }

        $data['status'] = 'present';
        $work_duration = $clock_in->diff($clock_out)->format('%H:%I');
        $data['total_work'] = $work_duration;
        $data['clock_in_out'] = 0;


        Attendance::find($id)->update($data);

        return response()->json(['success' => true]);
    }

    //----------- Delete  Attendance --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Attendance::class);

            Attendance::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);


        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Attendance::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $attendance_id) {
            Attendance::whereId($attendance_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
    

}
