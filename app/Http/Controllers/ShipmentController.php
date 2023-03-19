<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Sale;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentController extends BaseController
{

    //----------- Get ALL Shipments-------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Shipment::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        $data = array();

        $shipments = Shipment::with('sale','sale.client','sale.warehouse')

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('status', 'LIKE', "%{$request->search}%")
                        ->orWhere('delivered_to', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale.warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale.client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });

                });
            });
        $totalRows = $shipments->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $shipments_data = $shipments->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($shipments_data as $shipment) {

            $item['id'] = $shipment['id'];
            $item['date'] = $shipment['date'];
            $item['shipment_ref'] = $shipment['Ref'];
            $item['status'] = $shipment['status'];
            $item['delivered_to'] = $shipment['delivered_to'];
            $item['shipping_address'] = $shipment['shipping_address'];
            $item['shipping_details'] = $shipment['shipping_details'];
            $item['sale_ref'] = $shipment['sale']['Ref'];
            $item['sale_id'] = $shipment['sale']['id'];
            $item['warehouse_name'] = $shipment['sale']['warehouse']->name;
            $item['customer_name'] = $shipment['sale']['client']->name;
            
            $data[] = $item;
        }

        return response()->json([
            'shipments' => $data,
            'totalRows' => $totalRows,
        ]);
    }

   

    //----------- Store new Shipment -------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Shipment::class);

        request()->validate([
            'status' => 'required',
        ]);

        \DB::transaction(function () use ($request) {
            $shipment = Shipment::firstOrNew([ 'Ref' => $request['Ref']]);

            $shipment->user_id = Auth::user()->id;
            $shipment->sale_id = $request['sale_id'];
            $shipment->delivered_to = $request['delivered_to'];
            $shipment->shipping_address = $request['shipping_address'];
            $shipment->shipping_details = $request['shipping_details'];
            $shipment->status = $request['status'];
            $shipment->save();

            $sale = Sale::findOrFail($request['sale_id']);
            $sale->update([
                'shipping_status' => $request['status'],
            ]);

        }, 10);
       
        return response()->json(['success' => true]);

    }

    public function show($id){

        $get_shipment = Shipment::where('sale_id', $id)->first();

        if($get_shipment){

            $shipment_data['Ref'] = $get_shipment->Ref;
            $shipment_data['sale_id'] = $get_shipment->sale_id;
            $shipment_data['delivered_to'] = $get_shipment->delivered_to;
            $shipment_data['shipping_address'] = $get_shipment->shipping_address;
            $shipment_data['status'] = $get_shipment->status;
            $shipment_data['shipping_details'] = $get_shipment->shipping_details;

        }else{

            $shipment_data['Ref'] = $this->getNumberOrder();
            $shipment_data['sale_id'] = $id;
            $shipment_data['delivered_to'] = '';
            $shipment_data['shipping_address'] = '';
            $shipment_data['status'] = '';
            $shipment_data['shipping_details'] = '';
        }
        return response()->json([
            'shipment' => $shipment_data,
        ]);

    }


    //----------- Update Shipment-------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Shipment::class);

        request()->validate([
            'status' => 'required',
        ]);

        \DB::transaction(function () use ($request , $id) {

            Shipment::whereId($id)->update($request->all());

            $sale = Sale::findOrFail($request['sale_id']);
            $sale->update([
                'shipping_status' => $request['status'],
            ]);

        }, 10);

        return response()->json(['success' => true]);

    }

    //----------- delete Shipment-------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Shipment::class);

        \DB::transaction(function () use ($request , $id) {

            $shipment = Shipment::find($id);
            $shipment->delete();

            $sale = Sale::findOrFail($shipment->sale_id);
            $sale->update([
                'shipping_status' => $request['status'],
            ]);

        }, 10);

        return response()->json(['success' => true]);

    }

   
   //------------- Reference Number Order SALE -----------\\

   public function getNumberOrder()
   {

       $last = DB::table('shipments')->latest('id')->first();

       if ($last) {
           $item = $last->Ref;
           $nwMsg = explode("_", $item);
           $inMsg = $nwMsg[1] + 1;
           $code = $nwMsg[0] . '_' . $inMsg;
       } else {
           $code = 'SM_1111';
       }
       return $code;
   }

    

}
