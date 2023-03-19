<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\OfficeShift;
use App\Models\Company;
use Carbon\Carbon;
use DateTime;

class OfficeShiftController extends Controller
{

    //----------- GET ALL  office_shift --------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', OfficeShift::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();
        $office_shifts = OfficeShift::with('company:id,name')->where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $office_shifts->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $office_shifts = $office_shifts->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($office_shifts as $office_shift) {

            $item['id'] = $office_shift->id;
            $item['name'] = $office_shift->name;
            $item['company_id'] = $office_shift['company']->id;
            $item['company_name'] = $office_shift['company']->name;
            $item['monday_in'] = $office_shift->monday_in?substr($office_shift->monday_in, 0, -2):NULL;
            $item['monday_out'] = $office_shift->monday_out?substr($office_shift->monday_out, 0, -2):NULL;
            $item['tuesday_in'] = $office_shift->tuesday_in?substr($office_shift->tuesday_in, 0, -2):NULL;
            $item['tuesday_out'] = $office_shift->tuesday_out?substr($office_shift->tuesday_out, 0, -2):NULL;
            $item['wednesday_in'] = $office_shift->wednesday_in?substr($office_shift->wednesday_in, 0, -2):NULL;
            $item['wednesday_out'] = $office_shift->wednesday_out?substr($office_shift->wednesday_out, 0, -2):NULL;
            $item['thursday_in'] = $office_shift->thursday_in?substr($office_shift->thursday_in, 0, -2):NULL;
            $item['thursday_out'] = $office_shift->thursday_out?substr($office_shift->thursday_out, 0, -2):NULL;
            $item['friday_in'] = $office_shift->friday_in?substr($office_shift->friday_in, 0, -2):NULL;
            $item['friday_out'] = $office_shift->friday_out?substr($office_shift->friday_out, 0, -2):NULL;
            $item['saturday_in'] = $office_shift->saturday_in?substr($office_shift->saturday_in, 0, -2):NULL;
            $item['saturday_out'] = $office_shift->saturday_out?substr($office_shift->saturday_out, 0, -2):NULL;
            $item['sunday_in'] = $office_shift->sunday_in?substr($office_shift->sunday_in, 0, -2):NULL;
            $item['sunday_out'] = $office_shift->sunday_out?substr($office_shift->sunday_out, 0, -2):NULL;
            $data[] = $item;
        }


        return response()->json([
            'office_shifts' => $data,
            'totalRows'   => $totalRows,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', OfficeShift::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //----------- Store new office_shift --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', OfficeShift::class);

        request()->validate([
            'name'           => 'required|string',
            'company_id'     => 'required',
        ]);

        $monday_in = new DateTime($request['monday_in']);
        $monday_out = new DateTime($request['monday_out']);
        $tuesday_in = new DateTime($request['tuesday_in']);
        $tuesday_out = new DateTime($request['tuesday_out']);
        $wednesday_in = new DateTime($request['wednesday_in']);
        $wednesday_out = new DateTime($request['wednesday_out']);
        $thursday_in = new DateTime($request['thursday_in']);
        $thursday_out = new DateTime($request['thursday_out']);
        $friday_in = new DateTime($request['friday_in']);
        $friday_out = new DateTime($request['friday_out']);
        $saturday_in = new DateTime($request['saturday_in']);
        $saturday_out = new DateTime($request['saturday_out']);
        $sunday_in = new DateTime($request['sunday_in']);
        $sunday_out = new DateTime($request['sunday_out']);

        OfficeShift::create([
            'company_id'     => $request['company_id'],
            'name'           => $request['name'],
            'monday_in'      => $request['monday_in']?$monday_in->format('H:iA'):Null,
            'monday_out'     => $request['monday_out']?$monday_out->format('H:iA'):Null,
            'tuesday_in'     => $request['tuesday_in']?$tuesday_in->format('H:iA'):Null,
            'tuesday_out'    => $request['tuesday_out']?$tuesday_out->format('H:iA'):Null,
            'wednesday_in'   => $request['wednesday_in']?$wednesday_in->format('H:iA'):Null,
            'wednesday_out'  => $request['wednesday_out']?$wednesday_out->format('H:iA'):Null,
            'thursday_in'    => $request['thursday_in']?$thursday_in->format('H:iA'):Null,
            'thursday_out'   => $request['thursday_out']?$thursday_out->format('H:iA'):Null,
            'friday_in'      => $request['friday_in']?$friday_in->format('H:iA'):Null,
            'friday_out'     => $request['friday_out']?$friday_out->format('H:iA'):Null,
            'saturday_in'    => $request['saturday_in']?$saturday_in->format('H:iA'):Null,
            'saturday_out'   => $request['saturday_out']?$saturday_out->format('H:iA'):Null,
            'sunday_in'      => $request['sunday_in']?$sunday_in->format('H:iA'):Null,
            'sunday_out'     => $request['sunday_out']?$sunday_out->format('H:iA'):Null,
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
        $this->authorizeForUser($request->user('api'), 'update', OfficeShift::class);

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        return response()->json([
            'companies' =>$companies,
        ]);

    }

    //-----------Update office_shift --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', OfficeShift::class);

        //monday_in
        if(strlen($request['monday_in']) == 5){
            $monday_in = new DateTime($request['monday_in']);
        }else{
            $monday_in =  new DateTime(substr($request['monday_in'], 0, -2));
        }

         //monday_out
        if(strlen($request['monday_out']) == 5){
            $monday_out = new DateTime($request['monday_out']);
        }else{
            $monday_out =  new DateTime(substr($request['monday_out'], 0, -2));
        }

        //tuesday_in
        if(strlen($request['tuesday_in']) == 5){
            $tuesday_in = new DateTime($request['tuesday_in']);
        }else{
            $tuesday_in =  new DateTime(substr($request['tuesday_in'], 0, -2));
        }

        //tuesday_out
        if(strlen($request['tuesday_out']) == 5){
            $tuesday_out = new DateTime($request['tuesday_out']);
        }else{
            $tuesday_out =  new DateTime(substr($request['tuesday_out'], 0, -2));
        }

        //wednesday_in
        if(strlen($request['wednesday_in']) == 5){
            $wednesday_in = new DateTime($request['wednesday_in']);
        }else{
            $wednesday_in =  new DateTime(substr($request['wednesday_in'], 0, -2));
        }

        //wednesday_out
        if(strlen($request['wednesday_out']) == 5){
            $wednesday_out = new DateTime($request['wednesday_out']);
        }else{
            $wednesday_out =  new DateTime(substr($request['wednesday_out'], 0, -2));
        }

        //thursday_in
        if(strlen($request['thursday_in']) == 5){
            $thursday_in = new DateTime($request['thursday_in']);
        }else{
            $thursday_in =  new DateTime(substr($request['thursday_in'], 0, -2));
        }

        //thursday_out
        if(strlen($request['thursday_out']) == 5){
            $thursday_out = new DateTime($request['thursday_out']);
        }else{
            $thursday_out =  new DateTime(substr($request['thursday_out'], 0, -2));
        }

        //friday_in
        if(strlen($request['friday_in']) == 5){
            $friday_in = new DateTime($request['friday_in']);
        }else{
            $friday_in =  new DateTime(substr($request['friday_in'], 0, -2));
        }

        //friday_out
        if(strlen($request['friday_out']) == 5){
            $friday_out = new DateTime($request['friday_out']);
        }else{
            $friday_out =  new DateTime(substr($request['friday_out'], 0, -2));
        }

        //saturday_in
        if(strlen($request['saturday_in']) == 5){
            $saturday_in = new DateTime($request['saturday_in']);
        }else{
            $saturday_in =  new DateTime(substr($request['saturday_in'], 0, -2));
        }

        //saturday_out
        if(strlen($request['saturday_out']) == 5){
            $saturday_out = new DateTime($request['saturday_out']);
        }else{
            $saturday_out =  new DateTime(substr($request['saturday_out'], 0, -2));
        }

        //sunday_in
        if(strlen($request['sunday_in']) == 5){
            $sunday_in = new DateTime($request['sunday_in']);
        }else{
            $sunday_in =  new DateTime(substr($request['sunday_in'], 0, -2));
        }

        //sunday_out
        if(strlen($request['sunday_out']) == 5){
            $sunday_out = new DateTime($request['sunday_out']);
        }else{
            $sunday_out =  new DateTime(substr($request['sunday_out'], 0, -2));
        }

        OfficeShift::whereId($id)->update([
            'company_id'     => $request['company_id'],
            'name'           => $request['name'],
            'monday_in'      => $request['monday_in']?$monday_in->format('H:iA'):Null,
            'monday_out'     => $request['monday_out']?$monday_out->format('H:iA'):Null,
            'tuesday_in'     => $request['tuesday_in']?$tuesday_in->format('H:iA'):Null,
            'tuesday_out'    => $request['tuesday_out']?$tuesday_out->format('H:iA'):Null,
            'wednesday_in'   => $request['wednesday_in']?$wednesday_in->format('H:iA'):Null,
            'wednesday_out'  => $request['wednesday_out']?$wednesday_out->format('H:iA'):Null,
            'thursday_in'    => $request['thursday_in']?$thursday_in->format('H:iA'):Null,
            'thursday_out'   => $request['thursday_out']?$thursday_out->format('H:iA'):Null,
            'friday_in'      => $request['friday_in']?$friday_in->format('H:iA'):Null,
            'friday_out'     => $request['friday_out']?$friday_out->format('H:iA'):Null,
            'saturday_in'    => $request['saturday_in']?$saturday_in->format('H:iA'):Null,
            'saturday_out'   => $request['saturday_out']?$saturday_out->format('H:iA'):Null,
            'sunday_in'      => $request['sunday_in']?$sunday_in->format('H:iA'):Null,
            'sunday_out'     => $request['sunday_out']?$sunday_out->format('H:iA'):Null,
        ]);


        return response()->json(['success' => true]);
    }

    //----------- Delete  office_shift --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', OfficeShift::class);

        \DB::transaction(function () use ($id) {

            OfficeShift::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', OfficeShift::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $office_shift_id) {
            OfficeShift::whereId($office_shift_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

}
