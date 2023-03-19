<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request; 
use App\Models\Designation;
use App\Models\Company;
use App\Models\Department;
use Carbon\Carbon;

class DesignationsController extends Controller
{

    //----------- GET ALL  Designations --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Designation::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();
        $designations = Designation::with('department')->where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('designation', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $designations->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $designations = $designations->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($designations as $designation) {

            $item['id'] = $designation->id;
            $item['designation'] = $designation->designation;
            $item['company_name'] = $designation['company']->name;
            $item['company_id'] = $designation['company']->id;
            $item['department_name'] = $designation['department']->department;
            $item['department_id'] = $designation['department']->id;
            
            $data[] = $item;
        }
    
        return response()->json([
            'designations' => $data,
            'totalRows'   => $totalRows,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Designation::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //----------- Store new designation --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Designation::class);

        request()->validate([
            'designation'   => 'required|string',
            'company_id'    => 'required',
            'department'    => 'required',
        ]);

        Designation::create([
            'designation'   => $request['designation'],
            'company_id'    => $request['company_id'],
            'department_id' => $request['department'],
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
        $this->authorizeForUser($request->user('api'), 'update', Designation::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //-----------Update designation --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Designation::class);

        request()->validate([
            'designation'   => 'required|string',
            'company_id'   => 'required',
            'department'    => 'required',
        ]);

        Designation::whereId($id)->update([
            'designation'   => $request['designation'],
            'company_id'    => $request['company_id'],
            'department_id' => $request['department'],
        ]);

        return response()->json(['success' => true]);
    }

    //----------- Delete  designation --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Designation::class);

        \DB::transaction(function () use ($id) {

            Designation::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Designation::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $designation_id) {
            Designation::whereId($designation_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function Get_designations_by_department(Request $request)
    {
        $designations = Designation::where('department_id' , $request->id)->where('deleted_at', '=', null)->get();

        return response()->json($designations);
    }

}
