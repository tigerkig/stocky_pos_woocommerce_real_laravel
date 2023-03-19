<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Company;
use Carbon\Carbon;

class HolidayController extends Controller
{

    //----------- GET ALL Holidays --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Holiday::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        $holidays = Holiday::with('company')->where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('title', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $holidays->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $holidays = $holidays->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($holidays as $holiday) {

            $item['id'] = $holiday->id;
            $item['title'] = $holiday->title;
            $item['company_id'] = $holiday['company']->id;
            $item['company_name'] = $holiday['company']->name;
            $item['start_date'] = $holiday->start_date;
            $item['end_date'] = $holiday->end_date;
            $item['description'] = $holiday->description;
            
            $data[] = $item;
        }

        return response()->json([
            'holidays' => $data,
            'totalRows' => $totalRows,
        ]);
    }



    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Holiday::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);
    }

    //----------- Store new Holiday --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Holiday::class);

        request()->validate([
            'title'           => 'required|string',
            'start_date'      => 'required',
            'end_date'        => 'required',
            'company_id'      => 'required',
        ]);

        Holiday::create([
            'company_id'      => $request['company_id'],
            'title'           => $request['title'],
            'start_date'      => $request['start_date'],
            'end_date'        => $request['end_date'],
            'description'     => $request['description'],
        ]);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }


    public function edit(Request $request ,$id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Holiday::class);

        $companies = Company::where('deleted_at', '=', null)->orderBy('id', 'desc')->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //-----------Update Holiday --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Holiday::class);

        request()->validate([
            'title'           => 'required|string|max:255',
            'start_date'      => 'required',
            'end_date'        => 'required',
            'company_id'      => 'required',
        ]);

        Holiday::whereId($id)->update([
            'company_id'      => $request['company_id'],
            'title'           => $request['title'],
            'start_date'      => $request['start_date'],
            'end_date'        => $request['end_date'],
            'description'     => $request['description'],
        ]);

        return response()->json(['success' => true]);
    }

    //----------- Delete  Holiday --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Holiday::class);

        Holiday::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);


        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Holiday::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $holiday_id) {
            Holiday::whereId($holiday_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }


}
