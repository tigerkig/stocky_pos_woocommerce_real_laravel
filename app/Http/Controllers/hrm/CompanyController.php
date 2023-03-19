<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Company;
use Carbon\Carbon;
use DB;

class CompanyController extends Controller
{

    //----------- GET ALL  company --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Company::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        $companies = Company::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%")
                        ->orWhere('country', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $companies->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $companies = $companies->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'companies' => $companies,
            'totalRows' => $totalRows,
        ]);
    }

    //----------- Store new Company --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Company::class);

        request()->validate([
            'name'      => 'required|string',
        ]);

        Company::create([
            'name'    => $request['name'],
            'email'   => $request['email'],
            'phone'   => $request['phone'],
            'country' => $request['country'],
        ]);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //-----------Update Warehouse --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Company::class);

        request()->validate([
            'name'      => 'required|string',
        ]);

        Company::whereId($id)->update([
            'name'    => $request['name'],
            'email'   => $request['email'],
            'phone'   => $request['phone'],
            'country' => $request['country'],
        ]);

        return response()->json(['success' => true]);
    }

    //----------- Delete  company --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Company::class);

        Company::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);


        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Company::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $company_id) {
            Company::whereId($company_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    //----------- GET ALL  Company --------------\\
    
    public function Get_all_Company()
    {
        $companies = Company::where('deleted_at', '=', null)
        ->orderBy('id', 'desc')
        ->get(['id','name']);

        return response()->json($companies);
    }

}
