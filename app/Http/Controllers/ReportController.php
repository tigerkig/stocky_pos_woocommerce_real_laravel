<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\PaymentPurchase;
use App\Models\PaymentPurchaseReturns;
use App\Models\PaymentSale;
use App\Models\PaymentSaleReturns;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Adjustment;
use App\Models\AdjustmentDetail;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetails;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use DB;

class ReportController extends BaseController
{

    //----------------- Sales Chart js -----------------------\\

    public function SalesChart()
    {
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        // Build an array of the dates we want to show, oldest first
        $dates = collect();
        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            $dates->put($date, 0);
        }

        $date_range = \Carbon\Carbon::today()->subDays(6);
        // Get the sales counts
        $sales = Sale::where('date', '>=', $date_range)
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(GrandTotal) AS count'),
            ])
            ->pluck('count', 'date');

        // Merge the two collections;
        $dates = $dates->merge($sales);

        $data = [];
        $days = [];
        foreach ($dates as $key => $value) {
            $data[] = $value;
            $days[] = $key;
        }

        return response()->json(['data' => $data, 'days' => $days]);

    }

    //----------------- Purchases Chart -----------------------\\

    public function PurchasesChart()
    {

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        // Build an array of the dates we want to show, oldest first
        $dates = collect();
        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            $dates->put($date, 0);
        }

        $date_range = \Carbon\Carbon::today()->subDays(6);

        // Get the purchases counts
        $purchases = Purchase::where('date', '>=', $date_range)
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(GrandTotal) AS count'),
            ])
            ->pluck('count', 'date');

        // Merge the two collections;
        $dates = $dates->merge($purchases);

        $data = [];
        $days = [];
        foreach ($dates as $key => $value) {
            $data[] = $value;
            $days[] = $key;
        }

        return response()->json(['data' => $data, 'days' => $days]);

    }

    //-------------------- Get Top 5 Customers -------------\\

    public function TopCustomers()
    {
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        $data = Sale::whereBetween('date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->where('sales.deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('sales.user_id', '=', Auth::user()->id);
                }
            })

            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(DB::raw('clients.name'), DB::raw("count(*) as value"))
            ->groupBy('clients.name')
            ->orderBy('value', 'desc')
            ->take(5)
            ->get();

        return response()->json($data);
    }

    //----------------- report dashboard with_echart -----------------------\\

    public function report_with_echart()
    {
        $dataSales = $this->SalesChart();
        $datapurchases = $this->PurchasesChart();
        $Payment_chart = $this->Payment_chart();
        $TopCustomers = $this->TopCustomers();
        $Top_Products_Year = $this->Top_Products_Year();
        $report_dashboard = $this->report_dashboard();

        return response()->json([
            'sales' => $dataSales,
            'purchases' => $datapurchases,
            'payments' => $Payment_chart,
            'customers' => $TopCustomers,
            'product_report' => $Top_Products_Year,
            'report_dashboard' => $report_dashboard,
        ]);

    }

    //----------------- Payment Chart js -----------------------\\

    public function Payment_chart()
    {

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        // Build an array of the dates we want to show, oldest first
        $dates = collect();
        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            $dates->put($date, 0);
        }

        $date_range = \Carbon\Carbon::today()->subDays(6);
        // Get the sales counts
        $Payment_Sale = PaymentSale::where('date', '>=', $date_range)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(montant) AS count'),
            ])
            ->pluck('count', 'date');

        $Payment_Sale_Returns = PaymentSaleReturns::where('date', '>=', $date_range)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(montant) AS count'),
            ])
            ->pluck('count', 'date');

        $Payment_Purchases = PaymentPurchase::where('date', '>=', $date_range)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(montant) AS count'),
            ])
            ->pluck('count', 'date');

        $Payment_Purchase_Returns = PaymentPurchaseReturns::where('date', '>=', $date_range)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(montant) AS count'),
            ])
            ->pluck('count', 'date');

        $Payment_Expense = Expense::where('date', '>=', $date_range)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as date")),
                DB::raw('SUM(amount) AS count'),
            ])
            ->pluck('count', 'date');

        $paymen_recieved = $this->array_merge_numeric_values($Payment_Sale, $Payment_Purchase_Returns);
        $payment_sent = $this->array_merge_numeric_values($Payment_Purchases, $Payment_Sale_Returns, $Payment_Expense);

        $dates_recieved = $dates->merge($paymen_recieved);
        $dates_sent = $dates->merge($payment_sent);

        $data_recieved = [];
        $data_sent = [];
        $days = [];
        foreach ($dates_recieved as $key => $value) {
            $data_recieved[] = $value;
            $days[] = $key;
        }

        foreach ($dates_sent as $key => $value) {
            $data_sent[] = $value;
        }

        return response()->json([
            'payment_sent' => $data_sent,
            'payment_received' => $data_recieved,
            'days' => $days,
        ]);

    }

    //----------------- array merge -----------------------\\

    public function array_merge_numeric_values()
    {
        $arrays = func_get_args();
        $merged = array();
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                if (!isset($merged[$key])) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] += $value;
                }
            }
        }
        return $merged;
    }

    //----------- Get Last 5 Sales --------------\\

    public function Get_last_Sales()
    {

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $Sales = Sale::with('details', 'client', 'facture')->where('deleted_at', '=', null)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        foreach ($Sales as $Sale) {

            $item['Ref'] = $Sale['Ref'];
            $item['statut'] = $Sale['statut'];
            $item['client_name'] = $Sale['client']['name'];
            $item['GrandTotal'] = $Sale['GrandTotal'];
            $item['paid_amount'] = $Sale['paid_amount'];
            $item['due'] = $Sale['GrandTotal'] - $Sale['paid_amount'];
            $item['payment_status'] = $Sale['payment_statut'];

            $data[] = $item;
        }

        return response()->json($data);
    }

    //-------------------- Get Top 5 Products This YEAR -------------\\

    public function Top_Products_Year()
    {

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        $products = SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sale_details.date', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ])
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('sales.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                DB::raw('products.name as name'),
                // DB::raw('sum(sale_details.quantity) as value'),
                DB::raw('count(*) as value'),
            )
            ->groupBy('products.name')
            ->orderBy('value', 'desc')
            ->take(5)
            ->get();

        return response()->json($products);
    }

    //-------------------- General Report dashboard -------------\\

    public function report_dashboard()
    {

        $Role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($Role->id)->inRole('record_view');

        // top selling product this month
        $products = SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('units', 'products.unit_sale_id', '=', 'units.id')
            ->whereBetween('sale_details.date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('sales.user_id', '=', Auth::user()->id);
                }
            })
            ->select(
                DB::raw('products.name as name'),
                DB::raw('units.ShortName as unit_product'),
                DB::raw('count(*) as count'),
                DB::raw('sum(total) as total'),
                DB::raw('sum(quantity) as quantity'),
            )
            ->groupBy('products.name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        // Stock Alerts
        $product_warehouse_data = product_warehouse::with('warehouse', 'product' ,'productVariant')
        ->join('products', 'product_warehouse.product_id', '=', 'products.id')
        ->whereRaw('qte <= stock_alert')
        ->where('product_warehouse.deleted_at', null)
        ->take('5')->get();

        $stock_alert = [];
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
                    $stock_alert[] = $item;
                }
            }

        }

        $data['today_sales'] = Sale::where('deleted_at', '=', null)
        ->where('date', \Carbon\Carbon::today())
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('user_id', '=', Auth::user()->id);
            }
        })
        ->get(DB::raw('SUM(GrandTotal)  As sum'))
        ->first()->sum;
        $data['today_sales'] = number_format($data['today_sales'], 2, '.', ',');


        $data['return_sales'] = SaleReturn::where('deleted_at', '=', null)
        ->where('date', \Carbon\Carbon::today())
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('user_id', '=', Auth::user()->id);
            }
        })
        ->get(DB::raw('SUM(GrandTotal)  As sum'))
        ->first()->sum; 
        $data['return_sales'] = number_format($data['return_sales'], 2, '.', ',');

        $data['today_purchases'] = Purchase::where('deleted_at', '=', null)
        ->where('date', \Carbon\Carbon::today())
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('user_id', '=', Auth::user()->id);
            }
        })
        ->get(DB::raw('SUM(GrandTotal)  As sum'))
        ->first()->sum;
        $data['today_purchases'] = number_format($data['today_purchases'], 2, '.', ',');

        $data['return_purchases'] = PurchaseReturn::where('deleted_at', '=', null)
        ->where('date', \Carbon\Carbon::today())
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('user_id', '=', Auth::user()->id);
            }
        })
        ->get(DB::raw('SUM(GrandTotal)  As sum'))
        ->first()->sum;
        $data['return_purchases'] = number_format($data['return_purchases'], 2, '.', ',');

        $last_sales = [];

        //last sales
        $Sales = Sale::with('details', 'client', 'facture')->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        foreach ($Sales as $Sale) {

            $item_sale['Ref'] = $Sale['Ref'];
            $item_sale['statut'] = $Sale['statut'];
            $item_sale['client_name'] = $Sale['client']['name'];
            $item_sale['GrandTotal'] = $Sale['GrandTotal'];
            $item_sale['paid_amount'] = $Sale['paid_amount'];
            $item_sale['due'] = $Sale['GrandTotal'] - $Sale['paid_amount'];
            $item_sale['payment_status'] = $Sale['payment_statut'];

            $last_sales[] = $item_sale;
        }

        return response()->json([
            'products' => $products,
            'stock_alert' => $stock_alert,
            'report' => $data,
            'last_sales' => $last_sales,
        ]);

    }

    //----------------- Customers Report -----------------------\\

    public function Client_Report(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();

        $clients = Client::where('deleted_at', '=', null)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('code', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $clients->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $clients = $clients->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($clients as $client) {
            $item['total_sales'] = DB::table('sales')
                ->where('deleted_at', '=', null)
                ->where('client_id', $client->id)
                ->count();

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

            $item['name'] = $client->name;
            $item['phone'] = $client->phone;
            $item['code'] = $client->code;
            $item['id'] = $client->id;

            $data[] = $item;
        }

        return response()->json([
            'report' => $data,
            'totalRows' => $totalRows,
        ]);

    }

    //----------------- Customers Report By ID-----------------------\\

    public function Client_Report_detail(request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);

        $client = Client::where('deleted_at', '=', null)->findOrFail($id);

        $data['total_sales'] = DB::table('sales')->where('deleted_at', '=', null)->where('client_id', $id)->count();

        $data['total_amount'] = DB::table('sales')->where('deleted_at', '=', null)->where('client_id', $id)
            ->sum('GrandTotal');

        $data['total_paid'] = DB::table('sales')
            ->where('sales.deleted_at', '=', null)
            ->where('sales.client_id', $client->id)
            ->sum('paid_amount');

        $data['due'] = $data['total_amount'] - $data['total_paid'];

        return response()->json(['report' => $data]);
    }

    //----------------- Provider Report By ID-----------------------\\

    public function Provider_Report_detail(request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);

        $provider = Provider::where('deleted_at', '=', null)->findOrFail($id);

        $data['total_purchase'] = DB::table('purchases')->where('deleted_at', '=', null)->where('provider_id', $id)->count();

        $data['total_amount'] = DB::table('purchases')->where('deleted_at', '=', null)->where('provider_id', $id)
            ->sum('GrandTotal');

        $data['total_paid'] = DB::table('purchases')
            ->where('purchases.deleted_at', '=', null)
            ->where('purchases.provider_id', $id)
            ->sum('paid_amount');

        $data['due'] = $data['total_amount'] - $data['total_paid'];

        return response()->json(['report' => $data]);

    }

    //-------------------- Get Sales By Clients -------------\\

    public function Sales_Client(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $sales = Sale::where('deleted_at', '=', null)->with('client','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where('client_id', $request->id)
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $sales->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $sales = $sales->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach ($sales as $sale) {
            $item['id'] = $sale->id;
            $item['date'] = $sale->date;
            $item['Ref'] = $sale->Ref;
            $item['warehouse_name'] = $sale['warehouse']->name;
            $item['client_name'] = $sale['client']->name;
            $item['statut'] = $sale->statut;
            $item['GrandTotal'] = $sale->GrandTotal;
            $item['paid_amount'] = $sale->paid_amount;
            $item['due'] = $sale->GrandTotal - $sale->paid_amount;
            $item['payment_status'] = $sale->payment_statut;
            $item['shipping_status'] = $sale->shipping_status;
            
            $data[] = $item;
        }
        return response()->json([
            'totalRows' => $totalRows,
            'sales' => $data,
        ]);

    }

    //-------------------- Get Payments By Clients -------------\\

    public function Payments_Client(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $payments = DB::table('payment_sales')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('payment_sales.user_id', '=', Auth::user()->id);
                }
            })
            ->where('payment_sales.deleted_at', '=', null)
            ->join('sales', 'payment_sales.sale_id', '=', 'sales.id')
            ->where('sales.client_id', $request->id)
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('payment_sales.Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_sales.date', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_sales.Reglement', 'LIKE', "%{$request->search}%");
                });
            })
            ->select(
                'payment_sales.date', 'payment_sales.Ref AS Ref', 'sales.Ref AS Sale_Ref',
                'payment_sales.Reglement', 'payment_sales.montant'
            );

        $totalRows = $payments->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $payments = $payments->offset($offSet)
            ->limit($perPage)
            ->orderBy('payment_sales.id', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
            'totalRows' => $totalRows,
        ]);

    }

    //-------------------- Get Quotations By Clients -------------\\

    public function Quotations_Client(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
        $data = array();
        
        $Quotations = Quotation::with('client', 'warehouse')
            ->where('deleted_at', '=', null)
            ->where('client_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $Quotations->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Quotations = $Quotations->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($Quotations as $Quotation) {

            $item['id'] = $Quotation->id;
            $item['date'] = $Quotation->date;
            $item['Ref'] = $Quotation->Ref;
            $item['statut'] = $Quotation->statut;
            $item['warehouse_name'] = $Quotation['warehouse']->name;
            $item['client_name'] = $Quotation['client']->name;
            $item['GrandTotal'] = $Quotation->GrandTotal;

            $data[] = $item;
        }

        return response()->json([
            'quotations' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    //-------------------- Get Returns By Client -------------\\

    public function Returns_Client(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        //  Check If User Has Permission Show All Records
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $SaleReturn = SaleReturn::where('deleted_at', '=', null)->with('sale','client','warehouse')
            ->where('client_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
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

        $totalRows = $SaleReturn->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $SaleReturn = $SaleReturn->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($SaleReturn as $Sale_Return) {
            $item['id'] = $Sale_Return->id;
            $item['Ref'] = $Sale_Return->Ref;
            $item['statut'] = $Sale_Return->statut;
            $item['client_name'] = $Sale_Return['client']->name;
            $item['sale_ref'] = $Sale_Return['sale']?$Sale_Return['sale']->Ref:'---';
            $item['sale_id'] = $Sale_Return['sale']?$Sale_Return['sale']->id:NULL;
            $item['warehouse_name'] = $Sale_Return['warehouse']->name;
            $item['GrandTotal'] = $Sale_Return->GrandTotal;
            $item['paid_amount'] = $Sale_Return->paid_amount;
            $item['due'] = $Sale_Return->GrandTotal - $Sale_Return->paid_amount;
            $item['payment_status'] = $Sale_Return->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'returns_customer' => $data,
        ]);
    }

    //------------- Show Report Purchases ----------\\

    public function Report_Purchases(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'ReportPurchases', Purchase::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(0 => 'like', 1 => 'like', 2 => '=', 3 => 'like');
        $columns = array(0 => 'Ref', 1 => 'statut', 2 => 'provider_id', 3 => 'payment_statut');
        $data = array();
        $total = 0;

        $Purchases = Purchase::select('purchases.*')
            ->with('facture', 'provider', 'warehouse')
            ->join('providers', 'purchases.provider_id', '=', 'providers.id')
            ->where('purchases.deleted_at', '=', null)
            ->whereBetween('purchases.date', array($request->from, $request->to));
            
        //  Check If User Has Permission Show All Records
        $Purchases = $helpers->Show_Records($Purchases);
        //Multiple Filter
        $Filtred = $helpers->filter($Purchases, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('purchases.Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('purchases.statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('purchases.GrandTotal', $request->search)
                        ->orWhere('purchases.payment_statut', 'like', "$request->search")
                        ->orWhere('providers.name', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Purchases = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy('purchases.' . $order, $dir)
            ->get();

        foreach ($Purchases as $Purchase) {

            $item['id'] = $Purchase->id;
            $item['date'] = $Purchase->date;
            $item['Ref'] = $Purchase->Ref;
            $item['warehouse_name'] = $Purchase['warehouse']->name;
            $item['discount'] = $Purchase->discount;
            $item['shipping'] = $Purchase->shipping;
            $item['statut'] = $Purchase->statut;
            $item['provider_name'] = $Purchase['provider']->name;
            $item['provider_email'] = $Purchase['provider']->email;
            $item['provider_tele'] = $Purchase['provider']->phone;
            $item['provider_code'] = $Purchase['provider']->code;
            $item['provider_adr'] = $Purchase['provider']->adresse;
            $item['GrandTotal'] = $Purchase['GrandTotal'];
            $item['paid_amount'] = $Purchase['paid_amount'];
            $item['due'] = $Purchase['GrandTotal'] - $Purchase['paid_amount'];
            $item['payment_status'] = $Purchase['payment_statut'];

            $data[] = $item;
        }

        $suppliers = provider::where('deleted_at', '=', null)->get(['id', 'name']);
        return response()->json([
            'totalRows' => $totalRows,
            'purchases' => $data,
            'suppliers' => $suppliers,
        ]);
    }

    //------------- Show Report SALES -----------\\

    public function Report_Sales(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Reports_sales', Sale::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(0 => 'like', 1 => 'like', 2 => '=', 3 => 'like');
        $columns = array(0 => 'Ref', 1 => 'statut', 2 => 'client_id', 3 => 'payment_statut');
        $data = array();

        $Sales = Sale::select('sales.*')
            ->with('facture', 'client', 'warehouse')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->where('sales.deleted_at', '=', null)
            ->whereBetween('sales.date', array($request->from, $request->to));

        //  Check If User Has Permission Show All Records
        $Sales = $helpers->Show_Records($Sales);
        //Multiple Filter
        $Filtred = $helpers->filter($Sales, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('sales.Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('sales.statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('sales.GrandTotal', $request->search)
                        ->orWhere('sales.payment_statut', 'like', "$request->search")
                        ->orWhere('clients.name', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Sales = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy('sales.' . $order, $dir)
            ->get();

        foreach ($Sales as $Sale) {

            $item['id'] = $Sale['id'];
            $item['date'] = $Sale['date'];
            $item['Ref'] = $Sale['Ref'];
            $item['statut'] = $Sale['statut'];
            $item['discount'] = $Sale['discount'];
            $item['shipping'] = $Sale['shipping'];
            $item['warehouse_name'] = $Sale['warehouse']['name'];
            $item['client_name'] = $Sale['client']['name'];
            $item['client_email'] = $Sale['client']['email'];
            $item['client_tele'] = $Sale['client']['phone'];
            $item['client_code'] = $Sale['client']['code'];
            $item['client_adr'] = $Sale['client']['adresse'];
            $item['GrandTotal'] = $Sale['GrandTotal'];
            $item['paid_amount'] = $Sale['paid_amount'];
            $item['due'] = $Sale['GrandTotal'] - $Sale['paid_amount'];
            $item['payment_status'] = $Sale['payment_statut'];

            $data[] = $item;
        }

        $customers = client::where('deleted_at', '=', null)->get(['id', 'name']);
        return response()->json(['totalRows' => $totalRows, 'sales' => $data, 'customers' => $customers]);
    }

    //----------------- Providers Report -----------------------\\

    public function Providers_Report(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();

        $providers = Provider::where('deleted_at', '=', null)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('code', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $providers->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $providers = $providers->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($providers as $provider) {
            $item['total_purchase'] = DB::table('purchases')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $provider->id)
                ->count();

            $item['total_amount'] = DB::table('purchases')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $provider->id)
                ->sum('GrandTotal');

            $item['total_paid'] = DB::table('purchases')
                ->where('purchases.deleted_at', '=', null)
                ->where('purchases.provider_id', $provider->id)
                ->sum('paid_amount');

            $item['due'] = $item['total_amount'] - $item['total_paid'];

            $item['total_amount_return'] = DB::table('purchase_returns')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $provider->id)
            ->sum('GrandTotal');

            $item['total_paid_return'] = DB::table('purchase_returns')
                ->where('deleted_at', '=', null)
                ->where('provider_id', $provider->id)
                ->sum('paid_amount');

            $item['return_Due'] = $item['total_amount_return'] - $item['total_paid_return'];

            $item['id'] = $provider->id;
            $item['name'] = $provider->name;
            $item['phone'] = $provider->phone;
            $item['code'] = $provider->code;

            $data[] = $item;
        }

        return response()->json([
            'report' => $data,
            'totalRows' => $totalRows,
        ]);

    }

    //-------------------- Get Purchases By Provider -------------\\

    public function Purchases_Provider(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $purchases = Purchase::where('deleted_at', '=', null)
            ->with('provider','warehouse')
            ->where('provider_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
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

        $totalRows = $purchases->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $purchases = $purchases->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($purchases as $purchase) {
            $item['id'] = $purchase->id;
            $item['Ref'] = $purchase->Ref;
            $item['warehouse_name'] = $purchase['warehouse']->name;
            $item['provider_name'] = $purchase['provider']->name;
            $item['statut'] = $purchase->statut;
            $item['GrandTotal'] = $purchase->GrandTotal;
            $item['paid_amount'] = $purchase->paid_amount;
            $item['due'] = $purchase->GrandTotal - $purchase->paid_amount;
            $item['payment_status'] = $purchase->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'purchases' => $data,
        ]);

    }

    //-------------------- Get Payments By Provider -------------\\

    public function Payments_Provider(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $payments = DB::table('payment_purchases')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where('payment_purchases.deleted_at', '=', null)
            ->join('purchases', 'payment_purchases.purchase_id', '=', 'purchases.id')
            ->where('purchases.provider_id', $request->id)
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('payment_purchases.Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_purchases.date', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_purchases.Reglement', 'LIKE', "%{$request->search}%");
                });
            })
            ->select(
                'payment_purchases.date', 'payment_purchases.Ref AS Ref', 'purchases.Ref AS purchase_Ref',
                'payment_purchases.Reglement', 'payment_purchases.montant'
            );

        $totalRows = $payments->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $payments = $payments->offset($offSet)
            ->limit($perPage)
            ->orderBy('payment_purchases.id', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
            'totalRows' => $totalRows,
        ]);
    }

    //-------------------- Get Returns By Providers -------------\\

    public function Returns_Provider(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $PurchaseReturn = PurchaseReturn::where('deleted_at', '=', null)
            ->with('purchase','provider','warehouse')
            ->where('provider_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('purchase', function ($q) use ($request) {
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

        $totalRows = $PurchaseReturn->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $PurchaseReturn = $PurchaseReturn->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($PurchaseReturn as $Purchase_Return) {
            $item['id'] = $Purchase_Return->id;
            $item['Ref'] = $Purchase_Return->Ref;
            $item['statut'] = $Purchase_Return->statut;
            $item['purchase_ref'] = $Purchase_Return['purchase']?$Purchase_Return['purchase']->Ref:'---';
            $item['purchase_id'] = $Purchase_Return['purchase']?$Purchase_Return['purchase']->id:NULL;
            $item['provider_name'] = $Purchase_Return['provider']->name;
            $item['warehouse_name'] = $Purchase_Return['warehouse']->name;
            $item['GrandTotal'] = $Purchase_Return->GrandTotal;
            $item['paid_amount'] = $Purchase_Return->paid_amount;
            $item['due'] = $Purchase_Return->GrandTotal - $Purchase_Return->paid_amount;
            $item['payment_status'] = $Purchase_Return->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'returns_supplier' => $data,
        ]);

    }

    //-------------------- Top 5 Suppliers -------------\\

    public function ToProviders(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);

        $results = DB::table('purchases')->where('purchases.deleted_at', '=', null)
            ->join('providers', 'purchases.provider_id', '=', 'providers.id')
            ->select(DB::raw('providers.name'), DB::raw('count(*) as count'))
            ->groupBy('providers.name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        $data = [];
        $providers = [];
        foreach ($results as $result) {
            $providers[] = $result->name;
            $data[] = $result->count;
        }
        $data[] = 0;
        return response()->json(['providers' => $providers, 'data' => $data]);
    }

    //----------------- Warehouse Report By ID-----------------------\\

    public function Warehouse_Report(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);

        $data['sales'] = Sale::where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })->count();

        $data['purchases'] = Purchase::where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })->count();

        $data['ReturnPurchase'] = PurchaseReturn::where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })->count();

        $data['ReturnSale'] = SaleReturn::where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })->count();

        //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

        return response()->json([
            'data' => $data,
            'warehouses' => $warehouses,
        ], 200);

    }

    //-------------------- Get Sales By Warehouse -------------\\

    public function Sales_Warehouse(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = [];

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $sales = Sale::where('deleted_at', '=', null)->with('client','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })
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
                        });
                });
            });

        $totalRows = $sales->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $sales = $sales->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($sales as $sale) {
            $item['id'] = $sale->id;
            $item['date'] = $sale->date;
            $item['Ref'] = $sale->Ref;
            $item['client_name'] = $sale['client']->name;
            $item['warehouse_name'] = $sale['warehouse']->name;
            $item['statut'] = $sale->statut;
            $item['GrandTotal'] = $sale->GrandTotal;
            $item['paid_amount'] = $sale->paid_amount;
            $item['due'] = $sale->GrandTotal - $sale->paid_amount;
            $item['payment_status'] = $sale->payment_statut;
            $item['shipping_status'] = $sale->shipping_status;

            $data[] = $item;
        }
        return response()->json([
            'totalRows' => $totalRows,
            'sales' => $data,
        ]);

    }

    //-------------------- Get Quotations By Warehouse -------------\\

    public function Quotations_Warehouse(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = [];

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $Quotations = Quotation::where('deleted_at', '=', null)
            ->with('client','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })
        //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
        $totalRows = $Quotations->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Quotations = $Quotations->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($Quotations as $Quotation) {
            $item['id'] = $Quotation->id;
            $item['date'] = $Quotation->date;
            $item['Ref'] = $Quotation->Ref;
            $item['warehouse_name'] = $Quotation['warehouse']->name;
            $item['client_name'] = $Quotation['client']->name;
            $item['statut'] = $Quotation->statut;
            $item['GrandTotal'] = $Quotation->GrandTotal;

            $data[] = $item;
        }

        return response()->json([
            'quotations' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    //-------------------- Get Returns Sale By Warehouse -------------\\

    public function Returns_Sale_Warehouse(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        //  Check If User Has Permission Show All Records
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $SaleReturn = SaleReturn::where('deleted_at', '=', null)
            ->with('sale','client','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })
        //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'like', "$request->search")

                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $SaleReturn->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $SaleReturn = $SaleReturn->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($SaleReturn as $Sale_Return) {
            $item['id'] = $Sale_Return->id;
            $item['warehouse_name'] = $Sale_Return['warehouse']->name;
            $item['Ref'] = $Sale_Return->Ref;
            $item['statut'] = $Sale_Return->statut;
            $item['client_name'] = $Sale_Return['client']->name;
            $item['sale_ref'] = $Sale_Return['sale']?$Sale_Return['sale']->Ref:'---';
            $item['sale_id'] = $Sale_Return['sale']?$Sale_Return['sale']->id:NULL;
            $item['GrandTotal'] = $Sale_Return->GrandTotal;
            $item['paid_amount'] = $Sale_Return->paid_amount;
            $item['due'] = $Sale_Return->GrandTotal - $Sale_Return->paid_amount;
            $item['payment_status'] = $Sale_Return->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'returns_sale' => $data,
        ]);
    }

    //-------------------- Get Returns Purchase By Warehouse -------------\\

    public function Returns_Purchase_Warehouse(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        //  Check If User Has Permission Show All Records
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $PurchaseReturn = PurchaseReturn::where('deleted_at', '=', null)
            ->with('purchase','provider','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->orWhere(function ($query) use ($request) {
                return $query->whereHas('purchase', function ($q) use ($request) {
                    $q->where('Ref', 'LIKE', "%{$request->search}%");
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })
        //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $PurchaseReturn->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $PurchaseReturn = $PurchaseReturn->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($PurchaseReturn as $Purchase_Return) {
            $item['id'] = $Purchase_Return->id;
            $item['Ref'] = $Purchase_Return->Ref;
            $item['statut'] = $Purchase_Return->statut;
            $item['purchase_ref'] = $Purchase_Return['purchase']?$Purchase_Return['purchase']->Ref:'---';
            $item['purchase_id'] = $Purchase_Return['purchase']?$Purchase_Return['purchase']->id:NULL;
            $item['warehouse_name'] = $Purchase_Return['warehouse']->name;
            $item['provider_name'] = $Purchase_Return['provider']->name;
            $item['GrandTotal'] = $Purchase_Return->GrandTotal;
            $item['paid_amount'] = $Purchase_Return->paid_amount;
            $item['due'] = $Purchase_Return->GrandTotal - $Purchase_Return->paid_amount;
            $item['payment_status'] = $Purchase_Return->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'returns_purchase' => $data,
        ]);
    }

    //-------------------- Get Expenses By Warehouse -------------\\

    public function Expenses_Warehouse(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        //  Check If User Has Permission Show All Records
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $Expenses = Expense::where('deleted_at', '=', null)
            ->with('expense_category','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse_id'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                });
            })
        //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('date', 'LIKE', "%{$request->search}%")
                        ->orWhere('details', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('expense_category', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $Expenses->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Expenses = $Expenses->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($Expenses as $Expense) {

            $item['date'] = $Expense->date;
            $item['Ref'] = $Expense->Ref;
            $item['details'] = $Expense->details;
            $item['amount'] = $Expense->amount;
            $item['warehouse_name'] = $Expense['warehouse']->name;
            $item['category_name'] = $Expense['expense_category']->name;
            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'expenses' => $data,
        ]);
    }

    //----------------- Warhouse Count Stock -----------------------\\

    public function Warhouse_Count_Stock(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'WarehouseStock', Product::class);

        $stock_count = product_warehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->join('warehouses', 'product_warehouse.warehouse_id', '=', 'warehouses.id')
            ->where('product_warehouse.deleted_at', '=', null)
            ->select(
                DB::raw("count(DISTINCT products.id) as value"),
                DB::raw("warehouses.name as name"),
                DB::raw('(IFNULL(SUM(qte),0)) AS value1'),
            )
            ->where('qte', '>', 0)
            ->groupBy('warehouses.name')
            ->get();

        $stock_value = product_warehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->join('warehouses', 'product_warehouse.warehouse_id', '=', 'warehouses.id')
            ->where('product_warehouse.deleted_at', '=', null)
            ->select(
                DB::raw("SUM(products.price * qte ) as price"),
                DB::raw("SUM(products.cost * qte) as cost"),
                DB::raw("warehouses.name as name"),
            )
            ->where('qte', '>', 0)
            ->groupBy('warehouses.name')
            ->get();

        $data = [];
        foreach ($stock_value as $key => $value) {
            $item['name'] = $value->name;
            $item['value'] = $value->price;
            $item['value1'] = $value->cost;
            $data[] = $item;
        }

        //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

        return response()->json([
            'stock_count' => $stock_count,
            'stock_value' => $data,
            'warehouses' => $warehouses,
        ]);

    }

    //-------------- Count  Product Quantity Alerts ---------------\\

    public function count_quantity_alert(request $request)
    {

        $products_alerts = product_warehouse::join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->whereRaw('qte <= stock_alert')
            ->count();

        return response()->json($products_alerts);
    }

    //-----------------Profit And Loss ---------------------------\\

    public function ProfitAndLoss(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_profit', Client::class);

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');

        $data = [];

        $item['sales'] = Sale::where('deleted_at', '=', null)->whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

        $item['purchases'] = Purchase::where('deleted_at', '=', null)->whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

        $item['returns_sales'] = SaleReturn::where('deleted_at', '=', null)->whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

        $item['returns_purchases'] = PurchaseReturn::where('deleted_at', '=', null)->whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(GrandTotal) AS sum'),
                DB::raw("count(*) as nmbr")
            )->first();

        $item['paiement_sales'] = PaymentSale::whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(montant) AS sum')
            )->first();

        $item['PaymentSaleReturns'] = PaymentSaleReturns::whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(montant) AS sum')
            )->first();

        $item['PaymentPurchaseReturns'] = PaymentPurchaseReturns::whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(montant) AS sum')
            )->first();

        $item['paiement_purchases'] = PaymentPurchase::whereBetween('date', array($request->from, $request->to))
            ->select(
                DB::raw('SUM(montant) AS sum')
            )->first();

        $item['expenses'] = Expense::whereBetween('date', array($request->from, $request->to))
            ->where('deleted_at', '=', null)
            ->select(
                DB::raw('SUM(amount) AS sum')
            )->first();


        $item['return_sales'] = SaleReturn::where('deleted_at', '=', null)
            ->whereBetween('date', array($request->from, $request->to))
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->get(DB::raw('SUM(GrandTotal)  As sum'))
            ->first()->sum; 
        
        $item['today_purchases'] = Purchase::where('deleted_at', '=', null)
            ->whereBetween('date', array($request->from, $request->to))
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->get(DB::raw('SUM(GrandTotal)  As sum'))
            ->first()->sum;
    
        $item['purchases_return'] = PurchaseReturn::where('deleted_at', '=', null)
            ->whereBetween('date', array($request->from, $request->to))
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->get(DB::raw('SUM(GrandTotal)  As sum'))
            ->first()->sum;

            //calcul profit
        $product_sale_data = Sale::join('sale_details' , 'sales.id', '=', 'sale_details.sale_id')
        ->select(DB::raw('sale_details.product_id , sum(sale_details.quantity) as sold_qty , sum(sale_details.total) as sold_amount'))
        ->where('sales.deleted_at', '=', null)
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('sales.user_id', '=', Auth::user()->id);
            }
        })
        ->whereBetween('sales.date', array($request->from, $request->to))
        ->groupBy('sale_details.product_id')
        ->get();

        $product_revenue = 0;
        $product_cost = 0;
        $profit = 0;


        foreach($product_sale_data as $key => $product_sale){

            $product_purchase_data = PurchaseDetail::where('product_id' , $product_sale->product_id)->get();
            $product_cost_default = Product::findOrFail($product_sale->product_id);

            $purchased_qty = 0;
            $purchased_amount = 0;  
            $qty_adjust = 0;   
            $sold_qty = $product_sale->sold_qty;
            $product_revenue += $product_sale->sold_amount;

            foreach ($product_purchase_data as $key => $product_purchase) {
                $purchased_qty += $product_purchase->quantity;
                $purchased_amount += $product_purchase->total;
                if($purchased_qty >= $sold_qty){
                    $qty_diff = $purchased_qty - $sold_qty;
                    $unit_cost = $product_purchase->total / $product_purchase->quantity;
                    $purchased_amount -= ($qty_diff * $unit_cost);
                    break;

                }
            }

            if ($product_purchase_data->isEmpty()) {
                $unit_cost = ($sold_qty * $product_cost_default->cost) / $sold_qty;
                $purchased_amount += ($sold_qty * $product_cost_default->cost);
            }
            
            $product_cost += $purchased_amount;
        }

        $item['product_cost'] = $product_cost;
        $item['profit'] = $item['sales']['sum'] - $product_cost;
        $item['payment_received'] = $item['paiement_sales']['sum'] + $item['PaymentPurchaseReturns']['sum'];
        $item['payment_sent'] = $item['paiement_purchases']['sum'] + $item['PaymentSaleReturns']['sum'] + $item['expenses']['sum'];
        $item['paiement_net'] = $item['payment_received'] - $item['payment_sent'];
        $item['total_revenue'] =  $item['sales']['sum'] -  $item['return_sales'];
        
        return response()->json(['data' => $item]);
        
    }


     //-------------------- report_top_products -------------\\

     public function report_top_products(request $request)
     {
 
        $this->authorizeForUser($request->user('api'), 'Top_products', Product::class);

        $Role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($Role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        // top selling product
        $products_count = SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('units', 'products.unit_sale_id', '=', 'units.id')
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('sales.user_id', '=', Auth::user()->id);
                }
            })
            ->whereBetween('sale_details.date', array($request->from, $request->to))
            ->select(
                DB::raw('products.name as name'),
            )
            ->groupBy('products.name')->get();

            $totalRows = $products_count->count();
            if($perPage == "-1"){
                $perPage = $totalRows;
            }

            $products_data = SaleDetail::join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('units', 'products.unit_sale_id', '=', 'units.id')
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('sales.user_id', '=', Auth::user()->id);
                }
            })
            ->whereBetween('sale_details.date', array($request->from, $request->to))
            ->select(
                DB::raw('products.name as name'),
                DB::raw('products.code as code'),
                DB::raw('units.ShortName as unit_product'),
                DB::raw('count(*) as total_sales'),
                DB::raw('sum(total) as total'),
                DB::raw('sale_details.price as price'),
                DB::raw('sum(quantity) as quantity'),
            )
            ->groupBy('products.name');
            
            $products = $products_data->offset($offSet)
            ->limit($perPage)
            ->orderBy('total_sales', 'desc')
            ->get();


            return response()->json([
                'products' => $products,
                'totalRows' => $totalRows,
            ]);

     }


    //-------------------- report_top_customers -------------\\

    public function report_top_customers(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'Top_customers', Client::class);

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $customers_count = Sale::where('sales.deleted_at', '=', null)
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('sales.user_id', '=', Auth::user()->id);
            }
        })

        ->join('clients', 'sales.client_id', '=', 'clients.id')
        ->select(DB::raw('clients.name'), DB::raw("count(*) as total_sales"))
        ->groupBy('clients.name')->get();

        $totalRows = $customers_count->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }

        $customers_data = Sale::where('sales.deleted_at', '=', null)
        ->where(function ($query) use ($view_records) {
            if (!$view_records) {
                return $query->where('sales.user_id', '=', Auth::user()->id);
            }
        })

        ->join('clients', 'sales.client_id', '=', 'clients.id')
        ->select(
            DB::raw('clients.name as name'), 
            DB::raw('clients.phone as phone'), 
            DB::raw('clients.email as email'), 
            DB::raw("count(*) as total_sales"),
            DB::raw('sum(GrandTotal) as total'),
        )
        ->groupBy('clients.name');

        $customers = $customers_data->offset($offSet)
            ->limit($perPage)
            ->orderBy('total_sales', 'desc')
            ->get();

        return response()->json([
            'customers' => $customers,
            'totalRows' => $totalRows,
        ]);

    }


     //----------------- Users Report -----------------------\\

     public function users_Report(request $request)
     {
 
         $this->authorizeForUser($request->user('api'), 'users_report', User::class);
 
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
         $order = $request->SortField;
         $dir = $request->SortType;
         $data = array();
 
         $users = User::where(function ($query) use ($request) {
            return $query->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('username', 'LIKE', "%{$request->search}%");
                });
            });
 
         $totalRows = $users->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $users = $users->offset($offSet)
             ->limit($perPage)
             ->orderBy($order, $dir)
             ->get();
 
         foreach ($users as $user) {
            $item['total_sales'] = DB::table('sales')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_purchases'] = DB::table('purchases')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_quotations'] = DB::table('quotations')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_return_sales'] = DB::table('sale_returns')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_return_purchases'] = DB::table('purchase_returns')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_transfers'] = DB::table('transfers')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();

            $item['total_adjustments'] = DB::table('adjustments')
                 ->where('deleted_at', '=', null)
                 ->where('user_id', $user->id)
                 ->count();
 
             $item['id'] = $user->id;
             $item['username'] = $user->username;
             $data[] = $item;
         }
 
         return response()->json([
             'report' => $data,
             'totalRows' => $totalRows,
         ]);
 
     }


      //-------------------- Get Sales By user -------------\\

    public function get_sales_by_user(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'users_report', User::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $sales = Sale::where('deleted_at', '=', null)->with('user','client','warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            ->where('user_id', $request->id)
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "%{$request->search}%")
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

        $totalRows = $sales->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $sales = $sales->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach ($sales as $sale) {
            $item['username'] = $sale['user']->username;
            $item['client_name'] = $sale['client']->name;
            $item['warehouse_name'] = $sale['warehouse']->name;
            $item['date'] = $sale->date;
            $item['Ref'] = $sale->Ref;
            $item['sale_id'] = $sale->id;
            $item['statut'] = $sale->statut;
            $item['GrandTotal'] = $sale->GrandTotal;
            $item['paid_amount'] = $sale->paid_amount;
            $item['due'] = $sale->GrandTotal - $sale->paid_amount;
            $item['payment_status'] = $sale->payment_statut;
            $item['shipping_status'] = $sale->shipping_status;

            $data[] = $item;
        }
        return response()->json([
            'totalRows' => $totalRows,
            'sales' => $data,
        ]);

    }

     //-------------------- Get Quotations By user -------------\\

     public function get_quotations_by_user(request $request)
     {
 
        $this->authorizeForUser($request->user('api'), 'users_report', User::class);
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
 
         $Role = Auth::user()->roles()->first();
         $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
         $data = array();

         $Quotations = Quotation::with('client', 'warehouse','user')
            ->where('deleted_at', '=', null)
             ->where('user_id', $request->id)
             ->where(function ($query) use ($ShowRecord) {
                 if (!$ShowRecord) {
                     return $query->where('user_id', '=', Auth::user()->id);
                 }
             })
              //Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
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

         $totalRows = $Quotations->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $Quotations = $Quotations->offset($offSet)
             ->limit($perPage)
             ->orderBy('id', 'desc')
             ->get();

            foreach ($Quotations as $Quotation) {

                $item['id'] = $Quotation->id;
                $item['date'] = $Quotation->date;
                $item['Ref'] = $Quotation->Ref;
                $item['statut'] = $Quotation->statut;
                $item['username'] = $Quotation['user']->username;
                $item['warehouse_name'] = $Quotation['warehouse']->name;
                $item['client_name'] = $Quotation['client']->name;
                $item['GrandTotal'] = $Quotation->GrandTotal;

                $data[] = $item;
            }
 
         return response()->json([
             'quotations' => $data,
             'totalRows' => $totalRows,
         ]);
     }

      //-------------------- Get Purchases By user -------------\\

    public function get_purchases_by_user(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'users_report', User::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $purchases = Purchase::where('deleted_at', '=', null)
            ->with('user','provider','warehouse')
            ->where('user_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
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

        $totalRows = $purchases->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $purchases = $purchases->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($purchases as $purchase) {
            $item['Ref'] = $purchase->Ref;
            $item['purchase_id'] = $purchase->id;
            $item['username'] = $purchase['user']->username;
            $item['provider_name'] = $purchase['provider']->name;
            $item['warehouse_name'] = $purchase['warehouse']->name;
            $item['statut'] = $purchase->statut;
            $item['GrandTotal'] = $purchase->GrandTotal;
            $item['paid_amount'] = $purchase->paid_amount;
            $item['due'] = $purchase->GrandTotal - $purchase->paid_amount;
            $item['payment_status'] = $purchase->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'purchases' => $data,
        ]);

    }

     //-------------------- Get sale Returns By user -------------\\

     public function get_sales_return_by_user(request $request)
     {
 
        $this->authorizeForUser($request->user('api'), 'users_report', User::class);
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
         $data = array();
 
         //  Check If User Has Permission Show All Records
         $Role = Auth::user()->roles()->first();
         $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
 
         $SaleReturn = SaleReturn::where('deleted_at', '=', null)->with('user','client','warehouse')
             ->where('user_id', $request->id)
             ->where(function ($query) use ($ShowRecord) {
                 if (!$ShowRecord) {
                     return $query->where('user_id', '=', Auth::user()->id);
                 }
             })
             // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "$request->search")
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
 
         $totalRows = $SaleReturn->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $SaleReturn = $SaleReturn->offset($offSet)
             ->limit($perPage)
             ->orderBy('id', 'desc')
             ->get();
 
         foreach ($SaleReturn as $Sale_Return) {
             $item['Ref'] = $Sale_Return->Ref;
             $item['return_sale_id'] = $Sale_Return->id;
             $item['statut'] = $Sale_Return->statut;
             $item['username'] = $Sale_Return['user']->username;
             $item['client_name'] = $Sale_Return['client']->name;
             $item['warehouse_name'] = $Sale_Return['warehouse']->name;
             $item['GrandTotal'] = $Sale_Return->GrandTotal;
             $item['paid_amount'] = $Sale_Return->paid_amount;
             $item['due'] = $Sale_Return->GrandTotal - $Sale_Return->paid_amount;
             $item['payment_status'] = $Sale_Return->payment_statut;
 
             $data[] = $item;
         }
 
         return response()->json([
             'totalRows' => $totalRows,
             'sales_return' => $data,
         ]);
     }

    //-------------------- Get purchase Returns By user -------------\\

    public function get_purchase_return_by_user(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'users_report', User::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $PurchaseReturn = PurchaseReturn::where('deleted_at', '=', null)
            ->with('user','provider','warehouse')
            ->where('user_id', $request->id)
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('provider', function ($q) use ($request) {
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

        $totalRows = $PurchaseReturn->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $PurchaseReturn = $PurchaseReturn->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($PurchaseReturn as $Purchase_Return) {
            $item['Ref'] = $Purchase_Return->Ref;
            $item['return_purchase_id'] = $Purchase_Return->id;
            $item['statut'] = $Purchase_Return->statut;
            $item['username'] = $Purchase_Return['user']->username;
            $item['provider_name'] = $Purchase_Return['provider']->name;
            $item['warehouse_name'] = $Purchase_Return['warehouse']->name;
            $item['GrandTotal'] = $Purchase_Return->GrandTotal;
            $item['paid_amount'] = $Purchase_Return->paid_amount;
            $item['due'] = $Purchase_Return->GrandTotal - $Purchase_Return->paid_amount;
            $item['payment_status'] = $Purchase_Return->payment_statut;

            $data[] = $item;
        }

        return response()->json([
            'totalRows' => $totalRows,
            'purchases_return' => $data,
        ]);

    }

     //-------------------- Get transfers By user -------------\\

     public function get_transfer_by_user(request $request)
     {
 
         $this->authorizeForUser($request->user('api'), 'users_report', User::class);
 
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
         $data = array();
 
         $Role = Auth::user()->roles()->first();
         $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
 
         $transfers = Transfer::with('from_warehouse', 'to_warehouse')
             ->with('user')
             ->where('user_id', $request->id)
             ->where(function ($query) use ($ShowRecord) {
                 if (!$ShowRecord) {
                     return $query->where('user_id', '=', Auth::user()->id);
                 }
             })
             // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('from_warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('to_warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
 
         $totalRows = $transfers->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $transfers = $transfers->offset($offSet)
             ->limit($perPage)
             ->orderBy('id', 'desc')
             ->get();
 
        foreach ($transfers as $transfer) {
                $item['id'] = $transfer->id;
                $item['date'] = $transfer->date;
                $item['Ref'] = $transfer->Ref;
                $item['username'] = $transfer['user']->username;
                $item['from_warehouse'] = $transfer['from_warehouse']->name;
                $item['to_warehouse'] = $transfer['to_warehouse']->name;
                $item['GrandTotal'] = $transfer->GrandTotal;
                $item['items'] = $transfer->items;
                $item['statut'] = $transfer->statut;

                $data[] = $item;
        }
         return response()->json([
             'totalRows' => $totalRows,
             'transfers' => $data,
         ]);
 
     }

      //-------------------- Get adjustment By user -------------\\

      public function get_adjustment_by_user(request $request)
      {
  
          $this->authorizeForUser($request->user('api'), 'users_report', User::class);
  
          // How many items do you want to display.
          $perPage = $request->limit;
          $pageStart = \Request::get('page', 1);
          // Start displaying items from this number;
          $offSet = ($pageStart * $perPage) - $perPage;
          $data = array();
  
          $Role = Auth::user()->roles()->first();
          $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
  
          $Adjustments = Adjustment::with('warehouse')
              ->with('user')
              ->where('user_id', $request->id)
              ->where(function ($query) use ($ShowRecord) {
                  if (!$ShowRecord) {
                      return $query->where('user_id', '=', Auth::user()->id);
                  }
              })
              // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
  
          $totalRows = $Adjustments->count();
          if($perPage == "-1"){
              $perPage = $totalRows;
          }
          $Adjustments = $Adjustments->offset($offSet)
              ->limit($perPage)
              ->orderBy('id', 'desc')
              ->get();
  
        foreach ($Adjustments as $Adjustment) {
                $item['id'] = $Adjustment->id;
                $item['username'] = $Adjustment['user']->username;
                $item['date'] = $Adjustment->date;
                $item['Ref'] = $Adjustment->Ref;
                $item['warehouse_name'] = $Adjustment['warehouse']->name;
                $item['items'] = $Adjustment->items;
                $data[] = $item;
            }

          return response()->json([
              'totalRows' => $totalRows,
              'adjustments' => $data,
          ]);
  
      }


    //----------------- stock Report -----------------------\\

     public function stock_Report(request $request)
     {
 
         $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
 
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
         $order = $request->SortField;
         $dir = $request->SortType;
         $data = array();

         $products_data = Product::with('unit', 'category', 'brand')
            ->where('deleted_at', '=', null)
            // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('products.name', 'LIKE', "%{$request->search}%")
                        ->orWhere('products.code', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('category', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
 
            $totalRows = $products_data->count();
            if($perPage == "-1"){
                $perPage = $totalRows;
            }
            $products = $products_data->offset($offSet)
                ->limit($perPage)
                ->orderBy($order, $dir)
                ->get();
    
            foreach ($products as $product) {
                $item['id'] = $product->id;
                $item['code'] = $product->code;
                $item['name'] = $product->name;
                $item['category'] = $product['category']->name;
                $item['price'] = $product->price;
                
                $product_warehouse_data = product_warehouse::where('product_id', $product->id)
                    ->where('deleted_at', '=', null)
                    ->get();
                $total_qty = 0;
                foreach ($product_warehouse_data as $product_warehouse) {
                    $total_qty += $product_warehouse->qte;
                    $item['quantity'] = $total_qty .' '.$product['unit']->ShortName;
                }
    
                $data[] = $item;
            }
 
         return response()->json([
             'report' => $data,
             'totalRows' => $totalRows,
         ]);
 
     }


    //-------------------- Get Sales By product -------------\\

    public function get_sales_by_product(request $request)
    {

        $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;

        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        $sale_details_data = SaleDetail::with('product','sale','sale.client','sale.warehouse')
            ->where(function ($query) use ($ShowRecord) {
                if (!$ShowRecord) {
                    return $query->whereHas('sale', function ($q) use ($request) {
                        $q->where('user_id', '=', Auth::user()->id);
                    });
                }
            })
            ->where('product_id', $request->id)
             // Search With Multiple Param
             ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                            return $query->whereHas('sale.client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale.warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('sale', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('product', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $sale_details_data->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $sale_details = $sale_details_data->offset($offSet)
            ->limit($perPage)
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach ($sale_details as $detail) {

            if($detail->product_variant_id){
                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                ->where('id', $detail->product_variant_id)->first();

                $product_name = $productsVariants->name . '-' . $detail['product']['name'];

            }else{
                $product_name = $detail['product']['name'];
            }

            $item['date'] = $detail->date;
            $item['Ref'] = $detail['sale']->Ref;
            $item['sale_id'] = $detail['sale']->id;
            $item['client_name'] = $detail['sale']['client']->name;
            $item['warehouse_name'] = $detail['sale']['warehouse']->name;
            $item['quantity'] = $detail->quantity;
            $item['total'] = $detail->total;
            $item['product_name'] = $product_name;

            $data[] = $item;
        }
        return response()->json([
            'totalRows' => $totalRows,
            'sales' => $data,
        ]);

    }

     //-------------------- Get quotations By product -------------\\

     public function get_quotations_by_product(request $request)
     {
 
         $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
 
         $Role = Auth::user()->roles()->first();
         $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
 
         $quotation_details_data = QuotationDetail::with('product','quotation','quotation.client','quotation.warehouse')
             ->where(function ($query) use ($ShowRecord) {
                 if (!$ShowRecord) {
                     return $query->whereHas('quotation', function ($q) use ($request) {
                         $q->where('user_id', '=', Auth::user()->id);
                     });
                 }
             })
             ->where('product_id', $request->id)
              // Search With Multiple Param
              ->where(function ($query) use ($request) {
                 return $query->when($request->filled('search'), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                             return $query->whereHas('quotation.client', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('quotation.warehouse', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('quotation', function ($q) use ($request) {
                                 $q->where('Ref', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('product', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         });
                 });
             });
 
         $totalRows = $quotation_details_data->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $quotation_details = $quotation_details_data->offset($offSet)
             ->limit($perPage)
             ->orderBy('id', 'desc')
             ->get();
 
         $data = [];
         foreach ($quotation_details as $detail) {
 
             if($detail->product_variant_id){
                 $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                 ->where('id', $detail->product_variant_id)->first();
 
                 $product_name = $productsVariants->name . '-' . $detail['product']['name'];
 
             }else{
                 $product_name = $detail['product']['name'];
             }
 
             $item['date'] = $detail['quotation']->date;
             $item['Ref'] = $detail['quotation']->Ref;
             $item['quotation_id'] = $detail['quotation']->id;
             $item['client_name'] = $detail['quotation']['client']->name;
             $item['warehouse_name'] = $detail['quotation']['warehouse']->name;
             $item['quantity'] = $detail->quantity;
             $item['total'] = $detail->total;
             $item['product_name'] = $product_name;
 
             $data[] = $item;
         }
         return response()->json([
             'totalRows' => $totalRows,
             'quotations' => $data,
         ]);
 
     }

       //-------------------- Get purchases By product -------------\\

       public function get_purchases_by_product(request $request)
       {
   
           $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
           // How many items do you want to display.
           $perPage = $request->limit;
           $pageStart = \Request::get('page', 1);
           // Start displaying items from this number;
           $offSet = ($pageStart * $perPage) - $perPage;
   
           $Role = Auth::user()->roles()->first();
           $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
   
           $purchase_details_data = PurchaseDetail::with('product','purchase','purchase.provider','purchase.warehouse')
               ->where(function ($query) use ($ShowRecord) {
                   if (!$ShowRecord) {
                       return $query->whereHas('purchase', function ($q) use ($request) {
                           $q->where('user_id', '=', Auth::user()->id);
                       });
                   }
               })
               ->where('product_id', $request->id)
                // Search With Multiple Param
                ->where(function ($query) use ($request) {
                   return $query->when($request->filled('search'), function ($query) use ($request) {
                       return $query->where(function ($query) use ($request) {
                               return $query->whereHas('purchase.provider', function ($q) use ($request) {
                                   $q->where('name', 'LIKE', "%{$request->search}%");
                               });
                           })
                           ->orWhere(function ($query) use ($request) {
                               return $query->whereHas('purchase.warehouse', function ($q) use ($request) {
                                   $q->where('name', 'LIKE', "%{$request->search}%");
                               });
                           })
                           ->orWhere(function ($query) use ($request) {
                               return $query->whereHas('purchase', function ($q) use ($request) {
                                   $q->where('Ref', 'LIKE', "%{$request->search}%");
                               });
                           })
                           ->orWhere(function ($query) use ($request) {
                               return $query->whereHas('product', function ($q) use ($request) {
                                   $q->where('name', 'LIKE', "%{$request->search}%");
                               });
                           });
                   });
               });
   
           $totalRows = $purchase_details_data->count();
           if($perPage == "-1"){
               $perPage = $totalRows;
           }
           $purchase_details = $purchase_details_data->offset($offSet)
               ->limit($perPage)
               ->orderBy('id', 'desc')
               ->get();
   
           $data = [];
           foreach ($purchase_details as $detail) {
   
               if($detail->product_variant_id){
                   $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                   ->where('id', $detail->product_variant_id)->first();
   
                   $product_name = $productsVariants->name . '-' . $detail['product']['name'];
   
               }else{
                   $product_name = $detail['product']['name'];
               }
   
               $item['date'] = $detail['purchase']->date;
               $item['Ref'] = $detail['purchase']->Ref;
               $item['purchase_id'] = $detail['purchase']->id;
               $item['provider_name'] = $detail['purchase']['provider']->name;
               $item['warehouse_name'] = $detail['purchase']['warehouse']->name;
               $item['quantity'] = $detail->quantity;
               $item['total'] = $detail->total;
               $item['product_name'] = $product_name;
   
               $data[] = $item;
           }
           return response()->json([
               'totalRows' => $totalRows,
               'purchases' => $data,
           ]);
   
       }

         //-------------------- Get purchases return By product -------------\\

         public function get_purchase_return_by_product(request $request)
         {
     
             $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
             // How many items do you want to display.
             $perPage = $request->limit;
             $pageStart = \Request::get('page', 1);
             // Start displaying items from this number;
             $offSet = ($pageStart * $perPage) - $perPage;
     
             $Role = Auth::user()->roles()->first();
             $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
     
             $purchase_return_details_data = PurchaseReturnDetails::with('product','PurchaseReturn','PurchaseReturn.provider','PurchaseReturn.warehouse')
                 ->where(function ($query) use ($ShowRecord) {
                     if (!$ShowRecord) {
                         return $query->whereHas('PurchaseReturn', function ($q) use ($request) {
                             $q->where('user_id', '=', Auth::user()->id);
                         });
                     }
                 })
                 ->where('product_id', $request->id)
                  // Search With Multiple Param
                  ->where(function ($query) use ($request) {
                     return $query->when($request->filled('search'), function ($query) use ($request) {
                         return $query->where(function ($query) use ($request) {
                                 return $query->whereHas('PurchaseReturn.provider', function ($q) use ($request) {
                                     $q->where('name', 'LIKE', "%{$request->search}%");
                                 });
                             })
                             ->orWhere(function ($query) use ($request) {
                                 return $query->whereHas('PurchaseReturn.warehouse', function ($q) use ($request) {
                                     $q->where('name', 'LIKE', "%{$request->search}%");
                                 });
                             })
                             ->orWhere(function ($query) use ($request) {
                                 return $query->whereHas('PurchaseReturn', function ($q) use ($request) {
                                     $q->where('Ref', 'LIKE', "%{$request->search}%");
                                 });
                             })
                             ->orWhere(function ($query) use ($request) {
                                 return $query->whereHas('product', function ($q) use ($request) {
                                     $q->where('name', 'LIKE', "%{$request->search}%");
                                 });
                             });
                     });
                 });
     
             $totalRows = $purchase_return_details_data->count();
             if($perPage == "-1"){
                 $perPage = $totalRows;
             }
             $purchase_return_details = $purchase_return_details_data->offset($offSet)
                 ->limit($perPage)
                 ->orderBy('id', 'desc')
                 ->get();
     
             $data = [];
             foreach ($purchase_return_details as $detail) {
     
                 if($detail->product_variant_id){
                     $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                     ->where('id', $detail->product_variant_id)->first();
     
                     $product_name = $productsVariants->name . '-' . $detail['product']['name'];
     
                 }else{
                     $product_name = $detail['product']['name'];
                 }
     
                 $item['date'] = $detail['PurchaseReturn']->date;
                 $item['Ref'] = $detail['PurchaseReturn']->Ref;
                 $item['return_purchase_id'] = $detail['PurchaseReturn']->id;
                 $item['provider_name'] = $detail['PurchaseReturn']['provider']->name;
                 $item['warehouse_name'] = $detail['PurchaseReturn']['warehouse']->name;
                 $item['quantity'] = $detail->quantity;
                 $item['total'] = $detail->total;
                 $item['product_name'] = $product_name;
     
                 $data[] = $item;
             }
             return response()->json([
                 'totalRows' => $totalRows,
                 'purchases_return' => $data,
             ]);
     
         }

    //-------------------- Get sales return By product -------------\\

     public function get_sales_return_by_product(request $request)
     {
 
         $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
         // How many items do you want to display.
         $perPage = $request->limit;
         $pageStart = \Request::get('page', 1);
         // Start displaying items from this number;
         $offSet = ($pageStart * $perPage) - $perPage;
 
         $Role = Auth::user()->roles()->first();
         $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
 
         $Sale_Return_details_data = SaleReturnDetails::with('product','SaleReturn','SaleReturn.client','SaleReturn.warehouse')
             ->where(function ($query) use ($ShowRecord) {
                 if (!$ShowRecord) {
                     return $query->whereHas('SaleReturn', function ($q) use ($request) {
                         $q->where('user_id', '=', Auth::user()->id);
                     });
                 }
             })
             ->where('product_id', $request->id)
              // Search With Multiple Param
              ->where(function ($query) use ($request) {
                 return $query->when($request->filled('search'), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                             return $query->whereHas('SaleReturn.client', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('SaleReturn.warehouse', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('SaleReturn', function ($q) use ($request) {
                                 $q->where('Ref', 'LIKE', "%{$request->search}%");
                             });
                         })
                         ->orWhere(function ($query) use ($request) {
                             return $query->whereHas('product', function ($q) use ($request) {
                                 $q->where('name', 'LIKE', "%{$request->search}%");
                             });
                         });
                 });
             });
 
         $totalRows = $Sale_Return_details_data->count();
         if($perPage == "-1"){
             $perPage = $totalRows;
         }
         $Sale_Return_details = $Sale_Return_details_data->offset($offSet)
             ->limit($perPage)
             ->orderBy('id', 'desc')
             ->get();
 
         $data = [];
         foreach ($Sale_Return_details as $detail) {
 
             if($detail->product_variant_id){
                 $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                 ->where('id', $detail->product_variant_id)->first();
 
                 $product_name = $productsVariants->name . '-' . $detail['product']['name'];
 
             }else{
                 $product_name = $detail['product']['name'];
             }
 
             $item['date'] = $detail['SaleReturn']->date;
             $item['Ref'] = $detail['SaleReturn']->Ref;
             $item['return_sale_id'] = $detail['SaleReturn']->id;
             $item['client_name'] = $detail['SaleReturn']['client']->name;
             $item['warehouse_name'] = $detail['SaleReturn']['warehouse']->name;
             $item['quantity'] = $detail->quantity;
             $item['total'] = $detail->total;
             $item['product_name'] = $product_name;
 
             $data[] = $item;
         }
         return response()->json([
             'totalRows' => $totalRows,
             'sales_return' => $data,
         ]);
 
     }

      //-------------------- Get transfers By product -------------\\

      public function get_transfer_by_product(request $request)
      {
  
          $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
          // How many items do you want to display.
          $perPage = $request->limit;
          $pageStart = \Request::get('page', 1);
          // Start displaying items from this number;
          $offSet = ($pageStart * $perPage) - $perPage;
  
          $Role = Auth::user()->roles()->first();
          $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
  
          $transfer_details_data = TransferDetail::with('product','transfer','transfer.from_warehouse','transfer.to_warehouse')
              ->where(function ($query) use ($ShowRecord) {
                  if (!$ShowRecord) {
                      return $query->whereHas('transfer', function ($q) use ($request) {
                          $q->where('user_id', '=', Auth::user()->id);
                      });
                  }
              })
              ->where('product_id', $request->id)
               // Search With Multiple Param
               ->where(function ($query) use ($request) {
                  return $query->when($request->filled('search'), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                              return $query->whereHas('transfer.from_warehouse', function ($q) use ($request) {
                                  $q->where('name', 'LIKE', "%{$request->search}%");
                              });
                          })
                          ->orWhere(function ($query) use ($request) {
                              return $query->whereHas('transfer.to_warehouse', function ($q) use ($request) {
                                  $q->where('name', 'LIKE', "%{$request->search}%");
                              });
                          })
                          ->orWhere(function ($query) use ($request) {
                              return $query->whereHas('transfer', function ($q) use ($request) {
                                  $q->where('Ref', 'LIKE', "%{$request->search}%");
                              });
                          })
                          ->orWhere(function ($query) use ($request) {
                              return $query->whereHas('product', function ($q) use ($request) {
                                  $q->where('name', 'LIKE', "%{$request->search}%");
                              });
                          });
                  });
              });
  
          $totalRows = $transfer_details_data->count();
          if($perPage == "-1"){
              $perPage = $totalRows;
          }
          $transfer_details = $transfer_details_data->offset($offSet)
              ->limit($perPage)
              ->orderBy('id', 'desc')
              ->get();
  
          $data = [];
          foreach ($transfer_details as $detail) {
  
              if($detail->product_variant_id){
                  $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                  ->where('id', $detail->product_variant_id)->first();
  
                  $product_name = $productsVariants->name . '-' . $detail['product']['name'];
  
              }else{
                  $product_name = $detail['product']['name'];
              }
  
              $item['date'] = $detail['transfer']->date;
              $item['Ref'] = $detail['transfer']->Ref;
              $item['from_warehouse'] = $detail['transfer']['from_warehouse']->name;
              $item['to_warehouse'] = $detail['transfer']['to_warehouse']->name;
              $item['product_name'] = $product_name;
  
              $data[] = $item;
          }
          return response()->json([
              'totalRows' => $totalRows,
              'transfers' => $data,
          ]);
  
      }

        //-------------------- Get adjustments By product -------------\\

        public function get_adjustment_by_product(request $request)
        {
    
            $this->authorizeForUser($request->user('api'), 'stock_report', Product::class);
            // How many items do you want to display.
            $perPage = $request->limit;
            $pageStart = \Request::get('page', 1);
            // Start displaying items from this number;
            $offSet = ($pageStart * $perPage) - $perPage;
    
            $Role = Auth::user()->roles()->first();
            $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');
    
            $adjustment_details_data = AdjustmentDetail::with('product','adjustment','adjustment.warehouse')
                ->where(function ($query) use ($ShowRecord) {
                    if (!$ShowRecord) {
                        return $query->whereHas('adjustment', function ($q) use ($request) {
                            $q->where('user_id', '=', Auth::user()->id);
                        });
                    }
                })
                ->where('product_id', $request->id)
                 // Search With Multiple Param
                 ->where(function ($query) use ($request) {
                    return $query->when($request->filled('search'), function ($query) use ($request) {
                        return $query->where(function ($query) use ($request) {
                                return $query->whereHas('adjustment.warehouse', function ($q) use ($request) {
                                    $q->where('name', 'LIKE', "%{$request->search}%");
                                });
                            })
                            ->orWhere(function ($query) use ($request) {
                                return $query->whereHas('adjustment', function ($q) use ($request) {
                                    $q->where('Ref', 'LIKE', "%{$request->search}%");
                                });
                            })
                            ->orWhere(function ($query) use ($request) {
                                return $query->whereHas('product', function ($q) use ($request) {
                                    $q->where('name', 'LIKE', "%{$request->search}%");
                                });
                            });
                    });
                });
    
            $totalRows = $adjustment_details_data->count();
            if($perPage == "-1"){
                $perPage = $totalRows;
            }
            $adjustment_details = $adjustment_details_data->offset($offSet)
                ->limit($perPage)
                ->orderBy('id', 'desc')
                ->get();
    
            $data = [];
            foreach ($adjustment_details as $detail) {
    
                if($detail->product_variant_id){
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();
    
                    $product_name = $productsVariants->name . '-' . $detail['product']['name'];
    
                }else{
                    $product_name = $detail['product']['name'];
                }
    
                $item['date'] = $detail['adjustment']->date;
                $item['Ref'] = $detail['adjustment']->Ref;
                $item['warehouse_name'] = $detail['adjustment']['warehouse']->name;
                $item['product_name'] = $product_name;
    
                $data[] = $item;
            }
            return response()->json([
                'totalRows' => $totalRows,
                'adjustments' => $data,
            ]);
    
        }

    //------------- download_report_client_pdf -----------\\

    public function download_report_client_pdf(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'Reports_customers', Client::class);

        $helpers = new helpers();
        $client = Client::where('deleted_at', '=', null)->findOrFail($id);

        $Sales = Sale::where('deleted_at', '=', null)
        ->where([
            ['payment_statut', '!=', 'paid'],
            ['client_id', $id]
        ])->get();

        $sales_details = [];

        foreach ($Sales as $Sale) {
            
            $item_sale['date'] = $Sale['date'];
            $item_sale['Ref'] = $Sale['Ref'];
            $item_sale['GrandTotal'] = number_format($Sale['GrandTotal'], 2, '.', '');
            $item_sale['paid_amount'] = number_format($Sale['paid_amount'], 2, '.', '');
            $item_sale['due'] = number_format($item_sale['GrandTotal'] - $item_sale['paid_amount'], 2, '.', '');
            $item_sale['payment_status'] = $Sale['payment_statut'];
            
            $sales_details[] = $item_sale;
        }

        $data['client_name'] = $client->name;
        $data['phone'] = $client->phone;

        $data['total_sales'] = DB::table('sales')->where('deleted_at', '=', null)->where('client_id', $id)->count();

        $data['total_amount'] = DB::table('sales')
                ->where('deleted_at', '=', null)
                ->where('client_id', $client->id)
                ->sum('GrandTotal');

        $data['total_paid'] = DB::table('sales')
            ->where('deleted_at', '=', null)
            ->where('client_id', $client->id)
            ->sum('paid_amount');

        $data['due'] = $data['total_amount'] - $data['total_paid'];

        $data['total_amount_return'] = DB::table('sale_returns')
            ->where('deleted_at', '=', null)
            ->where('client_id', $client->id)
            ->sum('GrandTotal');

        $data['total_paid_return'] = DB::table('sale_returns')
            ->where('deleted_at', '=', null)
            ->where('client_id', $client->id)
            ->sum('paid_amount');

        $data['return_Due'] = $data['total_amount_return'] - $data['total_paid_return'];
     
        $symbol = $helpers->Get_Currency();
        $settings = Setting::where('deleted_at', '=', null)->first();

        $pdf = \PDF::loadView('pdf.report_client_pdf', [
            'symbol' => $symbol,
            'client' => $data,
            'sales' => $sales_details,
            'setting' => $settings,
        ]);

        return $pdf->download('report_client.pdf');

    }

     //------------- download_report_provider_pdf -----------\\

     public function download_report_provider_pdf(Request $request, $id)
     {
 
        $this->authorizeForUser($request->user('api'), 'Reports_suppliers', Provider::class);
 
         $helpers = new helpers();
         $provider = Provider::where('deleted_at', '=', null)->findOrFail($id);
 
         $purchases = Purchase::where('deleted_at', '=', null)
         ->where('payment_statut', '!=', 'paid')
         ->where('provider_id', $id)
         ->get();

         $purchases_details = [];
 
         foreach ($purchases as $purchase) {
             
             $item_purchase['date'] = $purchase['date'];
             $item_purchase['Ref'] = $purchase['Ref'];
             $item_purchase['GrandTotal'] = number_format($purchase['GrandTotal'], 2, '.', '');
             $item_purchase['paid_amount'] = number_format($purchase['paid_amount'], 2, '.', '');
             $item_purchase['due'] = number_format($item_purchase['GrandTotal'] - $item_purchase['paid_amount'], 2, '.', '');
             $item_purchase['payment_status'] = $purchase['payment_statut'];
             
             $purchases_details[] = $item_purchase;
         }
 
         $data['provider_name'] = $provider->name;
         $data['phone'] = $provider->phone;
 
        $data['total_purchase'] = DB::table('purchases')->where('deleted_at', '=', null)->where('provider_id', $id)->count();

        $data['total_amount'] = DB::table('purchases')->where('deleted_at', '=', null)->where('provider_id', $id)
            ->sum('GrandTotal');

        $data['total_paid'] = DB::table('purchases')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $id)
            ->sum('paid_amount');

        $data['due'] = $data['total_amount'] - $data['total_paid'];

        $data['total_amount_return'] = DB::table('purchase_returns')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $id)
            ->sum('GrandTotal');

        $data['total_paid_return'] = DB::table('purchase_returns')
            ->where('deleted_at', '=', null)
            ->where('provider_id', $id)
            ->sum('paid_amount');

        $data['return_Due'] = $data['total_amount_return'] - $data['total_paid_return'];
      
         $symbol = $helpers->Get_Currency();
         $settings = Setting::where('deleted_at', '=', null)->first();
 
         $pdf = \PDF::loadView('pdf.report_provider_pdf', [
             'symbol' => $symbol,
             'provider' => $data,
             'purchases' => $purchases_details,
             'setting' => $settings,
         ]);
 
         return $pdf->download('report_provider.pdf');
 
     }

}