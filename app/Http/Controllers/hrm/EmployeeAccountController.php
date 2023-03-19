<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

class EmployeeAccountController extends Controller
{


    public function index(Request $request)
    {
        //
    }

    //----------- Store new EmployeeAccount --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Employee::class);

        $this->validate($request, [
            'bank_name'      => 'required|string|max:255',
            'bank_branch'    => 'required|string|max:255',
            'account_no'     => 'required|string|max:255',
        ]);

        EmployeeAccount::create($request->all());

        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //-----------Update EmployeeAccount --------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Employee::class);

        $this->validate($request, [
            'bank_name'      => 'required|string|max:255',
            'bank_branch'    => 'required|string|max:255',
            'account_no'     => 'required|string|max:255',
        ]);

        EmployeeAccount::whereId($id)->update($request->all());

        return response()->json(['success' => true]);
    }



    //----------- Delete  EmployeeAccount --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Employee::class);

        EmployeeAccount::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }



}
