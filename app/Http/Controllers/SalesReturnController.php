<?php

namespace App\Http\Controllers;

use App\Mail\ReturnMail;
use App\Models\Client;
use App\Models\Unit;
use App\Models\PaymentSaleReturns;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\UserWarehouse;
use App\utils\helpers;
use Carbon\Carbon;
use Twilio\Rest\Client as Client_Twilio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use \Nwidart\Modules\Facades\Module;
use App\Models\sms_gateway;
use DB;
use PDF;
use WooCommerce;

class SalesReturnController extends BaseController
{

    //------------ GET ALL Sale Return--------------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
            3 => 'like',
            4 => '=',
            5 => '=',
            6 => '=',
        );
        $columns = array(
            0 => 'Ref',
            1 => 'statut',
            2 => 'client_id',
            3 => 'payment_statut',
            4 => 'warehouse_id',
            5 => 'date',
            6 => 'sale_id',
        );
        $data = array();

        // Check If User Has Permission View  All Records
        $SaleReturn = SaleReturn::with('sale','facture', 'client', 'warehouse')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            });

        //Multiple Filter
        $Filtred = $helpers->filter($SaleReturn, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $SaleReturn = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($SaleReturn as $Sale_Return) {

            $item['id'] = $Sale_Return->id;
            $item['date'] = $Sale_Return->date;
            $item['Ref'] = $Sale_Return->Ref;
            $item['discount'] = $Sale_Return->discount;
            $item['shipping'] = $Sale_Return->shipping;
            $item['statut'] = $Sale_Return->statut;
            $item['qte_retturn'] = $Sale_Return->qte_retour;
            $item['warehouse_name'] = $Sale_Return['warehouse']->name;
            $item['sale_ref'] = $Sale_Return['sale']?$Sale_Return['sale']->Ref:'---';
            $item['sale_id'] = $Sale_Return['sale']?$Sale_Return['sale']->id:NULL;
            $item['client_id'] = $Sale_Return['client']->id;
            $item['client_name'] = $Sale_Return['client']->name;
            $item['client_email'] = $Sale_Return['client']->email;
            $item['client_tele'] = $Sale_Return['client']->phone;
            $item['client_code'] = $Sale_Return['client']->code;
            $item['client_adr'] = $Sale_Return['client']->adresse;
            $item['GrandTotal'] = number_format($Sale_Return['GrandTotal'], 2, '.', '');
            $item['paid_amount'] = number_format($Sale_Return['paid_amount'], 2, '.', '');
            $item['due'] = number_format($item['GrandTotal'] - $item['paid_amount'], 2, '.', '');
            $item['payment_status'] = $Sale_Return['payment_statut'];

            $data[] = $item;
        }

        $customers = client::where('deleted_at', '=', null)->get(['id', 'name']);
        $sales = Sale::where('deleted_at', '=', null)->get(['id', 'Ref']);

        //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

        return response()->json([
            'totalRows' => $totalRows,
            'sale_Return' => $data,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'sales' => $sales,
        ]);

    }

    //------------ Store new Sale Return --------------\\

    public function store(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', SaleReturn::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',
            'statut' => 'required',
        ]);

        \DB::transaction(function () use ($request) {
            $order = new SaleReturn;

            $order->date = $request->date;
            $order->Ref = $this->getNumberOrder();
            $order->client_id = $request->client_id;
            $order->sale_id = $request->sale_id;
            $order->warehouse_id = $request->warehouse_id;
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping;
            $order->GrandTotal = $request->GrandTotal;
            $order->statut = $request->statut;
            $order->payment_statut = 'unpaid';
            $order->notes = $request->notes;
            $order->user_id = Auth::user()->id;

            $order->save();

            $data = $request['details'];
            foreach ($data as $key => $value) {
                $unit = Unit::where('id', $value['sale_unit_id'])->first();

                $orderDetails[] = [
                    'sale_return_id' => $order->id,
                    'quantity' => $value['quantity'],
                    'price' => $value['Unit_price'],
                    'sale_unit_id' =>  $value['sale_unit_id'],
                    'TaxNet' => $value['tax_percent'],
                    'tax_method' => $value['tax_method'],
                    'discount' => $value['discount'],
                    'discount_method' => $value['discount_Method'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'total' => $value['subtotal'],
                    'imei_number' => $value['imei_number'],
                ];

                if ($order->statut == "received") {
                    if ($value['product_variant_id'] !== null) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte += $value['quantity'] * $unit->operator_value;
                            }

                            $product_warehouse->save();
                        }

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte += $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte += $value['quantity'] * $unit->operator_value;
                            }

                            $product_warehouse->save();
                        }
                    }

                    $product_warehouse_data = product_warehouse::where('product_id', $value['product_id'])
                        ->where('deleted_at', '=', null)
                        ->get();
                    $total_qty = 0;
                    foreach ($product_warehouse_data as $product_warehouse) {
                        $total_qty += $product_warehouse->qte;
                    }

                    $Product = Product::where('id', $value['product_id'])
                        ->where('deleted_at', '=', null)
                        ->first();

                    if($Product->pos_id != -1){
                        if($Product->pos_var_id == -1){
                            $data = [
                                'manage_stock' => true,
                                'stock_quantity' => $total_qty,
                            ];
                
                            $product = $this->update_product_woo($Product->pos_id, $data);
                        }else{
                            $data = [
                                'manage_stock' => true,
                                'stock_quantity' => $total_qty,
                            ];
    
                            $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                        }
                    }
                }

            }
            SaleReturnDetails::insert($orderDetails);
        }, 10);

        return response()->json(['success' => true]);
    }

    public function update_product_woo($id, $data){
        $result = WooCommerce::update('products/'.$id, $data);
        return $result;
    }

    public function update_product_variation_woo($product_id, $product_variation_id, $data){
        $result = WooCommerce::update('products/'.$product_id.'/'.'variations/'.$product_variation_id, $data);
        return $result;
    }
    //------------ Update Return Sale--------------\\

    public function update(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', SaleReturn::class);

        \DB::transaction(function () use ($request, $id) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $current_SaleReturn = SaleReturn::findOrFail($id);

            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === SaleReturn->id
                $this->authorizeForUser($request->user('api'), 'check_record', $current_SaleReturn);
            }
            $old_return_details = SaleReturnDetails::where('sale_return_id', $id)->get();
            $new_return_details = $request['details'];
            $length = sizeof($new_return_details);

            // Get Ids details
            $new_products_id = [];
            foreach ($new_return_details as $new_detail) {
                $new_products_id[] = $new_detail['id'];
            }

            // Init Data with old Parametre
            $old_products_id = [];
            foreach ($old_return_details as $key => $value) {
                $old_products_id[] = $value->id;

                 //check if detail has sale_unit_id Or Null
                 if($value['sale_unit_id'] !== null){
                    $unit = Unit::where('id', $value['sale_unit_id'])->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $value['product_id'])
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }

                if($value['sale_unit_id'] !== null){
                    if ($current_SaleReturn->statut == "received") {
                        if ($value['product_variant_id'] !== null) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                                ->where('product_id', $value['product_id'])->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                        $product_warehouse_data = product_warehouse::where('product_id', $value['product_id'])
                            ->where('deleted_at', '=', null)
                            ->get();
                        $total_qty = 0;
                        foreach ($product_warehouse_data as $product_warehouse) {
                            $total_qty += $product_warehouse->qte;
                        }

                        $Product = Product::where('id', $value['product_id'])
                            ->where('deleted_at', '=', null)
                            ->first();

                        if($Product->pos_id != -1){
                            if($Product->pos_var_id == -1){
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
                    
                                $product = $this->update_product_woo($Product->pos_id, $data);
                            }else{
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
        
                                $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                            }
                        }
                    }

                    // Delete Detail
                    if (!in_array($old_products_id[$key], $new_products_id)) {
                        $SaleReturnDetails = SaleReturnDetails::findOrFail($value->id);
                        $SaleReturnDetails->delete();
                    }
                }

            }

            // Update Data with New request
            foreach ($new_return_details as $key => $product_detail) {
               
                if($product_detail['no_unit'] !== 0){
                    $unit_prod = Unit::where('id', $product_detail['sale_unit_id'])->first();

                    if ($request['statut'] == "received") {

                        if ($product_detail['product_variant_id'] !== null) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->where('product_variant_id', $product_detail['product_variant_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $product_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $product_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $product_detail['product_id'])
                                ->first();

                            if ($unit_prod && $product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    $product_warehouse->qte += $product_detail['quantity'] / $unit_prod->operator_value;
                                } else {
                                    $product_warehouse->qte += $product_detail['quantity'] * $unit_prod->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }

                        $product_warehouse_data = product_warehouse::where('product_id', $product_detail['product_id'])
                            ->where('deleted_at', '=', null)
                            ->get();
                        $total_qty = 0;
                        foreach ($product_warehouse_data as $product_warehouse) {
                            $total_qty += $product_warehouse->qte;
                        }

                        $Product = Product::where('id', $product_detail['product_id'])
                            ->where('deleted_at', '=', null)
                            ->first();

                        if($Product->pos_id != -1){
                            if($Product->pos_var_id == -1){
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
                    
                                $product = $this->update_product_woo($Product->pos_id, $data);
                            }else{
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
        
                                $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                            }
                        }
                    }

                    $orderDetails['sale_return_id'] = $id;
                    $orderDetails['sale_unit_id'] = $product_detail['sale_unit_id'];
                    $orderDetails['quantity'] = $product_detail['quantity'];
                    $orderDetails['price'] = $product_detail['Unit_price'];
                    $orderDetails['TaxNet'] = $product_detail['tax_percent'];
                    $orderDetails['tax_method'] = $product_detail['tax_method'];
                    $orderDetails['discount'] = $product_detail['discount'];
                    $orderDetails['discount_method'] = $product_detail['discount_Method'];
                    $orderDetails['product_id'] = $product_detail['product_id'];
                    $orderDetails['product_variant_id'] = $product_detail['product_variant_id'];
                    $orderDetails['total'] = $product_detail['subtotal'];
                    $orderDetails['imei_number'] = $product_detail['imei_number'];

                    if (!in_array($product_detail['id'], $old_products_id)) {
                        SaleReturnDetails::Create($orderDetails);
                    } else {
                        SaleReturnDetails::where('id', $product_detail['id'])->update($orderDetails);
                    }
                }

            }

            $due = $request['GrandTotal'] - $current_SaleReturn->paid_amount;
            if ($due === 0.0 || $due < 0.0) {
                $payment_statut = 'paid';
            } else if ($due != $request['GrandTotal']) {
                $payment_statut = 'partial';
            } else if ($due == $request['GrandTotal']) {
                $payment_statut = 'unpaid';
            }

            $current_SaleReturn->update([
                'date' => $request['date'],
                'notes' => $request['notes'],
                'statut' => $request['statut'],
                'tax_rate' => $request['tax_rate'],
                'TaxNet' => $request['TaxNet'],
                'discount' => $request['discount'],
                'shipping' => $request['shipping'],
                'GrandTotal' => $request['GrandTotal'],
                'payment_statut' => $payment_statut,
            ]);

        }, 10);

        return response()->json(['success' => true]);
    }

    //------------ Delete Sale Return--------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', SaleReturn::class);

        \DB::transaction(function () use ($id, $request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $current_SaleReturn = SaleReturn::findOrFail($id);
            $old_return_details = SaleReturnDetails::where('sale_return_id', $id)->get();

            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === current_SaleReturn->id
                $this->authorizeForUser($request->user('api'), 'check_record', $current_SaleReturn);
            }

            foreach ($old_return_details as $key => $value) {

                 //check if detail has sale_unit_id Or Null
                 if($value['sale_unit_id'] !== null){
                    $unit = Unit::where('id', $value['sale_unit_id'])->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $value['product_id'])
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }

                if ($current_SaleReturn->statut == "received") {
                    if ($value['product_variant_id'] !== null) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                            ->where('product_id', $value['product_id'])->where('product_variant_id', $value['product_variant_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse->save();
                        }

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->first();

                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            } else {
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }
                            $product_warehouse->save();
                        }
                    }

                    $product_warehouse_data = product_warehouse::where('product_id', $value['product_id'])
                        ->where('deleted_at', '=', null)
                        ->get();
                    $total_qty = 0;
                    foreach ($product_warehouse_data as $product_warehouse) {
                        $total_qty += $product_warehouse->qte;
                    }

                    $Product = Product::where('id', $value['product_id'])
                        ->where('deleted_at', '=', null)
                        ->first();

                    if($Product->pos_id != -1){
                        if($Product->pos_var_id == -1){
                            $data = [
                                'manage_stock' => true,
                                'stock_quantity' => $total_qty,
                            ];
                
                            $product = $this->update_product_woo($Product->pos_id, $data);
                        }else{
                            $data = [
                                'manage_stock' => true,
                                'stock_quantity' => $total_qty,
                            ];
    
                            $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                        }
                    }
                }

            }

            $current_SaleReturn->details()->delete();
            $current_SaleReturn->update([
                'deleted_at' => Carbon::now(),
            ]);
            PaymentSaleReturns::where('sale_return_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', SaleReturn::class);

        \DB::transaction(function () use ($request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $SaleReturn_id) {

                $current_SaleReturn = SaleReturn::findOrFail($SaleReturn_id);
                $old_return_details = SaleReturnDetails::where('sale_return_id', $SaleReturn_id)->get();
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === current_SaleReturn->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $current_SaleReturn);
                }

                foreach ($old_return_details as $key => $value) {

                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                       $unit = Unit::where('id', $value['sale_unit_id'])->first();
                   }else{
                       $product_unit_sale_id = Product::with('unitSale')
                       ->where('id', $value['product_id'])
                       ->first();
                       $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                   }
   
                   if ($current_SaleReturn->statut == "received") {
                       if ($value['product_variant_id'] !== null) {
                           $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                               ->where('product_id', $value['product_id'])->where('product_variant_id', $value['product_variant_id'])
                               ->first();
   
                           if ($unit && $product_warehouse) {
                               if ($unit->operator == '/') {
                                   $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                               } else {
                                   $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                               }
                               $product_warehouse->save();
                           }
   
                       } else {
                           $product_warehouse = product_warehouse::where('deleted_at', '=', null)->where('warehouse_id', $current_SaleReturn->warehouse_id)
                               ->where('product_id', $value['product_id'])
                               ->first();
   
                           if ($unit && $product_warehouse) {
                               if ($unit->operator == '/') {
                                   $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                               } else {
                                   $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                               }
                               $product_warehouse->save();
                           }
                       }

                       $product_warehouse_data = product_warehouse::where('product_id', $value['product_id'])
                            ->where('deleted_at', '=', null)
                            ->get();
                        $total_qty = 0;
                        foreach ($product_warehouse_data as $product_warehouse) {
                            $total_qty += $product_warehouse->qte;
                        }

                        $Product = Product::where('id', $value['product_id'])
                            ->where('deleted_at', '=', null)
                            ->first();

                        if($Product->pos_id != -1){
                            if($Product->pos_var_id == -1){
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
                    
                                $product = $this->update_product_woo($Product->pos_id, $data);
                            }else{
                                $data = [
                                    'manage_stock' => true,
                                    'stock_quantity' => $total_qty,
                                ];
        
                                $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                            }
                        }
                   }
   
               }

                $current_SaleReturn->details()->delete();
                $current_SaleReturn->update([
                    'deleted_at' => Carbon::now(),
                ]);
                PaymentSaleReturns::where('sale_return_id', $SaleReturn_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);
            }

        }, 10);

        return response()->json(['success' => true]);
    }



    //------------- GET Payments Sale Return-----------\\

    public function Payment_Returns(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', PaymentSaleReturns::class);

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $SaleReturn = SaleReturn::findOrFail($id);

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === SaleReturn->id
            $this->authorizeForUser($request->user('api'), 'check_record', $SaleReturn);
        }

        $payments = PaymentSaleReturns::with('SaleReturn')
            ->where('sale_return_id', $id)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })->orderBy('id', 'DESC')->get();

        $due = $SaleReturn->GrandTotal - $SaleReturn->paid_amount;

        return response()->json(['payments' => $payments, 'due' => $due]);
    }

    //------------- SEND Sale Return on EMAIL -----------\\

    public function Send_Email(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'view', SaleReturn::class);

        $SaleReturn['id'] = $request->id;
        $SaleReturn['Ref'] = $request->Ref;
        $settings = Setting::where('deleted_at', '=', null)->first();
        $SaleReturn['company_name'] = $settings->CompanyName;

        $pdf = $this->Return_pdf($request, $SaleReturn['id']);
        $this->Set_config_mail(); // Set_config_mail => BaseController
        $mail = Mail::to($request->to)->send(new ReturnMail($SaleReturn, $pdf));
        return $mail;
    }

    //------------ Reference Order Of Sale Return --------------\\

    public function getNumberOrder()
    {
        $last = DB::table('sale_returns')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'RT_1111';
        }
        return $code;
    }

    //---------------- Get Details Sale Return  -----------------\\

    public function show(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Sale_Return = SaleReturn::with('sale','details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = array();

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === SaleReturn->id
            $this->authorizeForUser($request->user('api'), 'check_record', $Sale_Return);
        }

        $return_details['Ref'] = $Sale_Return->Ref;
        $return_details['sale_id'] = $Sale_Return->sale_id?$Sale_Return['sale']->id:NULL;
        $return_details['sale_ref'] = $Sale_Return['sale']?$Sale_Return['sale']->Ref:'---';
        $return_details['date'] = $Sale_Return->date;
        $return_details['note'] = $Sale_Return->notes;
        $return_details['statut'] = $Sale_Return->statut;
        $return_details['discount'] = $Sale_Return->discount;
        $return_details['shipping'] = $Sale_Return->shipping;
        $return_details['tax_rate'] = $Sale_Return->tax_rate;
        $return_details['TaxNet'] = $Sale_Return->TaxNet;
        $return_details['client_name'] = $Sale_Return['client']->name;
        $return_details['client_phone'] = $Sale_Return['client']->phone;
        $return_details['client_adr'] = $Sale_Return['client']->adresse;
        $return_details['client_email'] = $Sale_Return['client']->email;
        $return_details['client_tax'] = $Sale_Return['client']->tax_number;
        $return_details['warehouse'] = $Sale_Return['warehouse']->name;
        $return_details['GrandTotal'] = number_format($Sale_Return->GrandTotal, 2, '.', '');
        $return_details['paid_amount'] = number_format($Sale_Return->paid_amount, 2, '.', '');
        $return_details['due'] = number_format($return_details['GrandTotal'] - $return_details['paid_amount'], 2, '.', '');
        $return_details['payment_status'] = $Sale_Return->payment_statut;

        foreach ($Sale_Return['details'] as $detail) {

             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }
            
            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
               
            } else {
                $data['code'] = $detail['product']['code'];
            }

            $data['quantity'] = $detail->quantity;
            $data['total'] = $detail->total;
            $data['name'] = $detail['product']['name'];
            $data['price'] = $detail->price;
            $data['unit_sale'] = $unit->ShortName;

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = $detail->discount;
            } else {
                $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = $detail->price;
            $data['discount'] = $detail->discount;

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = $tax_price;
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;

        }

        $company = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'details' => $details,
            'sale_Return' => $return_details,
            'company' => $company,
        ]);
    }

    //---------------- Show Elements Sale Return ---------------\\

    public function create(Request $request)
    {

        //

    }

    //---------------- edit ---------------\\

    public function edit(Request $request , $id)
    {

        //

    }

    public function create_sell_return(Request $request , $id)
    {

        $this->authorizeForUser($request->user('api'), 'create', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $SaleReturn = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->find($id);

        $details = array();

        if($SaleReturn){
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === SaleReturn->id
                $this->authorizeForUser($request->user('api'), 'check_record', $SaleReturn);
            }

            $Return_detail['client_id'] = $SaleReturn->client_id;
            $Return_detail['warehouse_id'] = $SaleReturn->warehouse_id;
            $Return_detail['sale_id'] = $SaleReturn->id;
            $Return_detail['sale_ref'] = $SaleReturn->Ref;
            $Return_detail['tax_rate'] = 0;
            $Return_detail['TaxNet'] = 0;
            $Return_detail['discount'] = 0;
            $Return_detail['shipping'] = 0;
            $Return_detail['statut'] = "received";
            $Return_detail['notes'] = "";

            $detail_id = 0;
            foreach ($SaleReturn['details'] as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                    $data['no_unit'] = 1;
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $detail->product_id)
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    $data['no_unit'] = 0;
                }

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->first();

                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $SaleReturn->warehouse_id)
                        ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];
                

                }

                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = 0;
                $data['sale_quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['name'] = $detail['product']['name'];
                $data['unitSale'] = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;
                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }

                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {

                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

                $details[] = $data;
            }
        }else{
            $SaleReturn = $this->sale_woo($id);

            $Return_detail['client_id'] = $SaleReturn->customer_id;
            $Return_detail['warehouse_id'] = 1;
            $Return_detail['sale_id'] = $SaleReturn->id;
            $Return_detail['sale_ref'] = 'SL_' . $SaleReturn->id;
            $Return_detail['tax_rate'] = 0;
            $Return_detail['TaxNet'] = 0;
            $Return_detail['discount'] = 0;
            $Return_detail['shipping'] = 0;
            $Return_detail['statut'] = "received";
            $Return_detail['notes'] = "";

            $detail_id = 0;
        }

        return response()->json([
            'details' => $details,
            'sale_return' => $Return_detail,
        ]);

    }

    public function sale_woo($id){
        $result = WooCommerce::find('orders/'.$id);
        return $result;
    } 
    //------------- Sale Return PDF-----------\\

    public function Return_pdf(Request $request, $id)
    {

        $details = array();
        $helpers = new helpers();
        $Sale_Return = SaleReturn::with('sale','details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $return_details['client_name'] = $Sale_Return['client']->name;
        $return_details['client_phone'] = $Sale_Return['client']->phone;
        $return_details['client_adr'] = $Sale_Return['client']->adresse;
        $return_details['client_email'] = $Sale_Return['client']->email;
        $return_details['client_tax'] = $Sale_Return['client']->tax_number;
        $return_details['TaxNet'] = number_format($Sale_Return->TaxNet, 2, '.', '');
        $return_details['discount'] = number_format($Sale_Return->discount, 2, '.', '');
        $return_details['shipping'] = number_format($Sale_Return->shipping, 2, '.', '');
        $return_details['statut'] = $Sale_Return->statut;
        $return_details['sale_ref'] = $Sale_Return['sale']?$Sale_Return['sale']->Ref:'---';
        $return_details['Ref'] = $Sale_Return->Ref;
        $return_details['date'] = $Sale_Return->date;
        $return_details['GrandTotal'] = number_format($Sale_Return->GrandTotal, 2, '.', '');
        $return_details['paid_amount'] = number_format($Sale_Return->paid_amount, 2, '.', '');
        $return_details['due'] = number_format($return_details['GrandTotal'] - $return_details['paid_amount'], 2, '.', '');
        $return_details['payment_status'] = $Sale_Return->payment_statut;

        $detail_id = 0;
        foreach ($Sale_Return['details'] as $detail) {
             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {
                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)
                    ->first();
                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
            } else {
                $data['code'] = $detail['product']['code'];
            }
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = number_format($detail->quantity, 2, '.', '');
                $data['total'] = number_format($detail->total, 2, '.', '');
                $data['name'] = $detail['product']['name'];
                $data['unitSale'] = $unit->ShortName;
                $data['price'] = number_format($detail->price, 2, '.', '');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = number_format($detail->price, 2, '.', '');
            $data['discount'] = $detail->discount;number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = number_format($tax_price, 2, '.', '');
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $settings = Setting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        $pdf = \PDF::loadView('pdf.Sales_Return_pdf',
            [
                'symbol' => $symbol,
                'setting' => $settings,
                'return_sale' => $return_details,
                'details' => $details,
            ]);
        return $pdf->download('Sales_Return.pdf');
    }

    //------------- Show Form Edit Sale Return-----------\\

    public function edit_sell_return(Request $request, $id, $sale_id)
    {

        $this->authorizeForUser($request->user('api'), 'update', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $SaleReturn = SaleReturn::with('sale','details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);
        $details = array();
        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === SaleReturn->id
            $this->authorizeForUser($request->user('api'), 'check_record', $SaleReturn);
        }
      
        $Return_detail['client_id'] = $SaleReturn->client_id;
        $Return_detail['warehouse_id'] = $SaleReturn->warehouse_id;
        $Return_detail['sale_id'] = $SaleReturn->sale_id?$SaleReturn['sale']->id:NULL;
        $Return_detail['sale_ref'] = $SaleReturn['sale']?$SaleReturn['sale']->Ref:'---';
        $Return_detail['date'] = $SaleReturn->date;
        $Return_detail['tax_rate'] = $SaleReturn->tax_rate;
        $Return_detail['TaxNet'] = $SaleReturn->TaxNet;
        $Return_detail['discount'] = $SaleReturn->discount;
        $Return_detail['shipping'] = $SaleReturn->shipping;
        $Return_detail['notes'] = $SaleReturn->notes;
        $Return_detail['statut'] = $SaleReturn->statut;

        $detail_id = 0;
        foreach ($SaleReturn['details'] as $detail) {

            //check if detail has sale_unit_id Or Null
            if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
                $data['no_unit'] = 1;
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                $data['no_unit'] = 0;
            }

            if ($detail->product_variant_id) {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('product_variant_id', $detail->product_variant_id)
                    ->where('deleted_at', '=', null)
                    ->where('warehouse_id', $SaleReturn->warehouse_id)
                    ->first();

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $item_product ? $data['del'] = 0 : $data['del'] = 1;
                $data['product_variant_id'] = $detail->product_variant_id;
                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

            } else {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $SaleReturn->warehouse_id)
                    ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                    ->first();

                $item_product ? $data['del'] = 0 : $data['del'] = 1;
                $data['product_variant_id'] = null;
                $data['code'] = $detail['product']['code'];

            }

            $data['id'] = $detail->id;
            $data['detail_id'] = $detail_id += 1;

            $sell_detail = SaleDetail::where('sale_id', $sale_id)
            ->where('product_id', $detail->product_id)
            ->where('product_variant_id', $detail->product_variant_id)
            ->first();

            $data['sale_quantity'] = $sell_detail->quantity;

            $data['quantity'] = $detail->quantity;
            $data['product_id'] = $detail->product_id;
            $data['name'] = $detail['product']['name'];
            $data['unitSale'] = $unit->ShortName;
            $data['sale_unit_id'] = $unit->id;
            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = $detail->discount;
            } else {
                $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = $detail->price;
            $data['tax_percent'] = $detail->TaxNet;
            $data['tax_method'] = $detail->tax_method;
            $data['discount'] = $detail->discount;
            $data['discount_Method'] = $detail->discount_method;

            if ($detail->tax_method == '1') {

                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = $tax_price;
                $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            }

            $details[] = $data;
        }

        return response()->json([
            'details' => $details,
            'sale_return' => $Return_detail,
        ]);
    }

     //-------------------Sms Notifications -----------------\\
     public function Send_SMS(Request $request)
     {
         $SaleReturn = SaleReturn::where('deleted_at', '=', null)->findOrFail($request->id);
         $settings = Setting::where('deleted_at', '=', null)->first();
         $gateway = sms_gateway::where('id' , $settings->sms_gateway)
         ->where('deleted_at', '=', null)->first();

         $url = url('/api/return_sale_pdf/' . $request->id);
         $receiverNumber = $SaleReturn['client']->phone;
         $message = "Dear" .' '.$SaleReturn['client']->name." \n We are contacting you in regard to a Sale Return #".$SaleReturn->Ref.' '.$url.' '. "that has been created on your account. \n We look forward to conducting future business with you.";
       
       
         //twilio
        if($gateway->title == "twilio"){
            try {
    
                $account_sid = env("TWILIO_SID");
                $auth_token = env("TWILIO_TOKEN");
                $twilio_number = env("TWILIO_FROM");
    
                $client = new Client_Twilio($account_sid, $auth_token);
                $client->messages->create($receiverNumber, [
                    'from' => $twilio_number, 
                    'body' => $message]);
        
            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

        //nexmo
        }elseif($gateway->title == "nexmo"){
            try {

                $basic  = new \Nexmo\Client\Credentials\Basic(env("NEXMO_KEY"), env("NEXMO_SECRET"));
                $client = new \Nexmo\Client($basic);
                $nexmo_from = env("NEXMO_FROM");
        
                $message = $client->message()->send([
                    'to' => $receiverNumber,
                    'from' => $nexmo_from,
                    'text' => $message
                ]);
                        
            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        }
    }

}
