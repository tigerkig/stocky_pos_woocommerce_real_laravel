<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use WooCommerce;

class CategorieController extends BaseController
{

    //-------------- Get All Categories ---------------\\

    // public function index(Request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'view', Category::class);
    //     // How many items do you want to display.
    //     $perPage = $request->limit;
    //     $pageStart = \Request::get('page', 1);
    //     // Start displaying items from this number;
    //     $offSet = ($pageStart * $perPage) - $perPage;
    //     $order = $request->SortField;
    //     $dir = $request->SortType;
    //     $helpers = new helpers();

    //     $categories = Category::where('deleted_at', '=', null)

    //     // Search With Multiple Param
    //         ->where(function ($query) use ($request) {
    //             return $query->when($request->filled('search'), function ($query) use ($request) {
    //                 return $query->where('name', 'LIKE', "%{$request->search}%")
    //                     ->orWhere('code', 'LIKE', "%{$request->search}%");
    //             });
    //         });
    //     $totalRows = $categories->count();
    //     if($perPage == "-1"){
    //         $perPage = $totalRows;
    //     }
    //     $categories = $categories->offset($offSet)
    //         ->limit($perPage)
    //         ->orderBy($order, $dir)
    //         ->get();

    //     return response()->json([
    //         'categories' => $categories,
    //         'totalRows' => $totalRows,
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'view', Category::class);
    //     // How many items do you want to display.
    //     $perPage = $request->limit;
    //     $pageStart = \Request::get('page', 1);
    //     // Start displaying items from this number;
    //     $offSet = ($pageStart * $perPage) - $perPage;
    //     $order = $request->SortField;
    //     $dir = $request->SortType;
    //     $helpers = new helpers();

    //     $categories = $this->category_list_woo();

    //     // Search With Multiple Param
    //         // ->where(function ($query) use ($request) {
    //         //     return $query->when($request->filled('search'), function ($query) use ($request) {
    //         //         return $query->where('name', 'LIKE', "%{$request->search}%")
    //         //             ->orWhere('code', 'LIKE', "%{$request->search}%");
    //         //     });
    //         // });
    //     $totalRows = count($categories);
    //     if($perPage == "-1"){
    //         $perPage = $totalRows;
    //     }
    //     $categories = array_slice($categories,$offSet,$perPage);

    //     $categories_pos = Category::where('deleted_at', '=', null);
    //     $categories_pos = $categories_pos->get();

    //     foreach($categories as $category){
    //         $is_new_category = true;
    //         foreach($categories_pos as $category_pos){
    //             if($category->name == $category_pos->name)
    //                 $is_new_category = false;
    //         }
    //         if($is_new_category){
    //             Category::create([
    //                 'code' => $category->name,
    //                 'name' => $category->name,
    //             ]);
    //         }
    //     }
    
    //     return response()->json([
    //         'categories' => $categories,
    //         'totalRows' => $totalRows,
    //     ]);
    // }

    public function category_list_woo(){
        $page = 1;
        $categories = [];
        $all_categories = [];
        do{
            try {
                $categories = WooCommerce::all('products/categories?per_page=100&page='.$page);
            }catch(HttpClientException $e){
            }
        $all_categories = array_merge($all_categories,$categories);
        $page++;
        } while (count($categories) > 0);
        return $all_categories;
    }
    //-------------- Store New Category ---------------\\

    // public function store(Request $request)
    // {
    //     $this->authorizeForUser($request->user('api'), 'create', Category::class);

    //     request()->validate([
    //         'name' => 'required',
    //         'code' => 'required',
    //     ]);

    //     Category::create([
    //         'code' => $request['code'],
    //         'name' => $request['name'],
    //     ]);
    //     return response()->json(['success' => true]);
    // }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Category::class);

        request()->validate([
            'name' => 'required',
            'code' => 'required',
        ]);

        $data = [
            'name' => $request['name'],
        ];
        $result = $this->create_category_woo($data);

        return response()->json(['success' => true]);
    }

    public function create_category_woo($data){
        $result = WooCommerce::create('products/categories', $data);
        return $result;
    } 

     //------------ function show -----------\\

    public function show($id){
        //
    
    }

    //-------------- Update Category ---------------\\

    // public function update(Request $request, $id)
    // {
    //     $this->authorizeForUser($request->user('api'), 'update', Category::class);

    //     request()->validate([
    //         'name' => 'required',
    //         'code' => 'required',
    //     ]);

    //     Category::whereId($id)->update([
    //         'code' => $request['code'],
    //         'name' => $request['name'],
    //     ]);
    //     return response()->json(['success' => true]);

    // }

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Category::class);

        request()->validate([
            'name' => 'required',
            'code' => 'required',
        ]);

        $data = [
            'name' => $request['name'],
        ];
        $result = $this->update_category_woo($id, $data);

        return response()->json(['success' => true]);

    }

    public function update_category_woo($id, $data){
        $result = WooCommerce::update('products/categories/'.$id, $data);
        return $result;
    }
    //-------------- Remove Category ---------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Category::class);

        try{
            $result = $this->delete_category_woo($id);
        }catch(HttpClientException $e){
            $success = false;
        }
        return response()->json(['success' => true]);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Category::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $category_id) {
            try{
                $result = $this->delete_category_woo($category_id);
            }catch(HttpClientException $e){
                $success = false;
            }
        }

        return response()->json(['success' => true]);
    }

    public function delete_category_woo($id){
        $result = WooCommerce::delete('products/categories/'.$id, ['force' => true]);
        return $result;
    }
}
