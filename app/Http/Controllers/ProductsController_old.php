<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Unit;
use App\Models\Warehouse;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use \Gumlet\ImageResize;
use WooCommerce;

class ProductsController extends BaseController
{

    //------------ Get ALL Products --------------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $columns = array(0 => 'name', 1 => 'category_id', 2 => 'brand_id', 3 => 'code');
        $param = array(0 => 'like', 1 => '=', 2 => '=', 3 => 'like');
        $data = array();

        $products = Product::with('unit', 'category', 'brand')
            ->where('deleted_at', '=', null);

        //Multiple Filter
        $Filtred = $helpers->filter($products, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('products.name', 'LIKE', "%{$request->search}%")
                        ->orWhere('products.code', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('category', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('brand', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $products = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($products as $product) {
            $item['id'] = $product->id;
            $item['code'] = $product->code;
            $item['name'] = $product->name;
            $item['category'] = $product['category']->name;
            $item['brand'] = $product['brand'] ? $product['brand']->name : 'N/D';
            $item['unit'] = $product['unit']->ShortName;
            $item['price'] = $product->price;
            
            $is_woo_product_deleted = false;
            if($product->pos_id != -1){
                if($product->pos_var_id == -1){
                    try{
                        $wooProduct = $this->show_woo($product->pos_id);
                        if($wooProduct->status == 'publish')
                            $item['price'] = $wooProduct->regular_price;
                        else
                            $is_woo_product_deleted = true;
                    }catch(HttpClientException $e){
                        $is_woo_product_deleted = true;
                    }
                }else{
                    try{
                        $variation = $this->show_variation_woo($product->pos_id, $product->pos_var_id);
                        if($variation->status == 'publish')
                            $item['price'] = $variation->regular_price;
                        else
                            $is_woo_product_deleted = true;
                    }catch(HttpClientException $e){
                        $is_woo_product_deleted = true;
                    }
                }
            }
            
            if(!$is_woo_product_deleted){
                $product_warehouse_data = product_warehouse::where('product_id', $product->id)
                    ->where('deleted_at', '=', null)
                    ->get();
                $total_qty = 0;
                foreach ($product_warehouse_data as $product_warehouse) {
                    $total_qty += $product_warehouse->qte;
                    $item['quantity'] = $total_qty;
                }

                if($product->pos_id != -1){
                    if($product->pos_var_id == -1){
                        $woo_product = $this->show_woo($product->pos_id);
                        if($woo_product->stock_quantity){
                            if($woo_product->stock_quantity != $total_qty){
                                $product_warehouse = product_warehouse::where('product_id', $product->id)
                                    ->where('deleted_at', '=', null)
                                    ->first();
                                $product_warehouse->qte += $woo_product->stock_quantity - $total_qty;
                                $product_warehouse->save();
                                $item['quantity'] = $woo_product->stock_quantity;
                            }
                        }
                    }else{
                        $woo_product = $this->show_variation_woo($product->pos_id, $product->pos_var_id);
                        if($woo_product->stock_quantity){
                            if($woo_product->stock_quantity != $total_qty){
                                $product_warehouse = product_warehouse::where('product_id', $product->id)
                                    ->where('deleted_at', '=', null)
                                    ->first();
                                $product_warehouse->qte += $woo_product->stock_quantity - $total_qty;
                                $product_warehouse->save();
                                $item['quantity'] = $woo_product->stock_quantity;
                            }
                        }
                    }
                }

                $firstimage = explode(',', $product->image);
                $item['image'] = $firstimage[0];
                
                $data[] = $item;
            }else{
                $product->deleted_at = Carbon::now();
                $product->save();

                foreach (explode(',', $product->image) as $img) {
                    $pathIMG = public_path() . '/images/products/' . $img;
                    if (file_exists($pathIMG)) {
                        if ($img != 'no-image.png') {
                            @unlink($pathIMG);
                        }
                    }
                }

                product_warehouse::where('product_id', $product->id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                ProductVariant::where('product_id', $product->id)->update([
                    'deleted_at' => Carbon::now(),
                ]);
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

        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);

        return response()->json([
            'warehouses' => $warehouses,
            'categories' => $categories,
            'brands' => $brands,
            'products' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    
    // public function index(request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'view', Product::class);
    //     // How many items do you want to display.
    //     $perPage = $request->limit;
    //     $pageStart = \Request::get('page', 1);
    //     // Start displaying items from this number;
    //     $offSet = ($pageStart * $perPage) - $perPage;
    //     $order = $request->SortField;
    //     $dir = $request->SortType;
    //     $helpers = new helpers();
    //     // Filter fields With Params to retrieve
    //     $columns = array(0 => 'name', 1 => 'category_id', 2 => 'brand_id', 3 => 'code');
    //     $param = array(0 => 'like', 1 => '=', 2 => '=', 3 => 'like');
    //     $data = array();

    //     // $products = Product::with('unit', 'category', 'brand')
    //     //     ->where('deleted_at', '=', null);
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
    //         $item['id'] = $product->id;
    //         $item['code'] = $product->sku;
    //         $item['name'] = $product->name;
    //         $item['category'] = 'cat';
    //         $item['brand'] = 'bra';
    //         $item['unit'] = 'uni';
    //         $item['price'] = $product->price;
            
    //         $item['quantity'] = 0;
    //         if($product->stock_quantity)
    //             $item['quantity'] = $product->stock_quantity;

    //         $item['image'] = 'image';
    //         if($product->images)
    //             $item['image'] = $product->images[0]->src;

    //         $data[] = $item;
    //     }

    //     //get warehouses assigned to user
    //     $user_auth = auth()->user();
    //     if($user_auth->is_all_warehouses){
    //         $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
    //     }else{
    //         $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
    //         $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
    //     }

    //     $categories = Category::where('deleted_at', null)->get(['id', 'name']);
    //     $brands = Brand::where('deleted_at', null)->get(['id', 'name']);

    //     return response()->json([
    //         'warehouses' => $warehouses,
    //         'categories' => $categories,
    //         'brands' => $brands,
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

    public function product_variant_list_woo($id){
        $page = 1;
        $product_variants = [];
        $all_product_variants = [];
        do{
            try {
                $product_variants = WooCommerce::all('products/'.$id.'/variations?per_page=100&page='.$page);
            }catch(HttpClientException $e){
            }
        $all_product_variants = array_merge($all_product_variants,$product_variants);
        $page++;
        } while (count($product_variants) > 0);
        return $all_product_variants;
    }

    //-------------- Store new  Product  ---------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Product::class);

        try {
            $this->validate($request, [
                'code' => 'required|unique:products',
                'code' => Rule::unique('products')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                }),
                'name' => 'required',
                'Type_barcode' => 'required',
                'price' => 'required',
                'category_id' => 'required',
                'cost' => 'required',
                'unit_id' => 'required',
            ], [
                'code.unique' => 'This code already used. Generate Now',
                'code.required' => 'This field is required',
            ]);

            $product_exist_woo = false;
            $temp_pos_id = -1;
            $temp_pos_var_id = -1;
            $temp_pos_name = $request['name'];
            $temp_pos_price = $request['price'];

            $products = $this->product_list_woo();
            foreach($products as $product){
                if($product->sku == $request['code']){
                    $product_exist_woo = true;
                    $temp_pos_id = $product->id;
                    $temp_pos_name = $product->name;
                    $temp_pos_price = $product->regular_price;
                }else{
                    $product_variants = $this->product_variant_list_woo($product->id);
                    foreach($product_variants as $product_variant){
                        if($product_variant->sku == $request['code']){
                            $product_exist_woo = true;
                            $temp_pos_id = $product->id;
                            $temp_pos_var_id = $product_variant->id;
                            $temp_pos_price = $product_variant->regular_price;
                        }
                    }
                }
            }

            // if(!$product_exist_woo){
            //     $data = [
            //         'name' => $request['name'],
            //         'type' => 'simple',
            //         'regular_price' => $request['price'],
            //         'sku' => $request['code'],
            //         'manage_stock' => true,
            //         'stock_quantity' => 0,
            //     ];
    
            //     $product = $this->create_product_woo($data);
            //     $temp_pos_id = $product->id;
            // }

            \DB::transaction(function () use ($request, $temp_pos_id, $temp_pos_var_id, $temp_pos_name, $temp_pos_price) {

                //-- Create New Product
                $Product = new Product;

                //-- Field Required
                $Product->pos_id = $temp_pos_id;
                $Product->pos_var_id = $temp_pos_var_id;
                $Product->name = $temp_pos_name;
                $Product->code = $request['code'];
                $Product->Type_barcode = $request['Type_barcode'];
                $Product->price = $temp_pos_price;
                $Product->category_id = $request['category_id'];
                $Product->brand_id = $request['brand_id'];
                $Product->TaxNet = $request['TaxNet'] ? $request['TaxNet'] : 0;
                $Product->tax_method = $request['tax_method'];
                $Product->note = $request['note'];
                $Product->cost = $request['cost'];
                $Product->unit_id = $request['unit_id'];
                $Product->unit_sale_id = $request['unit_sale_id'];
                $Product->unit_purchase_id = $request['unit_purchase_id'];
                $Product->stock_alert = $request['stock_alert'] ? $request['stock_alert'] : 0;
                $Product->is_variant = $request['is_variant'] == 'true' ? 1 : 0;
                $Product->is_imei = $request['is_imei'] == 'true' ? 1 : 0;
                $Product->not_selling = $request['not_selling'] == 'true' ? 1 : 0;

                if ($request['images']) {
                    $files = $request['images'];
                    foreach ($files as $file) {
                        $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
                        $fileData->resize(200, 200);
                        $name = rand(11111111, 99999999) . $file['name'];
                        $path = public_path() . '/images/products/';
                        $success = file_put_contents($path . $name, $fileData);
                        $images[] = $name;
                    }
                    $filename = implode(",", $images);
                } else {
                    $filename = 'no-image.png';
                }

                $Product->image = $filename;
                $Product->save();

                // Store Variants Product
                if ($request['is_variant'] == 'true') {
                    foreach ($request['variants'] as $variant) {
                        $Product_variants_data[] = [
                            'product_id' => $Product->id,
                            'name' => $variant,
                        ];
                    }
                    ProductVariant::insert($Product_variants_data);
                }

                //--Store Product Warehouse
                $warehouses = Warehouse::where('deleted_at', null)->pluck('id')->toArray();
                if ($warehouses) {
                    $Product_variants = ProductVariant::where('product_id', $Product->id)
                        ->where('deleted_at', null)
                        ->get();
                    foreach ($warehouses as $warehouse) {
                        if ($request['is_variant'] == 'true') {
                            foreach ($Product_variants as $product_variant) {

                                $product_warehouse[] = [
                                    'product_id' => $Product->id,
                                    'warehouse_id' => $warehouse,
                                    'product_variant_id' => $product_variant->id,
                                ];
                            }
                        } else {
                            $product_warehouse[] = [
                                'product_id' => $Product->id,
                                'warehouse_id' => $warehouse,
                            ];
                        }
                    }
                    product_warehouse::insert($product_warehouse);
                }

            }, 10);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }

    }

    // public function store(Request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'create', Product::class);

    //     try {
    //         $attributes = array();

    //         $data = [
    //             'name' => $request['name'],
    //             'type' => 'simple',
    //             'regular_price' => $request['price'],
    //             'description' => 'full description',
    //             'short_description' => 'short description',
    //             'sku' => $request['code'],
    //             'manage_stock' => true,
    //             'stock_quantity' => $request['quantity'],
    //             'attributes' => $attributes,
                
    //         ];

    //         $product = $this->create_product_woo($data);

    //         return response()->json(['success' => true]);

    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'status' => 422,
    //             'msg' => 'error',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     }

    // }

    public function create_product_woo($data){
        $result = WooCommerce::create('products', $data);
        return $result;
    } 

    //-------------- Update Product  ---------------\\
    //-----------------------------------------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Product::class);
        try {
            $this->validate($request, [
                'code' => 'required|unique:products',
                'code' => Rule::unique('products')->ignore($id)->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                }),
                'name' => 'required',
                'Type_barcode' => 'required',
                'price' => 'required',
                'category_id' => 'required',
                'cost' => 'required',
                'unit_id' => 'required',
            ], [
                'code.unique' => 'This code already used. Generate Now',
                'code.required' => 'This field is required',
            ]);

            \DB::transaction(function () use ($request, $id) {

                $Product = Product::where('id', $id)
                    ->where('deleted_at', '=', null)
                    ->first();

                if($Product){
                    //-- Update Product
                    
                    $Product->name = $request['name'];
                    $Product->code = $request['code'];
                    $Product->Type_barcode = $request['Type_barcode'];
                    $Product->price = $request['price'];
                    $Product->category_id = $request['category_id'];
                    $Product->brand_id = $request['brand_id'] == 'null' ?Null: $request['brand_id'];
                    $Product->TaxNet = $request['TaxNet'];
                    $Product->tax_method = $request['tax_method'];
                    $Product->note = $request['note'];
                    $Product->cost = $request['cost'];
                    $Product->unit_id = $request['unit_id'];
                    $Product->unit_sale_id = $request['unit_sale_id'] ? $request['unit_sale_id'] : $request['unit_id'];
                    $Product->unit_purchase_id = $request['unit_purchase_id'] ? $request['unit_purchase_id'] : $request['unit_id'];
                    $Product->stock_alert = $request['stock_alert'];
                    $Product->is_variant = $request['is_variant'] == 'true' ? 1 : 0;
                    $Product->is_imei = $request['is_imei'] == 'true' ? 1 : 0;
                    $Product->not_selling = $request['not_selling'] == 'true' ? 1 : 0;
                    // Store Variants Product
                    $oldVariants = ProductVariant::where('product_id', $id)
                        ->where('deleted_at', null)
                        ->get();

                    $warehouses = Warehouse::where('deleted_at', null)
                        ->pluck('id')
                        ->toArray();

                    if ($request['is_variant'] == 'true') {

                        if ($oldVariants->isNotEmpty()) {
                            $new_variants_id = [];
                            $var = 'id';
    
                            foreach ($request['variants'] as $new_id) {
                                if (array_key_exists($var, $new_id)) {
                                    $new_variants_id[] = $new_id['id'];
                                } else {
                                    $new_variants_id[] = 0;
                                }
                            }
    
                            foreach ($oldVariants as $key => $value) {
                                $old_variants_id[] = $value->id;
    
                                // Delete Variant
                                if (!in_array($old_variants_id[$key], $new_variants_id)) {
                                    $ProductVariant = ProductVariant::findOrFail($value->id);
                                    $ProductVariant->deleted_at = Carbon::now();
                                    $ProductVariant->save();
    
                                    $ProductWarehouse = product_warehouse::where('product_variant_id', $value->id)
                                        ->update(['deleted_at' => Carbon::now()]);
                                }
                            }
    
                            foreach ($request['variants'] as $key => $variant) {
                                if (array_key_exists($var, $variant)) {
    
                                    $ProductVariantDT = new ProductVariant;
    
                                    //-- Field Required
                                    $ProductVariantDT->product_id = $variant['product_id'];
                                    $ProductVariantDT->name = $variant['text'];
                                    $ProductVariantDT->qty = $variant['qty'];
                                    $ProductVariantUP['product_id'] = $variant['product_id'];
                                    $ProductVariantUP['name'] = $variant['text'];
                                    $ProductVariantUP['qty'] = $variant['qty'];
    
                                } else {
                                    $ProductVariantDT = new ProductVariant;
    
                                    //-- Field Required
                                    $ProductVariantDT->product_id = $id;
                                    $ProductVariantDT->name = $variant['text'];
                                    $ProductVariantDT->qty = 0.00;
                                    $ProductVariantUP['product_id'] = $id;
                                    $ProductVariantUP['name'] = $variant['text'];
                                    $ProductVariantUP['qty'] = 0.00;
                                }
    
                                if (!in_array($new_variants_id[$key], $old_variants_id)) {
                                    $ProductVariantDT->save();
    
                                    //--Store Product warehouse
                                    if ($warehouses) {
                                        $product_warehouse= [];
                                        foreach ($warehouses as $warehouse) {
    
                                            $product_warehouse[] = [
                                                'product_id' => $id,
                                                'warehouse_id' => $warehouse,
                                                'product_variant_id' => $ProductVariantDT->id,
                                            ];
    
                                        }
                                        product_warehouse::insert($product_warehouse);
                                    }
                                } else {
                                    ProductVariant::where('id', $variant['id'])->update($ProductVariantUP);
                                }
                            }
    
                        } else {
                            $ProducttWarehouse = product_warehouse::where('product_id', $id)
                                ->update([
                                    'deleted_at' => Carbon::now(),
                                ]);
    
                            foreach ($request['variants'] as $variant) {
                                $product_warehouse_DT = [];
                                $ProductVarDT = new ProductVariant;
    
                                //-- Field Required
                                $ProductVarDT->product_id = $id;
                                $ProductVarDT->name = $variant['text'];
                                $ProductVarDT->save();
    
                                //-- Store Product warehouse
                                if ($warehouses) {
                                    foreach ($warehouses as $warehouse) {
    
                                        $product_warehouse_DT[] = [
                                            'product_id' => $id,
                                            'warehouse_id' => $warehouse,
                                            'product_variant_id' => $ProductVarDT->id,
                                        ];
                                    }
    
                                    product_warehouse::insert($product_warehouse_DT);
                                }
                            }
    
                        }
                    } else {
                        if ($oldVariants->isNotEmpty()) {
                            foreach ($oldVariants as $old_var) {
                                $var_old = ProductVariant::where('product_id', $old_var['product_id'])
                                    ->where('deleted_at', null)
                                    ->first();
                                $var_old->deleted_at = Carbon::now();
                                $var_old->save();
    
                                $ProducttWarehouse = product_warehouse::where('product_variant_id', $old_var['id'])
                                    ->update([
                                        'deleted_at' => Carbon::now(),
                                    ]);
                            }
    
                            if ($warehouses) {
                                foreach ($warehouses as $warehouse) {
    
                                    $product_warehouse[] = [
                                        'product_id' => $id,
                                        'warehouse_id' => $warehouse,
                                        'product_variant_id' => null,
                                    ];
    
                                }
                                product_warehouse::insert($product_warehouse);
                            }
                        }
                    }

                    if ($request['images'] === null) {

                        if ($Product->image !== null) {
                            foreach (explode(',', $Product->image) as $img) {
                                $pathIMG = public_path() . '/images/products/' . $img;
                                if (file_exists($pathIMG)) {
                                    if ($img != 'no-image.png') {
                                        @unlink($pathIMG);
                                    }
                                }
                            }
                        }
                        $filename = 'no-image.png';
                    } else {
                        if ($Product->image !== null) {
                            foreach (explode(',', $Product->image) as $img) {
                                $pathIMG = public_path() . '/images/products/' . $img;
                                if (file_exists($pathIMG)) {
                                    if ($img != 'no-image.png') {
                                        @unlink($pathIMG);
                                    }
                                }
                            }
                        }
                        $files = $request['images'];
                        foreach ($files as $file) {
                            $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
                            $fileData->resize(200, 200);
                            $name = rand(11111111, 99999999) . $file['name'];
                            $path = public_path() . '/images/products/';
                            $success = file_put_contents($path . $name, $fileData);
                            $images[] = $name;
                        }
                        $filename = implode(",", $images);
                    }

                    $Product->image = $filename;
                    $Product->save();
                }else{
                    $Product = new Product;

                    //-- Field Required
                    $Product->pos_id = $id;
                    $Product->name = $request['name'];
                    $Product->code = $request['code'];
                    $Product->Type_barcode = $request['Type_barcode'];
                    $Product->price = $request['price'];
                    $Product->category_id = $request['category_id'];
                    $Product->brand_id = $request['brand_id'];
                    $Product->TaxNet = $request['TaxNet'] ? $request['TaxNet'] : 0;
                    $Product->tax_method = $request['tax_method'];
                    $Product->note = $request['note'];
                    $Product->cost = $request['cost'];
                    $Product->unit_id = $request['unit_id'];
                    $Product->unit_sale_id = $request['unit_sale_id'];
                    $Product->unit_purchase_id = $request['unit_purchase_id'];
                    $Product->stock_alert = $request['stock_alert'] ? $request['stock_alert'] : 0;
                    $Product->is_variant = $request['is_variant'] == 'true' ? 1 : 0;
                    $Product->is_imei = $request['is_imei'] == 'true' ? 1 : 0;
                    $Product->not_selling = $request['not_selling'] == 'true' ? 1 : 0;

                    if ($request['images']) {
                        $files = $request['images'];
                        foreach ($files as $file) {
                            $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
                            $fileData->resize(200, 200);
                            $name = rand(11111111, 99999999) . $file['name'];
                            $path = public_path() . '/images/products/';
                            $success = file_put_contents($path . $name, $fileData);
                            $images[] = $name;
                        }
                        $filename = implode(",", $images);
                    } else {
                        $filename = 'no-image.png';
                    }

                    $Product->image = $filename;
                    $Product->save();
                }
                
                if($Product->pos_id != -1){
                    if($Product->pos_var_id == -1){
                        $data = [
                            'name' => $request['name'],
                            'regular_price' => $request['price'],
                            'sku' => $request['code'],
                        ];
            
                        $product = $this->update_product_woo($Product->pos_id, $data);
                    }else{
                        $data = [
                            'regular_price' => $request['price'],
                            'sku' => $request['code'],
                        ];

                        $product = $this->update_product_variation_woo($Product->pos_id, $Product->pos_var_id, $data);
                    }
                }

                

            }, 10);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }

    }

    public function updateWoo(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Product::class);
        try {
            // $this->validate($request, [
            //     'code' => 'required|unique:products',
            //     'code' => Rule::unique('products')->ignore($id)->where(function ($query) {
            //         return $query->where('deleted_at', '=', null);
            //     }),
            //     'name' => 'required',
            //     'Type_barcode' => 'required',
            //     'price' => 'required',
            //     'category_id' => 'required',
            //     'cost' => 'required',
            //     'unit_id' => 'required',
            // ], [
            //     'code.unique' => 'This code already used. Generate Now',
            //     'code.required' => 'This field is required',
            // ]);
            $Product = $this->show_woo($id);

            $productsVariants = [];
            if($Product->attributes)
                $productsVariants = $Product->attributes;
            foreach ($productsVariants as $pvariant) {
                if(str_contains($pvariant->name, 'Select coffee capsule tray size')){
                    $pvariant->options = array();
                    if ($request['is_variant'] == 'true') {
                        foreach ($request['variants'] as $variant) {
                            $pvariant->options[] = $variant['text'];
                        }
                    }
                    // $pvariant->options = json_encode($pvariant->options);
                }
            }

            $images = [];
            // if ($request['images'] !== null) {
            //     $files = $request['images'];
            //     foreach ($files as $file) {
            //         $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
            //         $fileData->resize(200, 200);
            //         $name = rand(11111111, 99999999) . $file['name'];
            //         $path = 'https://coveinterior.com/wp-content/uploads/2022/07/';
            //         // $success = file_put_contents($path . $name, $fileData);

            //         $response = Http::attach(
            //             'attachment', $fileData, $name
            //         )->post($path);

            //         $images[] = [ 'src' => $path . $name ];
            //     }
            // }

//             $username = 'admin';
// $password = 'admin';
// $client = new Client([
// 'base_uri' => 'http://localhost:8888/wordpress/wp-json/wp/v2/',
// 'headers' => [
// 'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
// 'Accept' => 'application/json',
// 'Content-type' => 'application/json',
// 'Content-Disposition' => 'attachment',
// ]
// ]);
// // uploading featured image to wordpress media and getting id
// $name = $doc->getTitle() . '.' . 'jpg';
// $responses = $client->post(
// 'media',
// [
// 'multipart' => [
// [
// 'name' => 'file',
// 'contents' => file_get_contents($image_url),
// 'filename' => $name
// ],
// ]
// ]);
// $image_id_wp = json_decode($responses->getBody(), true);
// // uploading post to wordpress with featured image id
// $response = $client->post('posts', [
// 'multipart' => [
// [
// 'name' => 'title',
// 'contents' => $doc->getTitle()
// ],
// [
// 'name' => 'content',
// 'contents' => implode("", $datas)
// ],
// [
// 'name' => 'featured_media',
// 'contents' => $image_id_wp['id']
// ],
// ]
// ]);
// }
// //getting the content of the documents
// $file = new \Google_Service_Docs($client);
// $document = $respons['id'];
// $doc = $file->documents->get($document);
// $contents = $doc->getBody()->getContent();
// $datas = [];
// for ($i = 0; $i < count($contents); $i++) {
// if ($contents[$i]->getParagraph() == null) {
// continue;
// }
// $table = $contents[$i]->getParagraph()->getElements();
// for ($j = 0; $j < count($table); $j++) {
// if ($table[$j]->getTextRun() == null) {
// goto image;
// }
// $cell = $table[$j]->getTextRun()->getContent();
// array_push($datas, $cell);
// image:
// if ($table[$j]->getInlineObjectElement() == null) {
// continue;
// }
// $image_id = $table[$j]->getInlineObjectElement()->getInlineObjectId();
// $url = $doc->getInlineObjects();
// $image_url2 = "<img " . "src" . "=" . $url[$image_id]->getInlineObjectProperties()->getEmbeddedObject()->getImageProperties()->contentUri . ">";
// array_push($datas, $image_url2);
// $image_url = $url[$image_id]->getInlineObjectProperties()->getEmbeddedObject()->getImageProperties()->contentUri;
// }
// }

            $data = [
                'name' => $request['name'],
                'type' => 'simple',
                'regular_price' => $request['price'],
                'description' => 'full description',
                'short_description' => 'short description',
                'sku' => $request['code'],
                'manage_stock' => true,
                'stock_quantity' => $request['quantity'],
                'attributes' => $productsVariants,
                // 'categories' => [
                //     [
                //         'id' => $request['category_id']
                //     ],
                // ],
                'images' => $images,
            ];

            $product = $this->update_product_woo($id, $data);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }

    }

    public function update_product_woo($id, $data){
        $result = WooCommerce::update('products/'.$id, $data);
        return $result;
    }

    public function update_product_variation_woo($product_id, $product_variation_id, $data){
        $result = WooCommerce::update('products/'.$product_id.'/'.'variations/'.$product_variation_id, $data);
        return $result;
    }

    //-------------- Remove Product  ---------------\\
    //-----------------------------------------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Product::class);
        // $success = true;
        

        \DB::transaction(function () use ($id) {

            $Product = Product::findOrFail($id);
            $Product->deleted_at = Carbon::now();
            $Product->save();

            foreach (explode(',', $Product->image) as $img) {
                $pathIMG = public_path() . '/images/products/' . $img;
                if (file_exists($pathIMG)) {
                    if ($img != 'no-image.png') {
                        @unlink($pathIMG);
                    }
                }
            }

            product_warehouse::where('product_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            ProductVariant::where('product_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            if($Product->pos_id != -1){
                if($Product->pos_var_id == -1){
                    $this->delete_product_woo($Product->pos_id);
                }else{
                    $this->delete_product_woo($Product->pos_var_id);
                }
            }

        }, 10);
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Product::class);

        \DB::transaction(function () use ($request) {
            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $product_id) {

                $Product = Product::findOrFail($product_id);
                $Product->deleted_at = Carbon::now();
                $Product->save();

                foreach (explode(',', $Product->image) as $img) {
                    $pathIMG = public_path() . '/images/products/' . $img;
                    if (file_exists($pathIMG)) {
                        if ($img != 'no-image.png') {
                            @unlink($pathIMG);
                        }
                    }
                }

                product_warehouse::where('product_id', $product_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                ProductVariant::where('product_id', $product_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);
                
                if($Product->pos_id != -1){
                    if($Product->pos_var_id == -1){
                        $this->delete_product_woo($Product->pos_id);
                    }else{
                        $this->delete_product_woo($Product->pos_var_id);
                    }
                }
            }

        }, 10);

        return response()->json(['success' => true]);

    }

    // public function delete_by_selection(Request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'delete', Product::class);

    //     $selectedIds = $request->selectedIds;
    //     $success = true;
    //     foreach ($selectedIds as $product_id) {

    //         try{
    //             $this->delete_product_woo($product_id);
    //         }catch(HttpClientException $e){
    //             $success = false;
    //         }

    //     }

    //     return response()->json(['success' => $success]);

    // }

    public function delete_product_woo($id){
        $result = WooCommerce::delete('products/'.$id);
        return $result;
    }

    //--------------  Show Product Details ---------------\\

    public function Get_Products_Details(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', Product::class);

        $Product = Product::where('deleted_at', '=', null)->findOrFail($id);
        //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

        $item['id'] = $Product->id;
        $item['code'] = $Product->code;
        $item['Type_barcode'] = $Product->Type_barcode;
        $item['name'] = $Product->name;
        $item['note'] = $Product->note;
        $item['category'] = $Product['category']->name;
        $item['brand'] = $Product['brand'] ? $Product['brand']->name : 'N/D';
        $item['unit'] = $Product['unit']->ShortName;
        $item['price'] = $Product->price;
        $item['cost'] = $Product->cost;
        $item['stock_alert'] = $Product->stock_alert;
        $item['taxe'] = $Product->TaxNet;
        $item['tax_method'] = $Product->tax_method == '1' ? 'Exclusive' : 'Inclusive';

        if ($Product->is_variant) {
            $item['is_variant'] = 'yes';
            $productsVariants = ProductVariant::where('product_id', $id)
                ->where('deleted_at', null)
                ->get();
            foreach ($productsVariants as $variant) {
                $item['ProductVariant'][] = $variant->name;

                foreach ($warehouses as $warehouse) {
                    $product_warehouse = DB::table('product_warehouse')
                        ->where('product_id', $id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $warehouse->id)
                        ->where('product_variant_id', $variant->id)
                        ->select(DB::raw('SUM(product_warehouse.qte) AS sum'))
                        ->first();

                    $war_var['mag'] = $warehouse->name;
                    $war_var['variant'] = $variant->name;
                    $war_var['qte'] = $product_warehouse->sum;
                    $item['CountQTY_variants'][] = $war_var;
                }

            }
        } else {
            $item['is_variant'] = 'no';
            $item['CountQTY_variants'] = [];
        }

        foreach ($warehouses as $warehouse) {
            $product_warehouse_data = DB::table('product_warehouse')
                ->where('deleted_at', '=', null)
                ->where('product_id', $id)
                ->where('warehouse_id', $warehouse->id)
                ->select(DB::raw('SUM(product_warehouse.qte) AS sum'))
                ->first();

            $war['mag'] = $warehouse->name;
            $war['qte'] = $product_warehouse_data->sum;
            $item['CountQTY'][] = $war;
        }

        if ($Product->image != '') {
            foreach (explode(',', $Product->image) as $img) {
                $item['images'][] = $img;
            }
        }

        $data[] = $item;

        return response()->json($data[0]);

    }

    //------------ Get products By Warehouse -----------------\\

    public function Products_by_Warehouse(request $request, $id)
    {
        $data = [];
        $product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')

        ->where(function ($query) use ($request , $id) {
                return $query->where('warehouse_id', $id)
                    ->where('deleted_at', '=', null)
                    ->where(function ($query) use ($request) {
                        return $query->whereHas('product', function ($q) use ($request) {
                            if ($request->is_sale == '1') {
                                $q->where('not_selling', '=', 0);
                            }
                        });
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->stock == '1') {
                            return $query->where('qte', '>', 0);
                        }
                    });
        })->get();

        foreach ($product_warehouse_data as $product_warehouse) {

            if ($product_warehouse->product_variant_id) {
                $item['product_variant_id'] = $product_warehouse->product_variant_id;
                $item['code'] = $product_warehouse['productVariant']->name . '-' . $product_warehouse['product']->code;
                $item['Variant'] = $product_warehouse['productVariant']->name;
            } else {
                $item['product_variant_id'] = null;
                $item['Variant'] = null;
                $item['code'] = $product_warehouse['product']->code;
            }

            $item['id'] = $product_warehouse->product_id;
            $item['name'] = $product_warehouse['product']->name;
            $item['barcode'] = $product_warehouse['product']->code;
            $item['Type_barcode'] = $product_warehouse['product']->Type_barcode;
            $firstimage = explode(',', $product_warehouse['product']->image);
            $item['image'] = $firstimage[0];

            if ($product_warehouse['product']['unitSale']->operator == '/') {
                $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price / $product_warehouse['product']['unitSale']->operator_value;
            } else {
                $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price * $product_warehouse['product']['unitSale']->operator_value;
            }

            if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);
            } else {
                $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
            }

            $item['qte'] = $product_warehouse->qte;
            $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
            $item['unitPurchase'] = $product_warehouse['product']['unitPurchase']->ShortName;

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

        return response()->json($data);
    }

    // public function Products_by_Warehouse(request $request, $id)
    // {
    //     $data = [];
    //     $products = $this->product_list_woo();

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

    //     return response()->json($data);
    // }

    //------------ Get product By ID -----------------\\

    public function show($id)
    {

        $Product_data = Product::with('unit')
            ->where('id', $id)
            ->where('deleted_at', '=', null)
            ->first();

        $data = [];
        $item['id'] = $Product_data['id'];
        $item['name'] = $Product_data['name'];
        $item['Type_barcode'] = $Product_data['Type_barcode'];
        $item['unit_id'] = $Product_data['unit']->id;
        $item['unit'] = $Product_data['unit']->ShortName;
        $item['purchase_unit_id'] = $Product_data['unitPurchase']->id;
        $item['unitPurchase'] = $Product_data['unitPurchase']->ShortName;
        $item['sale_unit_id'] = $Product_data['unitSale']->id;
        $item['unitSale'] = $Product_data['unitSale']->ShortName;
        $item['tax_method'] = $Product_data['tax_method'];
        $item['tax_percent'] = $Product_data['TaxNet'];
        $item['is_imei'] = $Product_data['is_imei'];
        $item['not_selling'] = $Product_data['not_selling'];

        if ($Product_data['unitSale']->operator == '/') {
            $price = $Product_data['price'] / $Product_data['unitSale']->operator_value;

        } else {
            $price = $Product_data['price'] * $Product_data['unitSale']->operator_value;
        }

        if ($Product_data['unitPurchase']->operator == '/') {
            $cost = $Product_data['cost'] / $Product_data['unitPurchase']->operator_value;
        } else {
            $cost = $Product_data['cost'] * $Product_data['unitPurchase']->operator_value;
        }

        $item['Unit_cost'] = $cost;
        $item['fix_cost'] = $Product_data['cost'];
        $item['Unit_price'] = $price;
        $item['fix_price'] = $Product_data['price'];

        if ($Product_data->TaxNet !== 0.0) {
            //Exclusive
            if ($Product_data['tax_method'] == '1') {
                $tax_price = $price * $Product_data['TaxNet'] / 100;
                $tax_cost = $cost * $Product_data['TaxNet'] / 100;

                $item['Total_cost'] = $cost + $tax_cost;
                $item['Total_price'] = $price + $tax_price;
                $item['Net_cost'] = $cost;
                $item['Net_price'] = $price;
                $item['tax_price'] = $tax_price;
                $item['tax_cost'] = $tax_cost;

                // Inxclusive
            } else {
                $item['Total_cost'] = $cost;
                $item['Total_price'] = $price;
                $item['Net_cost'] = $cost / (($Product_data['TaxNet'] / 100) + 1);
                $item['Net_price'] = $price / (($Product_data['TaxNet'] / 100) + 1);
                $item['tax_cost'] = $item['Total_cost'] - $item['Net_cost'];
                $item['tax_price'] = $item['Total_price'] - $item['Net_price'];
            }
        } else {
            $item['Total_cost'] = $cost;
            $item['Total_price'] = $price;
            $item['Net_cost'] = $cost;
            $item['Net_price'] = $price;
            $item['tax_price'] = 0;
            $item['tax_cost'] = 0;
        }

        $data[] = $item;

        return response()->json($data[0]);
    }

    // public function show($id)
    // {
    //     $data = [];
    //     $products = $this->product_list_woo();
    //     foreach ($products as $product) {

    //         if($product->id == $id){
    //             $item['id'] = $product->id;
    //             $item['name'] = $product->name;
    //             $item['Type_barcode'] = $product->sku;
    //             $item['unit_id'] = 10;
    //             $item['unit'] = 'unit';
    //             $item['purchase_unit_id'] = $product->id;
    //             $item['unitPurchase'] = 'unitPurchase';
    //             $item['sale_unit_id'] = $product->id;
    //             $item['unitSale'] = 'unitSale';
    //             $item['tax_method'] = 'tax_method';
    //             $item['tax_percent'] = 10;
    //             $item['is_imei'] = false;
    //             $item['not_selling'] = false;

    //             $price = $product->price;
    //             $cost = $product->price;

    //             $item['Unit_cost'] = $cost;
    //             $item['fix_cost'] = $cost;
    //             $item['Unit_price'] = $price;
    //             $item['fix_price'] = $price;

    //             $item['Total_cost'] = $cost;
    //             $item['Total_price'] = $price;
    //             $item['Net_cost'] = $cost;
    //             $item['Net_price'] = $price;
    //             $item['tax_price'] = 0;
    //             $item['tax_cost'] = 0;

    //             $data[] = $item;
    //         }
    //     }

    //     return response()->json($data[0]);
    // }

    //--------------  Product Quantity Alerts ---------------\\

    public function Products_Alert(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Stock_Alerts', Product::class);

        $product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->whereRaw('qte <= stock_alert')
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse);
                });
            })->where('product_warehouse.deleted_at', null)->get();

        $data = [];

        if ($product_warehouse_data->isNotEmpty()) {

            foreach ($product_warehouse_data as $product_warehouse) {
                if ($product_warehouse->qte <= $product_warehouse['product']->stock_alert) {
                    if ($product_warehouse->product_variant_id !== null) {
                        $item['code'] = $product_warehouse['productVariant']->name . '-' . $product_warehouse['product']->code;
                    } else {
                        $item['code'] = $product_warehouse['product']->code;
                    }
                    $item['quantity'] = $product_warehouse->qte;
                    $item['name'] = $product_warehouse['product']->name;
                    $item['warehouse'] = $product_warehouse['warehouse']->name;
                    $item['stock_alert'] = $product_warehouse['product']->stock_alert;
                    $data[] = $item;
                }
            }
        }

        $perPage = $request->limit; // How many items do you want to display.
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $collection = collect($data);
        // Get only the items you need using array_slice
        $data_collection = $collection->slice($offSet, $perPage)->values();

        $products = new LengthAwarePaginator($data_collection, count($data), $perPage, Paginator::resolveCurrentPage(), array('path' => Paginator::resolveCurrentPath()));
       
         //get warehouses assigned to user
         $user_auth = auth()->user();
         if($user_auth->is_all_warehouses){
             $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
         }else{
             $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
             $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
         }
 
        return response()->json([
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    //---------------- Show Form Create Product ---------------\\

    public function create(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Product::class);

        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);
        $units = Unit::where('deleted_at', null)->where('base_unit', null)->get();
        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
        ]);

    }

    //---------------- Show Elements Barcode ---------------\\

    public function Get_element_barcode(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'barcode', Product::class);

         //get warehouses assigned to user
         $user_auth = auth()->user();
         if($user_auth->is_all_warehouses){
             $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
         }else{
             $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
             $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
         }
        
        return response()->json(['warehouses' => $warehouses]);

    }

    //---------------- Show Form Edit Product ---------------\\

    public function edit(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', Product::class);

        $Product = Product::where('id', $id)
                    ->where('deleted_at', '=', null)
                    ->first();

        if($Product){
            $item['id'] = $Product->id;
            $item['code'] = $Product->code;
            $item['Type_barcode'] = $Product->Type_barcode;
            $item['name'] = $Product->name;
            if ($Product->category_id) {
                if (Category::where('id', $Product->category_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $item['category_id'] = $Product->category_id;
                } else {
                    $item['category_id'] = '';
                }
            } else {
                $item['category_id'] = '';
            }

            if ($Product->brand_id) {
                if (Brand::where('id', $Product->brand_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $item['brand_id'] = $Product->brand_id;
                } else {
                    $item['brand_id'] = '';
                }
            } else {
                $item['brand_id'] = '';
            }

            if ($Product->unit_id) {
                if (Unit::where('id', $Product->unit_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $item['unit_id'] = $Product->unit_id;
                } else {
                    $item['unit_id'] = '';
                }

                if (Unit::where('id', $Product->unit_sale_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $item['unit_sale_id'] = $Product->unit_sale_id;
                } else {
                    $item['unit_sale_id'] = '';
                }

                if (Unit::where('id', $Product->unit_purchase_id)
                    ->where('deleted_at', '=', null)
                    ->first()) {
                    $item['unit_purchase_id'] = $Product->unit_purchase_id;
                } else {
                    $item['unit_purchase_id'] = '';
                }

            } else {
                $item['unit_id'] = '';
            }

            $item['tax_method'] = $Product->tax_method;
            $item['price'] = $Product->price;
            $item['cost'] = $Product->cost;
            $item['stock_alert'] = $Product->stock_alert;
            $item['TaxNet'] = $Product->TaxNet;
            $item['note'] = $Product->note ? $Product->note : '';
            $item['images'] = [];
            if ($Product->image != '' && $Product->image != 'no-image.png') {
                foreach (explode(',', $Product->image) as $img) {
                    $path = public_path() . '/images/products/' . $img;
                    if (file_exists($path)) {
                        $itemImg['name'] = $img;
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $itemImg['path'] = 'data:image/' . $type . ';base64,' . base64_encode($data);

                        $item['images'][] = $itemImg;
                    }
                }
            } else {
                $item['images'] = [];
            }
            if ($Product->is_variant) {
                $item['is_variant'] = true;
                $productsVariants = ProductVariant::where('product_id', $id)
                    ->where('deleted_at', null)
                    ->get();
                foreach ($productsVariants as $variant) {
                    $variant_item['id'] = $variant->id;
                    $variant_item['text'] = $variant->name;
                    $variant_item['qty'] = $variant->qty;
                    $variant_item['product_id'] = $variant->product_id;
                    $item['ProductVariant'][] = $variant_item;
                }
            } else {
                $item['is_variant'] = false;
                $item['ProductVariant'] = [];
            }

            $item['is_imei'] = $Product->is_imei?true:false;
            $item['not_selling'] = $Product->not_selling?true:false;
            
            
            if($Product->pos_id != -1){
                if($Product->pos_var_id == -1){
                    $wooProduct = $this->show_woo($Product->pos_id);
                    $item['price'] = $wooProduct->regular_price;
                }else{
                    $variation = $this->show_variation_woo($Product->pos_id, $Product->pos_var_id);

                    $item['price'] = $variation->regular_price;
                }
            }
            
            $product_units = Unit::where('id', $Product->unit_id)
                            ->orWhere('base_unit', $Product->unit_id)
                            ->where('deleted_at', null)
                            ->get();
        }else{
            $item['Type_barcode'] = 'barcode';
            $item['category_id'] = '';
            $item['brand_id'] = '';
            $item['unit_id'] = '';
            $item['tax_method'] = 'tax_method';
            $item['cost'] = 0;
            $item['stock_alert'] = '';
            $item['TaxNet'] = 0;
            $item['note'] = '';
            $item['images'] = [];
            $item['is_imei'] = false;
            $item['not_selling'] = false;
            $product_units = [];
        }

        $item['quantity'] = 0;
        $product_warehouse_data = product_warehouse::where('product_id', $Product->id)
            ->where('deleted_at', '=', null)
            ->get();
        $total_qty = 0;
        foreach ($product_warehouse_data as $product_warehouse) {
            $total_qty += $product_warehouse->qte;
            $item['quantity'] = $total_qty;
        }

        // if($Product->pos_id != -1){
        //     $wooProduct = $this->show_woo($Product->pos_id);

        //     $item['id'] = $id;
        //     $item['code'] = $wooProduct->sku;
        //     $item['name'] = $wooProduct->name;
        //     $item['price'] = $wooProduct->price;
            
        //     $item['ProductVariant'] = [];
        //     $productsVariant = [];
        //     if($wooProduct->variations){
        //         $productsVariant = $wooProduct->variations;
        //         $item['is_variant'] = true;
        //     }
        //     foreach ($productsVariant as $variant) {
        //         $variation = $this->show_variation_woo($id, $variant);
                
        //         $id = $variation->id;

        //         $vattributes = $variation->attributes;
        //         $options = [];
        //         foreach($vattributes as $vattribute) {
        //             $options[] = [
        //                 'key' => $vattribute->name,
        //                 'value' => $vattribute->option
        //             ];
        //         }
                
        //         $options[] = [
        //             'key' => 'cost',
        //             'value' => $variation->regular_price
        //         ];

        //         $stock_quantity = 0;
        //         if($variation->stock_quantity)
        //             $stock_quantity = $variation->stock_quantity;
        //         $options[] = [
        //             'key' => 'quantity',
        //             'value' => $stock_quantity
        //         ];

        //         $sku = "";
        //         if($variation->sku)
        //             $sku = $variation->sku;
        //         $options[] = [
        //             'key' => 'code',
        //             'value' => $sku
        //         ];
                
        //         $text = '';
        //         foreach($options as $option){
        //             $text .= $option['key'] . ': ' . $option['value'] . '   ';
        //         }

        //         $item['ProductVariant'][] = [
        //             'title' => '#' . $id,
        //             'text' => $text,
        //             'tag' => '',
        //         ];
        //     }
            
        //     $item['quantity'] = 0;
        //     if($wooProduct->stock_quantity)
        //         $item['quantity'] = $wooProduct->stock_quantity;
        // }

        $data = $item;
        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);

        $units = Unit::where('deleted_at', null)
            ->where('base_unit', null)
            ->get();

        return response()->json([
            'product' => $data,
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'units_sub' => $product_units,
        ]);

    }

    public function editWoo(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', Product::class);

        $Product = $this->show_woo($id);

        $item['id'] = $Product->id;
        $item['code'] = $Product->sku;
        $item['Type_barcode'] = 'Type_barcode';
        $item['name'] = $Product->name;
        $item['category_id'] = 0;
        $item['brand_id'] = 0;
        $item['unit_id'] = 0;
        $item['unit_sale_id'] = 0;
        $item['unit_purchase_id'] = 0;
        $item['tax_method'] = 'tax_method';
        $item['price'] = $Product->price;
        $item['cost'] = 10;
        $item['stock_alert'] = '';
        $item['TaxNet'] = 0;
        $item['note'] = '';
        $item['images'] = [];
        $item['quantity'] = 0;
        if($Product->stock_quantity)
            $item['quantity'] = $Product->stock_quantity;

        $item['ProductAttributes'] = [];
        if($Product->attributes)
            $item['ProductAttributes'] = $Product->attributes;
        
        $item['is_variant'] = true;
        $item['ProductVariant'] = [];
        $productsVariant = [];
        if($Product->variations)
            $productsVariant = $Product->variations;
        foreach ($productsVariant as $variant) {
            $variation = $this->show_variation_woo($id, $variant);
            
            $id = $variation->id;

            $vattributes = $variation->attributes;
            $options = [];
            foreach($vattributes as $vattribute) {
                $options[] = [
                    'key' => $vattribute->name,
                    'value' => $vattribute->option
                ];
            }
            
            $options[] = [
                'key' => 'cost',
                'value' => $variation->regular_price
            ];

            $stock_quantity = 0;
            if($variation->stock_quantity)
                $stock_quantity = $variation->stock_quantity;
            $options[] = [
                'key' => 'quantity',
                'value' => $stock_quantity
            ];

            $sku = "";
            if($variation->sku)
                $sku = $variation->sku;
            $options[] = [
                'key' => 'code',
                'value' => $sku
            ];
            
            $text = '';
            foreach($options as $option){
                $text .= $option['key'] . ': ' . $option['value'] . '   ';
            }

            $item['ProductVariant'][] = [
                'title' => '#' . $id,
                'text' => $text,
                'tag' => '',
            ];
        }

        $item['is_imei'] = false;
        $item['not_selling'] = false;

        $data = $item;
        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);

        $product_units = Unit::where('id', 1)
                              ->orWhere('base_unit', 1)
                              ->where('deleted_at', null)
                              ->get();

      
        $units = Unit::where('deleted_at', null)
            ->where('base_unit', null)
            ->get();

        return response()->json([
            'product' => $data,
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'units_sub' => $product_units,
        ]);

    }

    public function show_variation_woo($pid, $vid){
        $result = WooCommerce::find('products/'.$pid.'/'.'variations/'.$vid);
        return $result;
    }

    public function show_woo($id){
        $result = WooCommerce::find('products/'.$id);
        return $result;
    }

    // import Products
    public function import_products(Request $request)
    {
        try {
            \DB::transaction(function () use ($request) {
                $file_upload = $request->file('products');
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


                    $warehouses = Warehouse::where('deleted_at', null)->pluck('id')->toArray();

                    //-- Create New Product
                    foreach ($data as $key => $value) {
                        $category = Category::firstOrCreate(['name' => $value['category']]);
                        $category_id = $category->id;

                        $unit = Unit::where(['ShortName' => $value['unit']])
                            ->orWhere(['name' => $value['unit']])->first();
                        $unit_id = $unit->id;

                        if ($value['brand'] != 'N/A' && $value['brand'] != '') {
                            $brand = Brand::firstOrCreate(['name' => $value['brand']]);
                            $brand_id = $brand->id;
                        } else {
                            $brand_id = null;
                        }
                        $Product = new Product;
                        $Product->name = $value['name'] == '' ? null : $value['name'];
                        $Product->code = $value['code'] == '' ? '11111111' : $value['code'];
                        $Product->Type_barcode = 'CODE128';
                        $Product->price = $value['price'];
                        $Product->cost = $value['cost'];
                        $Product->category_id = $category_id;
                        $Product->brand_id = $brand_id;
                        $Product->TaxNet = 0;
                        $Product->tax_method = 1;
                        $Product->note = $value['note'] ? $value['note'] : '';
                        $Product->unit_id = $unit_id;
                        $Product->unit_sale_id = $unit_id;
                        $Product->unit_purchase_id = $unit_id;
                        $Product->stock_alert = $value['stock_alert'] ? $value['stock_alert'] : 0;
                        $Product->is_variant = 0;
                        $Product->image = 'no-image.png';
                        $Product->save();

                        if ($warehouses) {
                            foreach ($warehouses as $warehouse) {
                                $product_warehouse[] = [
                                    'product_id' => $Product->id,
                                    'warehouse_id' => $warehouse,
                                ];
                            }
                        }
                    }
                    if ($warehouses) {
                        product_warehouse::insert($product_warehouse);
                    }
                }
            }, 10);
            return response()->json([
                'status' => true,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'msg' => 'error',
                'errors' => $e->errors(),
            ]);
        }

    }

    // Generate_random_code
    public function generate_random_code($value_code)
    {
        if($value_code == ''){
            $gen_code = substr(number_format(time() * mt_rand(), 0, '', ''), 0, 8);
            $this->check_code_exist($gen_code);
        }else{
            $this->check_code_exist($value_code);
        }
    }


    // check_code_exist
    public function check_code_exist($code)
    {
        $check_code = Product::where('code', $code)->first();
        if ($check_code) {
            $this->generate_random_code($code);
        } else {
            return $code;
        }



    }




}
