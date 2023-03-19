<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use DB;

class DepartmentsController extends Controller
{

    //----------- GET ALL  Department --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Department::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        $departments = Department::
            leftjoin('employees','employees.id','=','departments.department_head')
            ->join('companies','companies.id','=','departments.company_id')
            ->where('departments.deleted_at' , '=', null)
            ->select('departments.*','employees.username AS employee_head','companies.name AS company_name')

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('departments.department', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $departments->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $departments = $departments->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();


        return response()->json([
            'departments' => $departments,
            'totalRows'   => $totalRows,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Department::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //----------- Store new department --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Department::class);

        request()->validate([
            'department'   => 'required|string',
            'company_id'   => 'required',
        ]);

        Department::create([
            'department'        => $request['department'],
            'company_id'        => $request['company_id'],
            'department_head'   => $request['department_head']?$request['department_head']:Null,
            ]);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Department::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //-----------Update department --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Department::class);

        request()->validate([
            'department'   => 'required|string',
            'company_id'   => 'required',
        ]);

        Department::whereId($id)->update([
            'department'        => $request['department'],
            'company_id'        => $request['company_id'],
            'department_head'   => $request['department_head']?$request['department_head']:Null,
        ]);

        return response()->json(['success' => true]);
    }

    //----------- Delete  department --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Department::class);

        \DB::transaction(function () use ($id) {

            Department::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Department::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $department_id) {
            Department::whereId($department_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function Get_all_Departments()
    {
        $departments = Department::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','department']);

        return response()->json($departments);
    }

}
