<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\PaymentSale;
use App\Models\Product;
use App\Models\Setting;
use App\Models\PosSetting;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\PaymentWithCreditCard;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\SaleDetail;
use App\Models\Warehouse;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe;
use WooCommerce;

class PosController extends BaseController
{

    //------------ Create New  POS --------------\\

    public function CreatePOS(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',
            'payment.amount' => 'required',
        ]);

        $item = \DB::transaction(function () use ($request) {
            $helpers = new helpers();
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $order = new Sale;

            $order->is_pos = 1;
            $order->date = Carbon::now();
            $order->Ref = app('App\Http\Controllers\SalesController')->getNumberOrder();
            $order->client_id = $request->client_id;
            $order->warehouse_id = $request->warehouse_id;
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping;
            $order->GrandTotal = $request->GrandTotal;
            $order->notes = $request->notes;
            $order->statut = 'completed';
            $order->payment_statut = 'unpaid';
            $order->user_id = Auth::user()->id;

            $order->save();

            $data = $request['details'];
            foreach ($data as $key => $value) {

                $unit = Unit::where('id', $value['sale_unit_id'])
                    ->first();
                $orderDetails[] = [
                    'date' => Carbon::now(),
                    'sale_id' => $order->id,
                    'sale_unit_id' =>  $value['sale_unit_id'],
                    'quantity' => $value['quantity'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'total' => $value['subtotal'],
                    'price' => $value['Unit_price'],
                    'TaxNet' => $value['tax_percent'],
                    'tax_method' => $value['tax_method'],
                    'discount' => $value['discount'],
                    'discount_method' => $value['discount_Method'],
                    'imei_number' => $value['imei_number'],
                ];
        
                if ($value['product_variant_id'] !== null) {
                    $product_warehouse = product_warehouse::where('warehouse_id', $order->warehouse_id)
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
                    $product_warehouse = product_warehouse::where('warehouse_id', $order->warehouse_id)
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

            SaleDetail::insert($orderDetails);

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

                    PaymentSale::create([
                        'sale_id' => $order->id,
                        'Ref' => app('App\Http\Controllers\PaymentSalesController')->getNumberOrder(),
                        'date' => Carbon::now(),
                        'Reglement' => $request->payment['Reglement'] == 'credit card'?'Cash':$request->payment['Reglement'] == 'credit card',
                        'montant' => $request['amount'],
                        'change' => $request['change'],
                        'notes' => $request->payment['notes'],
                        'user_id' => Auth::user()->id,
                    ]);

                    $sale->update([
                        'paid_amount' => $total_paid,
                        'payment_statut' => $payment_statut,
                    ]);

                }
              
            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return $order->id;

        }, 10);

        $data = $request['details'];
        foreach ($data as $key => $v) {
            $parent = Product::where('parent_code', $v['code'])->first();
            if (isset($parent->id)) {
                $child_quantity = product_warehouse::where('product_id', $v['product_id'])
                    ->where('deleted_at', '=', null)
                    ->get();

                foreach ($child_quantity as $key => $value) {
                    product_warehouse::where("warehouse_id", $value->warehouse_id)
                        ->where('product_id', $parent->id)
                        ->update(["qte" => $value->qte]);
                }
            }
            

        }
        
        return response()->json(['success' => true, 'id' => $item]);

    }

    public function update_product_woo($id, $data){
        $result = WooCommerce::update('products/'.$id, $data);
        return $result;
    }

    public function update_product_variation_woo($product_id, $product_variation_id, $data){
        $result = WooCommerce::update('products/'.$product_id.'/'.'variations/'.$product_variation_id, $data);
        return $result;
    }
    //------------ Get Products--------------\\

    public function GetProductsByParametre(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);
        // How many items do you want to display.
        $perPage = 8;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $product_warehouse_data = product_warehouse::where('warehouse_id', $request->warehouse_id)
            ->with('product', 'product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                return $query->whereHas('product', function ($q) use ($request) {
                    $q->where('not_selling', '=', 0);
                })
                ->where(function ($query) use ($request) {
                    if ($request->stock == '1') {
                        return $query->where('qte', '>', 0);
                    }
                });
            })

        // Filter
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('category_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('category_id', '=', $request->category_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('brand_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('brand_id', '=', $request->brand_id);
                    });
                });
            });

        $totalRows = $product_warehouse_data->count();

        $product_warehouse_data = $product_warehouse_data
            ->offset($offSet)
            ->limit(8)
            ->get();

        foreach ($product_warehouse_data as $product_warehouse) {
            if ($product_warehouse->product_variant_id) {
                $productsVariants = ProductVariant::where('product_id', $product_warehouse->product_id)
                    ->where('id', $product_warehouse->product_variant_id)
                    ->where('deleted_at', null)
                    ->first();

                $item['product_variant_id'] = $product_warehouse->product_variant_id;
                $item['Variant'] = $productsVariants->name;
                $item['code'] = $productsVariants->name . '-' . $product_warehouse['product']->code;

            } else if ($product_warehouse->product_variant_id === null) {
                $item['product_variant_id'] = null;
                $item['Variant'] = null;
                $item['code'] = $product_warehouse['product']->code;
            }
            $item['id'] = $product_warehouse->product_id;
            $item['barcode'] = $product_warehouse['product']->code;
            $item['name'] = $product_warehouse['product']->name;
            $firstimage = explode(',', $product_warehouse['product']->image);
            $item['image'] = $firstimage[0];

            if ($product_warehouse['product']['unitSale']->operator == '/') {
                $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price / $product_warehouse['product']['unitSale']->operator_value;

            } else {
                $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price * $product_warehouse['product']['unitSale']->operator_value;

            }
            $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
            $item['qte'] = $product_warehouse->qte;

            if ($product_warehouse['product']->TaxNet !== 0.0) {

                //Exclusive
                if ($product_warehouse['product']->tax_method == '1') {
                    $tax_price = $price * $product_warehouse['product']->TaxNet / 100;

                    $item['Net_price'] = $price + $tax_price;

                    // Inxclusive
                } else {
                    $item['Net_price'] = $price;
                }
            } else {
                $item['Net_price'] = $price;
            }

            $data[] = $item;
        }

        return response()->json([
            'products' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    // public function GetProductsByParametre(request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);
    //     // How many items do you want to display.
    //     $perPage = 8;
    //     $pageStart = \Request::get('page', 1);
    //     // Start displaying items from this number;
    //     $offSet = ($pageStart * $perPage) - $perPage;
    //     $data = array();

    //     $products = $this->product_list_woo();
    //     // $product = $this->show_woo(9559);
    //     // $products = [{'name': '123'},{'name': '234'},{'name': '345'},{'name': '456'}];

    //     //Multiple Filter
    //     // $Filtred = $helpers->filter($products, $columns, $param, $request)
    //     // // Search With Multiple Param
    //     //     ->where(function ($query) use ($request) {
    //     //         return $query->when($request->filled('search'), function ($query) use ($request) {
    //     //             return $query->where('products.name', 'LIKE', "%{$request->search}%")
    //     //                 ->orWhere('products.code', 'LIKE', "%{$request->search}%")
    //     //                 ->orWhere(function ($query) use ($request) {
    //     //                     return $query->whereHas('category', function ($q) use ($request) {
    //     //                         $q->where('name', 'LIKE', "%{$request->search}%");
    //     //                     });
    //     //                 })
    //     //                 ->orWhere(function ($query) use ($request) {
    //     //                     return $query->whereHas('brand', function ($q) use ($request) {
    //     //                         $q->where('name', 'LIKE', "%{$request->search}%");
    //     //                     });
    //     //                 });
    //     //         });
    //     //     });
    //     $Filtred = $products;

    //     $totalRows = count($Filtred);
    //     if($perPage == "-1"){
    //         $perPage = $totalRows;
    //     }
    //     $products = array_slice($Filtred,$offSet,$perPage);

    //     foreach ($products as $product) {

    //         $item['product_variant_id'] = null;
    //         $item['Variant'] = null;
    //         $item['code'] = $product->sku;
    //         $item['id'] = $product->id;
    //         $item['barcode'] = $product->sku;
    //         $item['name'] = $product->name;
    //         // $firstimage = explode(',', $product->image);
    //         $item['image'] = 'img';
    //         $item['qte_sale'] = $product->price;
    //         $item['unitSale'] = 'unitSale';
    //         $item['qte'] = 'qte';
    //         $item['Net_price'] = $product->price;

    //         $data[] = $item;
    //     }

    //     return response()->json([
    //         'products' => $data,
    //         'totalRows' => $totalRows,
    //     ]);
    // }

    public function product_list_woo(){
        $page = 1;
        $products = [];
        $all_products = [];
        do{
            try {
                $products = WooCommerce::all('products?per_page=100&page='.$page);
            }catch(HttpClientException $e){
            }
        $all_products = array_merge($all_products,$products);
        $page++;
        } while (count($products) > 0);
        return $all_products;
    }

    //--------------------- Get Element POS ------------------------\\

    public function GetELementPos(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $settings = Setting::where('deleted_at', '=', null)->first();

          //get warehouses assigned to user
          $user_auth = auth()->user();
          if($user_auth->is_all_warehouses){
             $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

             if ($settings->warehouse_id) {
                if (Warehouse::where('id', $settings->warehouse_id)->where('deleted_at', '=', null)->first()) {
                    $defaultWarehouse = $settings->warehouse_id;
                } else {
                    $defaultWarehouse = '';
                }
            } else {
                $defaultWarehouse = '';
            }

          }else{
             $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
             $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);

             if ($settings->warehouse_id) {
                if (Warehouse::where('id', $settings->warehouse_id)->whereIn('id', $warehouses_id)->where('deleted_at', '=', null)->first()) {
                    $defaultWarehouse = $settings->warehouse_id;
                } else {
                    $defaultWarehouse = '';
                }
            } else {
                $defaultWarehouse = '';
            }
          }


      
        

        if ($settings->client_id) {
            if (Client::where('id', $settings->client_id)->where('deleted_at', '=', null)->first()) {
                $defaultClient = $settings->client_id;
            } else {
                $defaultClient = '';
            }
        } else {
            $defaultClient = '';
        }
        $categories = Category::where('deleted_at', '=', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', '=', null)->get();
        $stripe_key = config('app.STRIPE_KEY');

        return response()->json([
            'stripe_key' => $stripe_key,
            'brands' => $brands,
            'defaultWarehouse' => $defaultWarehouse,
            'defaultClient' => $defaultClient,
            'clients' => $clients,
            'warehouses' => $warehouses,
            'categories' => $categories,
        ]);
    }

}
