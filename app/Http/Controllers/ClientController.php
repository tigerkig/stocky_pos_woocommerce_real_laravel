<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Setting;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\SaleReturn;
use App\Models\PaymentSaleReturns;
use App\Models\Sale;
use App\Models\PaymentSale;
use DB;
use Illuminate\Http\Request;

class ClientController extends BaseController
{

    //------------- Get ALL Customers -------------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Client::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $columns = array(0 => 'name', 1 => 'code', 2 => 'phone', 3 => 'email');
        $param = array(0 => 'like', 1 => 'like', 2 => 'like', 3 => 'like');
        $data = array();
        $clients = Client::where('deleted_at', '=', null);

        //Multiple Filter
        $Filtred = $helpers->filter($clients, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('code', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $clients = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($clients as $client) {

            $item['total_amount'] = DB::table('sales')
                ->where('deleted_at', '=', null)
                ->where('client_id', $client->id)
                ->sum('GrandTotal');

            $item['total_paid'] = DB::table('sales')
                ->where('sales.deleted_at', '=', null)
                ->where('sales.client_id', $client->id)
                ->sum('paid_amount');

            $item['due'] = $item['total_amount'] - $item['total_paid'];

            $item['total_amount_return'] = DB::table('sale_returns')
                ->where('deleted_at', '=', null)
                ->where('client_id', $client->id)
                ->sum('GrandTotal');

            $item['total_paid_return'] = DB::table('sale_returns')
                ->where('sale_returns.deleted_at', '=', null)
                ->where('sale_returns.client_id', $client->id)
                ->sum('paid_amount');

            $item['return_Due'] = $item['total_amount_return'] - $item['total_paid_return'];

            $item['id'] = $client->id;
            $item['name'] = $client->name;
            $item['phone'] = $client->phone;
            $item['tax_number'] = $client->tax_number;
            $item['code'] = $client->code;
            $item['email'] = $client->email;
            $item['country'] = $client->country;
            $item['city'] = $client->city;
            $item['adresse'] = $client->adresse;
            $data[] = $item;
        }

        $company_info = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'clients' => $data,
            'company_info' => $company_info,
            'totalRows' => $totalRows,
        ]);
    }

    //------------- Store new Customer -------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Client::class);

        $this->validate($request, [
                'name' => 'required',
            ]
        );
        
        Client::create([
            'name' => $request['name'],
            'code' => $this->getNumberOrder(),
            'adresse' => $request['adresse'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'country' => $request['country'],
            'city' => $request['city'],
            'tax_number' => $request['tax_number'],
        ]);
        return response()->json(['success' => true]);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------- Update Customer -------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Client::class);
        $this->validate($request, [
            
                'name' => 'required',
            ]
        );

        Client::whereId($id)->update([
            'name' => $request['name'],
            'adresse' => $request['adresse'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'country' => $request['country'],
            'city' => $request['city'],
            'tax_number' => $request['tax_number'],
        ]);
        return response()->json(['success' => true]);

    }

    //------------- delete client -------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Client::class);

        Client::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Client::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $Client_id) {
            Client::whereId($Client_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return response()->json(['success' => true]);
    }


    //------------- get Number Order Customer -------------\\

    public function getNumberOrder()
    {
        $last = DB::table('clients')->latest('id')->first();

        if ($last) {
            $code = $last->code + 1;
        } else {
            $code = 1;
        }
        return $code;
    }

    //------------- Get Clients Without Paginate -------------\\

    public function Get_Clients_Without_Paginate()
    {
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        return response()->json($clients);
    }

    // import clients
    public function import_clients(Request $request)
    {
        $file_upload = $request->file('clients');
        $ext = pathinfo($file_upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if ($ext != 'csv') {
            return response()->json([
                'msg' => 'must be in csv format',
                'status' => false,
            ]);
        } else {
            $data = array();
            $rowcount = 0;
            if (($handle = fopen($file_upload, "r")) !== false) {
                $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
                $header = fgetcsv($handle, $max_line_length);
                $header_colcount = count($header);
                while (($row = fgetcsv($handle, $max_line_length)) !== false) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        $entry = array_combine($header, $row);
                        $data[] = $entry;
                    } else {
                        return null;
                    }
                    $rowcount++;
                }
                fclose($handle);
            } else {
                return null;
            }
           
            $rules = array('email' => 'required|email|unique:clients');
            //-- Create New Client
            foreach ($data as $key => $value) {
                $input['email'] = $value['email'];

                $validator = Validator::make($input, $rules);
                if (!$validator->fails()) {
                    
                    Client::create([
                        'name' => $value['name'] == '' ? null : $value['name'],
                        'code' => $this->getNumberOrder(),
                        'adresse' => $value['adresse'] == '' ? null : $value['adresse'],
                        'phone' => $value['phone'] == '' ? null : $value['phone'],
                        'email' => $value['email'] == '' ? null : $value['email'],
                        'country' => $value['country'] == '' ? null : $value['country'],
                        'city' => $value['city'] == '' ? null : $value['city'],
                        'tax_number' => $value['tax_number'] == '' ? null : $value['tax_number'],
                    ]);
                }

            }

            return response()->json([
                'status' => true,
            ], 200);
        }

    }


     //------------- clients_pay_due -------------\\

     public function clients_pay_due(Request $request)
     {
         $this->authorizeForUser($request->user('api'), 'pay_due', Client::class);
        
         if($request['amount'] > 0){
            $client_sales_due = Sale::where('deleted_at', '=', null)
            ->where([
                ['payment_statut', '!=', 'paid'],
                ['client_id', $request->client_id]
            ])->get();

            $paid_amount_total = $request->amount;

            foreach($client_sales_due as $key => $client_sale){
                if($paid_amount_total == 0)
                break;
                $due = $client_sale->GrandTotal  - $client_sale->paid_amount;

                if($paid_amount_total >= $due){
                    $amount = $due;
                    $payment_status = 'paid';
                }else{
                    $amount = $paid_amount_total;
                    $payment_status = 'partial';
                }

                $payment_sale = new PaymentSale();
                $payment_sale->sale_id = $client_sale->id;
                $payment_sale->Ref = app('App\Http\Controllers\PaymentSalesController')->getNumberOrder();
                $payment_sale->date = Carbon::now();
                $payment_sale->Reglement = $request['Reglement'];
                $payment_sale->montant = $amount;
                $payment_sale->change = 0;
                $payment_sale->notes = $request['notes'];
                $payment_sale->user_id = Auth::user()->id;
                $payment_sale->save();

                $client_sale->paid_amount += $amount;
                $client_sale->payment_statut = $payment_status;
                $client_sale->save();

                $paid_amount_total -= $amount;
            }
        }
        
         return response()->json(['success' => true]);
 
     }

    //------------- clients_pay_sale_return_due -------------\\

    public function pay_sale_return_due(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'pay_sale_return_due', Client::class);
        
        if($request['amount'] > 0){
            $client_sell_return_due = SaleReturn::where('deleted_at', '=', null)
            ->where([
                ['payment_statut', '!=', 'paid'],
                ['client_id', $request->client_id]
            ])->get();

            $paid_amount_total = $request->amount;

            foreach($client_sell_return_due as $key => $client_sale_return){
                if($paid_amount_total == 0)
                break;
                $due = $client_sale_return->GrandTotal  - $client_sale_return->paid_amount;

                if($paid_amount_total >= $due){
                    $amount = $due;
                    $payment_status = 'paid';
                }else{
                    $amount = $paid_amount_total;
                    $payment_status = 'partial';
                }

                $payment_sale_return = new PaymentSaleReturns();
                $payment_sale_return->sale_return_id = $client_sale_return->id;
                $payment_sale_return->Ref = app('App\Http\Controllers\PaymentSaleReturnsController')->getNumberOrder();
                $payment_sale_return->date = Carbon::now();
                $payment_sale_return->Reglement = $request['Reglement'];
                $payment_sale_return->montant = $amount;
                $payment_sale_return->change = 0;
                $payment_sale_return->notes = $request['notes'];
                $payment_sale_return->user_id = Auth::user()->id;
                $payment_sale_return->save();

                $client_sale_return->paid_amount += $amount;
                $client_sale_return->payment_statut = $payment_status;
                $client_sale_return->save();

                $paid_amount_total -= $amount;
            }
        }
        
        return response()->json(['success' => true]);

    }

}
