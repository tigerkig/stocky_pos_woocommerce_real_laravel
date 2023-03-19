<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Models\Leave;
use Carbon\Carbon;

class LeaveTypeController extends Controller
{

    //----------- GET ALL  Leave type --------------\\

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

        $leave_types = LeaveType::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('title', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $leave_types->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $leave_types = $leave_types->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'leave_types' => $leave_types,
            'totalRows' => $totalRows,
        ]);
    }

    //----------- Store new Leave --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Leave::class);

        request()->validate([
            'title'      => 'required|string',
        ]);

        LeaveType::create([
            'title'           => $request['title'],
        ]);

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //-----------Update Leave --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Leave::class);

        request()->validate([
            'title'           => 'required|string',
        ]);

        LeaveType::whereId($id)->update([
            'title'           => $request['title'],
        ]);
    
        return response()->json(['success' => true]);

        return response()->json(['success' => true]);
    }

    //----------- Delete  Leave --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Leave::class);

        LeaveType::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Leave::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $leave_type_id) {
            LeaveType::whereId($leave_type_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }


}
