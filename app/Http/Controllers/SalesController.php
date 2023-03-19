<?php

namespace App\Http\Controllers;
use Twilio\Rest\Client as Client_Twilio;
use App\Mail\SaleMail;
use App\Models\Client;
use App\Models\Unit;
use App\Models\PaymentSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\sms_gateway;
use App\Models\Role;
use App\Models\SaleReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\PosSetting;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Stripe;
use App\Models\PaymentWithCreditCard;
use DB;
use PDF;
use \Nwidart\Modules\Facades\Module;
use WooCommerce;
use \stdClass;

class SalesController extends BaseController
{

    //------------- GET ALL SALES -----------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Sale::class);
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
            6 => 'like',
        );
        $columns = array(
            0 => 'Ref',
            1 => 'statut',
            2 => 'client_id',
            3 => 'payment_statut',
            4 => 'warehouse_id',
            5 => 'date',
            6 => 'shipping_status',
        );
        $data = array();

        // Check If User Has Permission View  All Records
        $pos_Sales = Sale::with('facture', 'client', 'warehouse','user')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            });

        $Filtred_pos = $helpers->filter($pos_Sales, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('shipping_status', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        // echo json_encode($Filtred_pos);
        $pos_Sales = $Filtred_pos->orderBy($order, $dir)->get();
            // echo "---------------------------------------------------------------------------------";
        $Sales = $this->sale_list_woo();
        // echo json_encode($pos_Sales);

        $last_ref_id = (int)substr($pos_Sales[0]['Ref'],3) + count($Sales);

        foreach ($Sales as $Sale){
            $Sale->Ref = 'SL_' . $last_ref_id;
            $last_ref_id --;
        }

        if($request->sold_by != 'website'){
            if($request->sold_by == 'pos'){
                $Sales = array();
            }
            foreach ($pos_Sales as $Sale) {
                $item = new stdClass();
                $item->is_pos = true;
                $item->id = $Sale['id'];
                $item->date = $Sale['date'];
                $item->Ref = $Sale['Ref'];
                $item->created_by = $Sale['user']->username;
                $item->statut = $Sale['statut'];
                $item->shipping_status =  $Sale['shipping_status'];
                $item->discount = $Sale['discount'];
                $item->shipping = $Sale['shipping'];
                $item->warehouse_name = $Sale['warehouse']['name'];
                $item->client_id = $Sale['client']['id'];
                $item->client_name = $Sale['client']['name'];
                $item->client_email = $Sale['client']['email'];
                $item->client_tele = $Sale['client']['phone'];
                $item->client_code = $Sale['client']['code'];
                $item->client_adr = $Sale['client']['adresse'];
                $item->GrandTotal = number_format($Sale['GrandTotal'], 2, '.', '');
                $item->paid_amount = number_format($Sale['paid_amount'], 2, '.', '');
                $item->due = number_format($item->GrandTotal - $item->paid_amount, 2, '.', '');
                $item->payment_status = $Sale['payment_statut'];


                $item->salereturn_id = -1;
                if (SaleReturn::where('sale_id', $Sale['id'])->where('deleted_at', '=', null)->exists()) {
                    $sellReturn = SaleReturn::where('sale_id', $Sale['id'])->where('deleted_at', '=', null)->first();
                    $item->salereturn_id = $sellReturn->id;
                    $item->sale_has_return = 'yes';
                }else{
                    $item->sale_has_return = 'no';
                }
                
                $Sales[] = $item;
            }
        }

        //date filter
        $Filtred = array();
        foreach ($Sales as $Sale) {
            $sell_date = '';
            if(isset($Sale->is_pos)){
                $sell_date = $Sale->date;
            }else{
                $sell_date = substr($Sale->date_created,0,10);
            }

            if($request->from_date == ''){
                if($request->to_date == ''){
                    $Filtred[] = $Sale;
                }else{
                    if($sell_date <= $request->to_date){
                        $Filtred[] = $Sale;
                    }
                }
            }else{
                if($request->to_date == ''){
                    if($sell_date >= $request->from_date){
                        $Filtred[] = $Sale;
                    }
                }else{
                    if($sell_date >= $request->from_date && $sell_date <= $request->to_date){
                        $Filtred[] = $Sale;
                    }
                }
            }
        }

        //sku filter
        $SkuFiltred = array();
        foreach ($Filtred as $Sale) {
            $sell_sku = '';
            if(isset($Sale->is_pos)){
                if(isset($Sale->sale_id)){
                    $sale_detail = SaleDetail::where('sale_id', $Sale->id)->first();
                    
                    $product_id_sold = $sale_detail->product_id;

                    $product_sold = Product::where('id', $product_id_sold)
                            ->where('deleted_at', '=', null)
                            ->first();
                    $sell_sku = $product_sold->code;
                }
            }else{
                $sell_sku = $Sale->line_items[0]->sku;
            }
            if($request->sku == ''){
                $SkuFiltred[] = $Sale;
            }else{
                if($request->sku == 11018){
                    $SkuFiltred[] = $Sale;
                }
            }
        }

        $totalRows = count($SkuFiltred);
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Sales = array_slice($SkuFiltred,$offSet,$perPage);
        
        $item = array();
        foreach ($Sales as $Sale) {
            if(isset($Sale->is_pos)){
                if(isset($Sale->sale_id)){
                    $item['id'] = $Sale->id;

                    $sale_detail = SaleDetail::where('sale_id', $Sale->id)->first();
                    $product_id_sold = $sale_detail->product_id;
                    $product_sold = Product::where('id', $product_id_sold)
                            ->where('deleted_at', '=', null)
                            ->first();
                    $item['name'] = $product_sold->code;

                    $item['date'] = $Sale->date;
                    $item['Ref'] = $Sale->Ref;
                    $item['created_by'] = $Sale->created_by;
                    $item['statut'] = $Sale->statut;
                    $item['shipping_status'] =  $Sale->shipping_status;
                    $item['discount'] = $Sale->discount;
                    $item['shipping'] = $Sale->shipping;
                    $item['warehouse_name'] = $Sale->warehouse_name;
                    $item['client_id'] = $Sale->client_id;
                    $item['client_name'] = $Sale->client_name;
                    $item['client_email'] = $Sale->client_email;
                    $item['client_tele'] = $Sale->client_tele;
                    $item['client_code'] = $Sale->client_code;
                    $item['client_adr'] = $Sale->client_adr;
                    $item['GrandTotal'] = $Sale->GrandTotal;
                    $item['paid_amount'] = $Sale->paid_amount;
                    $item['due'] = $Sale->due;
                    $item['payment_status'] = $Sale->payment_status;
                    $item['salereturn_id'] = $Sale->salereturn_id;
                    $item['sale_has_return'] = $Sale->sale_has_return;
                    $item['is_pos'] = true;
                }
            }else{
                $item['id'] = $Sale->id;

                $products_sold = '';
                foreach($Sale->line_items as $line_item){
                    $products_sold .= $line_item->name;
                }
                $item['name'] = $products_sold;

                $item['date'] = substr($Sale->date_created,0,10);
                $item['Ref'] = $Sale->Ref;
                $item['created_by'] = 'WooCommerce';
                $item['statut'] = $Sale->status;
                $item['shipping_status'] =  $Sale->shipping->state;
                $item['discount'] = $Sale->discount_total;
                $item['shipping'] = 'shipping';
                $item['warehouse_name'] = 'warehouse';
                $item['client_id'] = $Sale->customer_id;
                
                $clientname = $Sale->billing->first_name . ' ' . $Sale->billing->last_name;

                $item['client_name'] = $clientname;
                $item['client_email'] = 'clientemail';
                $item['client_tele'] = 'clientphone';
                $item['client_code'] = 'clientcode';
                $item['client_adr'] = 'clientadresse';
                $item['GrandTotal'] = number_format($Sale->total, 2, '.', '');
                $item['paid_amount'] = number_format($Sale->total, 2, '.', '');
                $item['due'] = number_format($Sale->total - $Sale->total, 2, '.', '');
                $item['payment_status'] = 'paid';
                $item['sale_has_return'] = 'no';
                $item['is_pos'] = false;
            }
            $data[] = $item;
        }

        $stripe_key = config('app.STRIPE_KEY');
        $customers = client::where('deleted_at', '=', null)->get(['id', 'name']);

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }

        return response()->json([
            'stripe_key' => $stripe_key,
            'totalRows' => $totalRows,
            'sales' => $data,
            'customers' => $customers,
            'warehouses' => $warehouses,
        ]);
    }

    public function sale_list_woo(){
        $page = 1;
        $sales = [];
        $all_sales = [];
        do{
            try {
                $sales = WooCommerce::all('orders?per_page=100&page='.$page);
            }catch(HttpClientException $e){
            }
        $all_sales = array_merge($all_sales,$sales);
        $page++;
        } while (count($sales) > 0);
        return $all_sales;
    }

    public function sale_woo($id){
        $result = WooCommerce::find('orders/'.$id);
        return $result;
    } 

    public function customer_name_woo($id){
        $strId = strval($id);
        $result = WooCommerce::find('customers/'.$strId);
        return $result->first_name . ' ' . $result->last_name;
    } 

    //------------- STORE NEW SALE-----------\\

    public function store(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Sale::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',
        ]);

        \DB::transaction(function () use ($request) {
            $helpers = new helpers();
            $order = new Sale;

            $order->is_pos = 0;
            $order->date = $request->date;
            $order->Ref = $this->getNumberOrder();
            $order->client_id = $request->client_id;
            $order->GrandTotal = $request->GrandTotal;
            $order->warehouse_id = $request->warehouse_id;
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping;
            $order->statut = $request->statut;
            $order->payment_statut = 'unpaid';
            $order->notes = $request->notes;
            $order->user_id = Auth::user()->id;

            $order->save();

            $data = $request['details'];
            foreach ($data as $key => $value) {
                $unit = Unit::where('id', $value['sale_unit_id'])
                    ->first();
                $orderDetails[] = [
                    'date' => $request->date,
                    'sale_id' => $order->id,
                    'sale_unit_id' =>  $value['sale_unit_id'],
                    'quantity' => $value['quantity'],
                    'price' => $value['Unit_price'],
                    'TaxNet' => $value['tax_percent'],
                    'tax_method' => $value['tax_method'],
                    'discount' => $value['discount'],
                    'discount_method' => $value['discount_Method'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'total' => $value['subtotal'],
                    'imei_number' => $value['imei_number'],
                ];


                if ($order->statut == "completed") {
                    if ($value['product_variant_id'] !== null) {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->where('product_variant_id', $value['product_variant_id'])
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
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
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
            SaleDetail::insert($orderDetails);

            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');

            if ($request->payment['status'] != 'pending') {
                $sale = Sale::findOrFail($order->id);
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === sale->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $sale);
                }


                try {

                    $total_paid = $sale->paid_amount + $request['amount'];
                    $due = $sale->GrandTotal - $total_paid;
                    
                    if ($due === 0.0 || $due < 0.0) {
                        $payment_statut = 'paid';
                    } else if ($due != $sale->GrandTotal) {
                        $payment_statut = 'partial';
                    } else if ($due == $sale->GrandTotal) {
                        $payment_statut = 'unpaid';
                    }
                    
                    if($request['amount'] > 0){
                        if($request->payment['Reglement'] == 'credit card'){
                            $Client = Client::whereId($request->client_id)->first();
                            Stripe\Stripe::setApiKey(config('app.STRIPE_SECRET'));

                            $PaymentWithCreditCard = PaymentWithCreditCard::where('customer_id' ,$request->client_id)->first();
                            if(!$PaymentWithCreditCard){
                                // Create a Customer
                                $customer = \Stripe\Customer::create([
                                    'source' => $request->token,
                                    'email' => $Client->email, 
                                ]);

                                // Charge the Customer instead of the card:
                                $charge = \Stripe\Charge::create([
                                    'amount' => $request['amount'] * 100,
                                    'currency' => 'usd',
                                    'customer' => $customer->id,
                                ]);
                                $PaymentCard['customer_stripe_id'] =  $customer->id;

                            }else{
                                $customer_id = $PaymentWithCreditCard->customer_stripe_id;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $request['amount'] * 100,
                                    'currency' => 'usd',
                                    'customer' => $customer_id,
                                ]);
                                $PaymentCard['customer_stripe_id'] =  $customer_id;
                            }

                            $PaymentSale = new PaymentSale();
                            $PaymentSale->sale_id = $order->id;
                            $PaymentSale->Ref = app('App\Http\Controllers\PaymentSalesController')->getNumberOrder();
                            $PaymentSale->date = Carbon::now();
                            $PaymentSale->Reglement = $request->payment['Reglement'];
                            $PaymentSale->montant = $request['amount'];
                            $PaymentSale->change = $request['change'];
                            $PaymentSale->user_id = Auth::user()->id;
                            $PaymentSale->save();
        
                            $sale->update([
                                'paid_amount' => $total_paid,
                                'payment_statut' => $payment_statut,
                            ]);

                            $PaymentCard['customer_id'] = $request->client_id;
                            $PaymentCard['payment_id'] = $PaymentSale->id;
                            $PaymentCard['charge_id'] = $charge->id;
                            PaymentWithCreditCard::create($PaymentCard);

                        // Paying Method Cash
                        }else{

                            PaymentSale::create([
                                'sale_id' => $order->id,
                                'Ref' => app('App\Http\Controllers\PaymentSalesController')->getNumberOrder(),
                                'date' => Carbon::now(),
                                'Reglement' => $request->payment['Reglement'],
                                'montant' => $request['amount'],
                                'change' => $request['change'],
                                'user_id' => Auth::user()->id,
                            ]);

                            $sale->update([
                                'paid_amount' => $total_paid,
                                'payment_statut' => $payment_statut,
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
                
            }

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
    //------------- UPDATE SALE -----------

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Sale::class);

        request()->validate([
            'warehouse_id' => 'required',
            'client_id' => 'required',
        ]);

        \DB::transaction(function () use ($request, $id) {

            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $current_Sale = Sale::findOrFail($id);
            
            if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
            }else{
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === Sale->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
                }
                $old_sale_details = SaleDetail::where('sale_id', $id)->get();
                $new_sale_details = $request['details'];
                $length = sizeof($new_sale_details);

                // Get Ids for new Details
                $new_products_id = [];
                foreach ($new_sale_details as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                // Init Data with old Parametre
                $old_products_id = [];
                foreach ($old_sale_details as $key => $value) {
                    $old_products_id[] = $value->id;
                    
                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }

                    if($value['sale_unit_id'] !== null){
                        if ($current_Sale->statut == "completed") {

                            if ($value['product_variant_id'] !== null) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->where('product_variant_id', $value['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->first();
                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
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
                            $SaleDetail = SaleDetail::findOrFail($value->id);
                            $SaleDetail->delete();
                        }
                    }
                }

                // Update Data with New request
                foreach ($new_sale_details as $prd => $prod_detail) {
                    
                    if($prod_detail['no_unit'] !== 0){
                        $unit_prod = Unit::where('id', $prod_detail['sale_unit_id'])->first();

                        if ($request['statut'] == "completed") {

                            if ($prod_detail['product_variant_id'] !== null) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $request->warehouse_id)
                                    ->where('product_id', $prod_detail['product_id'])
                                    ->where('product_variant_id', $prod_detail['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($unit_prod->operator == '/') {
                                        $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                    } else {
                                        $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                    }
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $request->warehouse_id)
                                    ->where('product_id', $prod_detail['product_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($unit_prod->operator == '/') {
                                        $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                    } else {
                                        $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                    }
                                    $product_warehouse->save();
                                }
                            }

                            $product_warehouse_data = product_warehouse::where('product_id', $prod_detail['product_id'])
                                ->where('deleted_at', '=', null)
                                ->get();
                            $total_qty = 0;
                            foreach ($product_warehouse_data as $product_warehouse) {
                                $total_qty += $product_warehouse->qte;
                            }

                            $Product = Product::where('id', $prod_detail['product_id'])
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

                        $orderDetails['sale_id'] = $id;
                        $orderDetails['date'] = $request['date'];
                        $orderDetails['price'] = $prod_detail['Unit_price'];
                        $orderDetails['sale_unit_id'] = $prod_detail['sale_unit_id'];
                        $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                        $orderDetails['tax_method'] = $prod_detail['tax_method'];
                        $orderDetails['discount'] = $prod_detail['discount'];
                        $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                        $orderDetails['quantity'] = $prod_detail['quantity'];
                        $orderDetails['product_id'] = $prod_detail['product_id'];
                        $orderDetails['product_variant_id'] = $prod_detail['product_variant_id'];
                        $orderDetails['total'] = $prod_detail['subtotal'];
                        $orderDetails['imei_number'] = $prod_detail['imei_number'];

                        if (!in_array($prod_detail['id'], $old_products_id)) {
                            $orderDetails['date'] = Carbon::now();
                            $orderDetails['sale_unit_id'] = $unit_prod ? $unit_prod->id : Null;
                            SaleDetail::Create($orderDetails);
                        } else {
                            SaleDetail::where('id', $prod_detail['id'])->update($orderDetails);
                        }
                    }
                }

                $due = $request['GrandTotal'] - $current_Sale->paid_amount;
                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due != $request['GrandTotal']) {
                    $payment_statut = 'partial';
                } else if ($due == $request['GrandTotal']) {
                    $payment_statut = 'unpaid';
                }

                $current_Sale->update([
                    'date' => $request['date'],
                    'client_id' => $request['client_id'],
                    'warehouse_id' => $request['warehouse_id'],
                    'notes' => $request['notes'],
                    'statut' => $request['statut'],
                    'tax_rate' => $request['tax_rate'],
                    'TaxNet' => $request['TaxNet'],
                    'discount' => $request['discount'],
                    'shipping' => $request['shipping'],
                    'GrandTotal' => $request['GrandTotal'],
                    'payment_statut' => $payment_statut,
                ]);
            }

        }, 10);

        return response()->json(['success' => true]);
    }

    //------------- Remove SALE BY ID -----------\\

     public function destroy(Request $request, $id)
     {
         $this->authorizeForUser($request->user('api'), 'delete', Sale::class);
 
         \DB::transaction(function () use ($id, $request) {
             $role = Auth::user()->roles()->first();
             $view_records = Role::findOrFail($role->id)->inRole('record_view');
             $current_Sale = Sale::findOrFail($id);
             $old_sale_details = SaleDetail::where('sale_id', $id)->get();
             $shipment_data =  Shipment::where('sale_id', $id)->first();

             if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
            }else{
                
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === Sale->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
                }
                foreach ($old_sale_details as $key => $value) {
                    
                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }

                    if ($current_Sale->statut == "completed") {

                        if ($value['product_variant_id'] !== null) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
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

                if($shipment_data){
                    $shipment_data->delete();
                }
                $current_Sale->details()->delete();
                $current_Sale->update([
                    'deleted_at' => Carbon::now(),
                    'shipping_status' => NULL,
                ]);


                $Payment_Sale_data = PaymentSale::where('sale_id', $id)->get();
                foreach($Payment_Sale_data as $Payment_Sale){
                    if($Payment_Sale->Reglement == 'credit card') {
                        $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                        if($PaymentWithCreditCard){
                            $PaymentWithCreditCard->delete();
                        }
                    }
                    $Payment_Sale->delete();
                }
            }
 
         }, 10);
 
         return response()->json(['success' => true]);
     }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Sale::class);

        \DB::transaction(function () use ($request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $selectedIds = $request->selectedIds;
            $deleted = true;
            foreach ($selectedIds as $sale_id) {

                if (SaleReturn::where('sale_id', $sale_id)->where('deleted_at', '=', null)->exists()) {
                    return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
                }else{
                    $current_Sale = Sale::find($sale_id);
                    if($current_Sale){
                        $old_sale_details = SaleDetail::where('sale_id', $sale_id)->get();
                        $shipment_data =  Shipment::where('sale_id', $sale_id)->first();

                        // Check If User Has Permission view All Records
                        if (!$view_records) {
                            // Check If User->id === current_Sale->id
                            $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
                        }
                        foreach ($old_sale_details as $key => $value) {
                        
                            //check if detail has sale_unit_id Or Null
                            if($value['sale_unit_id'] !== null){
                                $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                            }else{
                                $product_unit_sale_id = Product::with('unitSale')
                                ->where('id', $value['product_id'])
                                ->first();
                                $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                            }
            
                            if ($current_Sale->statut == "completed") {
            
                                if ($value['product_variant_id'] !== null) {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $current_Sale->warehouse_id)
                                        ->where('product_id', $value['product_id'])
                                        ->where('product_variant_id', $value['product_variant_id'])
                                        ->first();
            
                                    if ($product_warehouse) {
                                        if ($old_unit->operator == '/') {
                                            $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                        } else {
                                            $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                        }
                                        $product_warehouse->save();
                                    }
            
                                } else {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $current_Sale->warehouse_id)
                                        ->where('product_id', $value['product_id'])
                                        ->first();
                                    if ($product_warehouse) {
                                        if ($old_unit->operator == '/') {
                                            $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                        } else {
                                            $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
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

                        if($shipment_data){
                            $shipment_data->delete();
                        }
                        
                        $current_Sale->details()->delete();
                        $current_Sale->update([
                            'deleted_at' => Carbon::now(),
                            'shipping_status' => NULL,
                        ]);


                        $Payment_Sale_data = PaymentSale::where('sale_id', $sale_id)->get();
                        foreach($Payment_Sale_data as $Payment_Sale){
                            if($Payment_Sale->Reglement == 'credit card') {
                                $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                                if($PaymentWithCreditCard){
                                    $PaymentWithCreditCard->delete();
                                }
                            }
                            $Payment_Sale->delete();
                        }
                    }else{
                        $deleted = false;
                    }
                }
            }

        }, 10);

        return response()->json(['success' => $deleted]);
    }

   
    //---------------- Get Details Sale-----------------\\

    public function show(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', Sale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->find($id);

        $details = array();

        if($sale_data){
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === sale->id
                $this->authorizeForUser($request->user('api'), 'check_record', $sale_data);
            }

            $sale_details['Ref'] = $sale_data->Ref;
            $sale_details['date'] = $sale_data->date;
            $sale_details['note'] = $sale_data->notes;
            $sale_details['statut'] = $sale_data->statut;
            $sale_details['warehouse'] = $sale_data['warehouse']->name;
            $sale_details['discount'] = $sale_data->discount;
            $sale_details['shipping'] = $sale_data->shipping;
            $sale_details['tax_rate'] = $sale_data->tax_rate;
            $sale_details['TaxNet'] = $sale_data->TaxNet;
            $sale_details['client_name'] = $sale_data['client']->name;
            $sale_details['client_phone'] = $sale_data['client']->phone;
            $sale_details['client_adr'] = $sale_data['client']->adresse;
            $sale_details['client_email'] = $sale_data['client']->email;
            $sale_details['client_tax'] = $sale_data['client']->tax_number;
            $sale_details['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
            $sale_details['paid_amount'] = number_format($sale_data->paid_amount, 2, '.', '');
            $sale_details['due'] = number_format($sale_details['GrandTotal'] - $sale_details['paid_amount'], 2, '.', '');
            $sale_details['payment_status'] = $sale_data->payment_statut;

            if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                $sellReturn = SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->first();
                $sale_details['salereturn_id'] = $sellReturn->id;
                $sale_details['sale_has_return'] = 'yes';
            }else{
                $sale_details['sale_has_return'] = 'no';
            }

            foreach ($sale_data['details'] as $detail) {

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
        }else{
            $sale_data = $this->sale_woo($id);

            $sale_details['Ref'] = ($id);
            $sale_details['date'] = substr($sale_data->date_created,0,10);
            $sale_details['note'] = 'note';
            $sale_details['statut'] = $sale_data->status;
            $sale_details['warehouse'] = 'warehouse';
            $sale_details['discount'] = (int)$sale_data->discount_total;
            $sale_details['shipping'] = 0;
            $sale_details['tax_rate'] = (int)$sale_data->total_tax;
            $sale_details['TaxNet'] = (int)$sale_data->total_tax;
            $clientname = $sale_data->billing->first_name . ' ' . $sale_data->billing->last_name;
            $sale_details['client_name'] = $clientname;
            $sale_details['client_phone'] = $sale_data->billing->phone;
            $sale_details['client_adr'] = $sale_data->billing->address_1;
            $sale_details['client_email'] = $sale_data->billing->email;
            $sale_details['client_tax'] = '';
            $sale_details['GrandTotal'] = number_format($sale_data->total, 2, '.', '');
            $sale_details['paid_amount'] = number_format($sale_data->total, 2, '.', '');
            $sale_details['due'] = number_format($sale_details['GrandTotal'] - $sale_details['paid_amount'], 2, '.', '');
            $sale_details['payment_status'] = 'paid';

            if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                $sellReturn = SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->first();
                $sale_details['salereturn_id'] = $sellReturn->id;
                $sale_details['sale_has_return'] = 'yes';
            }else{
                $sale_details['sale_has_return'] = 'no';
            }

            // foreach ($sale_data->details as $detail) {

            //     //check if detail has sale_unit_id Or Null
            //     if($detail->sale_unit_id !== null){
            //         $unit = Unit::where('id', $detail->sale_unit_id)->first();
            //     }else{
            //         $product_unit_sale_id = Product::with('unitSale')
            //         ->where('id', $detail->product_id)
            //         ->first();
            //         $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            //     }

            //     if ($detail->product_variant_id) {

            //         $productsVariants = ProductVariant::where('product_id', $detail->product_id)
            //             ->where('id', $detail->product_variant_id)->first();

            //         $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

            //     } else {
            //         $data['code'] = $detail['product']['code'];
            //     }

            //     $data['quantity'] = $detail->quantity;
            //     $data['total'] = $detail->total;
            //     $data['name'] = $detail['product']['name'];
            //     $data['price'] = $detail->price;
            //     $data['unit_sale'] = $unit->ShortName;

            //     if ($detail->discount_method == '2') {
            //         $data['DiscountNet'] = $detail->discount;
            //     } else {
            //         $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            //     }

            //     $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            //     $data['Unit_price'] = $detail->price;
            //     $data['discount'] = $detail->discount;

            //     if ($detail->tax_method == '1') {
            //         $data['Net_price'] = $detail->price - $data['DiscountNet'];
            //         $data['taxe'] = $tax_price;
            //     } else {
            //         $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
            //         $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            //     }

            //     $data['is_imei'] = $detail['product']['is_imei'];
            //     $data['imei_number'] = $detail->imei_number;

            //     $details[] = $data;
            // }
        }

        $company = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'details' => $details,
            'sale' => $sale_details,
            'company' => $company,
        ]);

    }

    //-------------- Print Invoice ---------------\\

    public function Print_Invoice_POS(Request $request, $id)
    {
        $helpers = new helpers();
        $details = array();

        $sale = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $item['id'] = $sale->id;
        $item['Ref'] = $sale->Ref;
        $item['date'] = $sale->date;
        $item['discount'] = number_format($sale->discount, 2, '.', '');
        $item['shipping'] = number_format($sale->shipping, 2, '.', '');
        $item['taxe'] =     number_format($sale->TaxNet, 2, '.', '');
        $item['tax_rate'] = $sale->tax_rate;
        $item['client_name'] = $sale['client']->name;
        $item['GrandTotal'] = number_format($sale->GrandTotal, 2, '.', '');
        $item['paid_amount'] = number_format($sale->paid_amount, 2, '.', '');

        foreach ($sale['details'] as $detail) {

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
                    $data['name'] = $productsVariants->name . '-' . $detail['product']['name'];
                    
                } else {
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                }
                
           
            $data['quantity'] = number_format($detail->quantity, 2, '.', '');
            $data['total'] = number_format($detail->total, 2, '.', '');
            $data['unit_sale'] = $unit->ShortName;

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $settings = Setting::where('deleted_at', '=', null)->first();
        $pos_settings = PosSetting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        return response()->json([
            'symbol' => $symbol,
            'payments' => $payments,
            'setting' => $settings,
            'pos_settings' => $pos_settings,
            'sale' => $item,
            'details' => $details,
        ]);

    }

    //------------- GET PAYMENTS SALE -----------\\

    public function Payments_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', PaymentSale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Sale = Sale::findOrFail($id);

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Sale->id
            $this->authorizeForUser($request->user('api'), 'check_record', $Sale);
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })->orderBy('id', 'DESC')->get();

        $due = $Sale->GrandTotal - $Sale->paid_amount;

        return response()->json(['payments' => $payments, 'due' => $due]);

    }

    //------------- Reference Number Order SALE -----------\\

    public function getNumberOrder()
    {

        $last = DB::table('sales')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'SL_1111';
        }
        return $code;
    }

    //------------- SALE PDF -----------\\

    public function Sale_PDF(Request $request, $id)
    {

        $details = array();
        $helpers = new helpers();
        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->find($id);

        if($sale_data){
            
            $sale['client_name'] = $sale_data['client']->name;
            $sale['client_phone'] = $sale_data['client']->phone;
            $sale['client_adr'] = $sale_data['client']->adresse;
            $sale['client_email'] = $sale_data['client']->email;
            $sale['client_tax'] = $sale_data['client']->tax_number;
            $sale['TaxNet'] = number_format($sale_data->TaxNet, 2, '.', '');
            $sale['discount'] = number_format($sale_data->discount, 2, '.', '');
            $sale['shipping'] = number_format($sale_data->shipping, 2, '.', '');
            $sale['statut'] = $sale_data->statut;
            $sale['Ref'] = $sale_data->Ref;
            $sale['date'] = $sale_data->date;
            $sale['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
            $sale['paid_amount'] = number_format($sale_data->paid_amount, 2, '.', '');
            $sale['due'] = number_format($sale['GrandTotal'] - $sale['paid_amount'], 2, '.', '');
            $sale['payment_status'] = $sale_data->payment_statut;

            $detail_id = 0;
            foreach ($sale_data['details'] as $detail) {

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
                $data['discount'] = number_format($detail->discount, 2, '.', '');

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
        }else{
            $sale_data = $this->sale_woo($id);

            $sale['Ref'] = 'ref';
            $sale['date'] = substr($sale_data->date_created,0,10);
            $sale['note'] = 'note';
            $sale['statut'] = $sale_data->status;
            $sale['warehouse'] = 'warehouse';
            $sale['discount'] = (int)$sale_data->discount_total;
            $sale['shipping'] = 0;
            $sale['tax_rate'] = (int)$sale_data->total_tax;
            $sale['TaxNet'] = (int)$sale_data->total_tax;
            $clientname = $sale_data->billing->first_name . ' ' . $sale_data->billing->last_name;
            $sale['client_name'] = $clientname;
            $sale['client_phone'] = $sale_data->billing->phone;
            $sale['client_adr'] = $sale_data->billing->address_1;
            $sale['client_email'] = $sale_data->billing->email;
            $sale['client_tax'] = '';
            $sale['GrandTotal'] = number_format($sale_data->total, 2, '.', '');
            $sale['paid_amount'] = number_format($sale_data->total, 2, '.', '');
            $sale['due'] = number_format($sale['GrandTotal'] - $sale['paid_amount'], 2, '.', '');
            $sale['payment_status'] = $sale_data->status;

            $detail_id = 0;

            // foreach ($sale_data->details as $detail) {

            //     //check if detail has sale_unit_id Or Null
            //     if($detail->sale_unit_id !== null){
            //         $unit = Unit::where('id', $detail->sale_unit_id)->first();
            //     }else{
            //         $product_unit_sale_id = Product::with('unitSale')
            //         ->where('id', $detail->product_id)
            //         ->first();
            //         $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            //     }

            //     if ($detail->product_variant_id) {

            //         $productsVariants = ProductVariant::where('product_id', $detail->product_id)
            //             ->where('id', $detail->product_variant_id)->first();

            //         $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

            //     } else {
            //         $data['code'] = $detail['product']['code'];
            //     }

            //     $data['quantity'] = $detail->quantity;
            //     $data['total'] = $detail->total;
            //     $data['name'] = $detail['product']['name'];
            //     $data['price'] = $detail->price;
            //     $data['unit_sale'] = $unit->ShortName;

            //     if ($detail->discount_method == '2') {
            //         $data['DiscountNet'] = $detail->discount;
            //     } else {
            //         $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            //     }

            //     $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            //     $data['Unit_price'] = $detail->price;
            //     $data['discount'] = $detail->discount;

            //     if ($detail->tax_method == '1') {
            //         $data['Net_price'] = $detail->price - $data['DiscountNet'];
            //         $data['taxe'] = $tax_price;
            //     } else {
            //         $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
            //         $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            //     }

            //     $data['is_imei'] = $detail['product']['is_imei'];
            //     $data['imei_number'] = $detail->imei_number;

            //     $details[] = $data;
            // }
        }
        $settings = Setting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        $pdf = \PDF::loadView('pdf.sale_pdf', [
            'symbol' => $symbol,
            'setting' => $settings,
            'sale' => $sale,
            'details' => $details,
        ]);

        return $pdf->download('Sale.pdf');

    }

    //----------------Show Form Create Sale ---------------\\

    public function create(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Sale::class);

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }

        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $stripe_key = config('app.STRIPE_KEY');

        return response()->json([
            'stripe_key' => $stripe_key,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);

    }

      //------------- Show Form Edit Sale -----------\\

      public function edit(Request $request, $id)
      {
        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            //if the sale is from woo
            //get woo values from woo sale list

            return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
        }else{
          $this->authorizeForUser($request->user('api'), 'update', Sale::class);
          $role = Auth::user()->roles()->first();
          $view_records = Role::findOrFail($role->id)->inRole('record_view');
          $Sale_data = Sale::with('details.product.unitSale')
              ->where('deleted_at', '=', null)
              ->find($id);
          if($Sale_data){
            $details = array();
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === sale->id
                $this->authorizeForUser($request->user('api'), 'check_record', $Sale_data);
            }
    
            if ($Sale_data->client_id) {
                if (Client::where('id', $Sale_data->client_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $sale['client_id'] = $Sale_data->client_id;
                } else {
                    $sale['client_id'] = '';
                }
            } else {
                $sale['client_id'] = '';
            }
    
            if ($Sale_data->warehouse_id) {
                if (Warehouse::where('id', $Sale_data->warehouse_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $sale['warehouse_id'] = $Sale_data->warehouse_id;
                } else {
                    $sale['warehouse_id'] = '';
                }
            } else {
                $sale['warehouse_id'] = '';
            }
    
            $sale['date'] = $Sale_data->date;
            $sale['tax_rate'] = $Sale_data->tax_rate;
            $sale['TaxNet'] = $Sale_data->TaxNet;
            $sale['discount'] = $Sale_data->discount;
            $sale['shipping'] = $Sale_data->shipping;
            $sale['statut'] = $Sale_data->statut;
            $sale['notes'] = $Sale_data->notes;
    
            $detail_id = 0;
            foreach ($Sale_data['details'] as $detail) {

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
                        ->where('deleted_at', '=', null)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Sale_data->warehouse_id)
                        ->first();
    
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->first();
    
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    
                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
    
                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('deleted_at', '=', null)->where('warehouse_id', $Sale_data->warehouse_id)
                        ->where('product_variant_id', '=', null)->first();
    
                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                        } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
    
                    }
                    
                    $data['id'] = $detail->id;
                    $data['detail_id'] = $detail_id += 1;
                    $data['product_id'] = $detail->product_id;
                    $data['total'] = $detail->total;
                    $data['name'] = $detail['product']['name'];
                    $data['quantity'] = $detail->quantity;
                    $data['qte_copy'] = $detail->quantity;
                    $data['etat'] = 'current';
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

            //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

          $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
          return response()->json([
            'details' => $details,
            'sale' => $sale,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);

        }else{
            $Sale_data = $this->show_woo($id);
            $details = array();
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === sale->id
                $this->authorizeForUser($request->user('api'), 'check_record', $Sale_data);
            }
    
            if ($Sale_data->customer_id) {
                if ($this->show_woo_customer($Sale_data->customer_id)) {
                    $sale['client_id'] = $Sale_data->customer_id;
                } else {
                    $sale['client_id'] = '';
                }
            } else {
                $sale['client_id'] = '';
            }
    
            // if ($Sale_data->warehouse_id) {
            //     if (Warehouse::where('id', $Sale_data->warehouse_id)
            //         ->where('deleted_at', '=', null)
            //         ->first()) {
            //         $sale['warehouse_id'] = $Sale_data->warehouse_id;
            //     } else {
            //         $sale['warehouse_id'] = '';
            //     }
            // } else {
            //     $sale['warehouse_id'] = '';
            // }
            $sale['warehouse_id'] = '';

    
            $sale['date'] = $Sale_data->date_paid;
            $sale['tax_rate'] = $Sale_data->total_tax;
            $sale['TaxNet'] = $Sale_data->total_tax;
            $sale['discount'] = $Sale_data->discount_total;
            $sale['shipping'] = $Sale_data->shipping_total;
            $sale['statut'] = $Sale_data->status;
            $sale['notes'] = $Sale_data->customer_note;
    
            $detail_id = 0;
            // foreach ($Sale_data['details'] as $detail) {

            //         //check if detail has sale_unit_id Or Null
            //         if($detail->sale_unit_id !== null){
            //             $unit = Unit::where('id', $detail->sale_unit_id)->first();
            //             $data['no_unit'] = 1;
            //         }else{
            //             $product_unit_sale_id = Product::with('unitSale')
            //             ->where('id', $detail->product_id)
            //             ->first();
            //             $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            //             $data['no_unit'] = 0;
            //         }
            
            //     if ($detail->product_variant_id) {
            //         $item_product = product_warehouse::where('product_id', $detail->product_id)
            //             ->where('deleted_at', '=', null)
            //             ->where('product_variant_id', $detail->product_variant_id)
            //             ->where('warehouse_id', $Sale_data->warehouse_id)
            //             ->first();
    
            //         $productsVariants = ProductVariant::where('product_id', $detail->product_id)
            //             ->where('id', $detail->product_variant_id)->first();
    
            //         $item_product ? $data['del'] = 0 : $data['del'] = 1;
            //         $data['product_variant_id'] = $detail->product_variant_id;
            //         $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    
            //         if ($unit && $unit->operator == '/') {
            //             $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
            //         } else if ($unit && $unit->operator == '*') {
            //             $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
            //         } else {
            //             $data['stock'] = 0;
            //         }
    
            //     } else {
            //         $item_product = product_warehouse::where('product_id', $detail->product_id)
            //             ->where('deleted_at', '=', null)->where('warehouse_id', $Sale_data->warehouse_id)
            //             ->where('product_variant_id', '=', null)->first();
    
            //         $item_product ? $data['del'] = 0 : $data['del'] = 1;
            //         $data['product_variant_id'] = null;
            //         $data['code'] = $detail['product']['code'];

            //         if ($unit && $unit->operator == '/') {
            //             $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
            //             } else if ($unit && $unit->operator == '*') {
            //             $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
            //         } else {
            //             $data['stock'] = 0;
            //         }
    
            //         }
                    
            //         $data['id'] = $detail->id;
            //         $data['detail_id'] = $detail_id += 1;
            //         $data['product_id'] = $detail->product_id;
            //         $data['total'] = $detail->total;
            //         $data['name'] = $detail['product']['name'];
            //         $data['quantity'] = $detail->quantity;
            //         $data['qte_copy'] = $detail->quantity;
            //         $data['etat'] = 'current';
            //         $data['unitSale'] = $unit->ShortName;
            //         $data['sale_unit_id'] = $unit->id;
            //         $data['is_imei'] = $detail['product']['is_imei'];
            //         $data['imei_number'] = $detail->imei_number;

            //         if ($detail->discount_method == '2') {
            //             $data['DiscountNet'] = $detail->discount;
            //         } else {
            //             $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            //         }

            //         $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            //         $data['Unit_price'] = $detail->price;
                    
            //         $data['tax_percent'] = $detail->TaxNet;
            //         $data['tax_method'] = $detail->tax_method;
            //         $data['discount'] = $detail->discount;
            //         $data['discount_Method'] = $detail->discount_method;

            //         if ($detail->tax_method == '1') {
            //             $data['Net_price'] = $detail->price - $data['DiscountNet'];
            //             $data['taxe'] = $tax_price;
            //             $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            //         } else {
            //             $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
            //             $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            //             $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            //         }

            //     $details[] = $data;
            // }

            //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

          $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
          return response()->json([
            'details' => $details,
            'sale' => $sale,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);
        }
        
         
  
          
        }
  
      }

      public function show_woo($id){
        $result = WooCommerce::find('orders/'.$id);
        return $result;
    }

    public function show_woo_customer($id){
        $result = WooCommerce::find('customers/'.$id);
        return $result;
    }

    //------------- SEND SALE TO EMAIL -----------\\

    public function Send_Email(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Sale::class);

        $sale['id'] = $request->id;
        $sale['Ref'] = $request->Ref;
        $settings = Setting::where('deleted_at', '=', null)->first();
        $sale['company_name'] = $settings->CompanyName;
        $pdf = $this->Sale_PDF($request, $sale['id']);
        $this->Set_config_mail(); // Set_config_mail => BaseController
        $mail = Mail::to($request->to)->send(new SaleMail($sale, $pdf));
        return $mail;
    }

    //------------- Show Form Convert To Sale -----------\\

    public function Elemens_Change_To_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', Quotation::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Quotation = Quotation::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);
        $details = array();
        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Quotation->id
            $this->authorizeForUser($request->user('api'), 'check_record', $Quotation);
        }

        if ($Quotation->client_id) {
            if (Client::where('id', $Quotation->client_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['client_id'] = $Quotation->client_id;
            } else {
                $sale['client_id'] = '';
            }
        } else {
            $sale['client_id'] = '';
        }

        if ($Quotation->warehouse_id) {
            if (Warehouse::where('id', $Quotation->warehouse_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['warehouse_id'] = $Quotation->warehouse_id;
            } else {
                $sale['warehouse_id'] = '';
            }
        } else {
            $sale['warehouse_id'] = '';
        }

        $sale['date'] = $Quotation->date;
        $sale['TaxNet'] = $Quotation->TaxNet;
        $sale['tax_rate'] = $Quotation->tax_rate;
        $sale['discount'] = $Quotation->discount;
        $sale['shipping'] = $Quotation->shipping;
        $sale['statut'] = 'pending';
        $sale['notes'] = $Quotation->notes;

        $detail_id = 0;
        foreach ($Quotation['details'] as $detail) {
           
                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('deleted_at', '=', null)
                        ->first();
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->where('deleted_at', null)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                
                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('product_variant_id', '=', null)
                        ->where('deleted_at', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
                }
                
                $data['id'] = $id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['total'] = $detail->total;
                $data['name'] = $detail['product']['name'];
                $data['etat'] = 'current';
                $data['qte_copy'] = $detail->quantity;
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
        }

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }
         
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'details' => $details,
            'sale' => $sale,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);

    }

    //-------------------Sms Notifications -----------------\\

    public function Send_SMS(Request $request)
    {
        $sale = Sale::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);
        $settings = Setting::where('deleted_at', '=', null)->first();
        $gateway = sms_gateway::where('id' , $settings->sms_gateway)
        ->where('deleted_at', '=', null)->first();

        $url = url('/api/sale_pdf/' . $request->id);

        if($sale){
            $receiverNumber = $sale['client']->phone;
            $message = "Dear" .' '.$sale['client']->name." \n We are contacting you in regard to a invoice #".$sale->Ref.' '.$url.' '. "that has been created on your account. \n We look forward to conducting future business with you.";
        }else{
            $sale_data = $this->sale_woo($request->id);
            $clientname = $sale_data->billing->first_name . ' ' . $sale_data->billing->last_name;
            $sale['client_name'] = $clientname;
            $sale['client_phone'] = $sale_data->billing->phone;
            $receiverNumber = $sale['client_phone'];
            $message = "Dear" .' '.$sale['client_name']." \n We are contacting you in regard to a invoice #... that has been created on your account. \n We look forward to conducting future business with you.";
        }

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



    //------------------- get_Products_by_sale -----------------\\

    public function get_Products_by_sale(Request $request , $id)
    {

        $this->authorizeForUser($request->user('api'), 'create', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $SaleReturn = Sale::with('details.product.unitSale')
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
        $Return_detail['sale_id'] = $SaleReturn->id;
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

                if ($unit && $unit->operator == '/') {
                    $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                } else if ($unit && $unit->operator == '*') {
                    $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                } else {
                    $data['stock'] = 0;
                }

            } else {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $SaleReturn->warehouse_id)
                    ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                    ->first();

                $item_product ? $data['del'] = 0 : $data['del'] = 1;
                $data['product_variant_id'] = null;
                $data['code'] = $detail['product']['code'];

                if ($unit && $unit->operator == '/') {
                    $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                } else if ($unit && $unit->operator == '*') {
                    $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                } else {
                    $data['stock'] = 0;
                }

            }

            $data['id'] = $detail->id;
            $data['detail_id'] = $detail_id += 1;
            $data['quantity'] = $detail->quantity;
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


        return response()->json([
            'details' => $details,
            'sale_return' => $Return_detail,
        ]);

    }

}
