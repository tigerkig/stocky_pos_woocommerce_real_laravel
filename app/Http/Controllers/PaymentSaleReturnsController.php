<?php
namespace App\Http\Controllers;

use Twilio\Rest\Client as Client_Twilio;
use App\Mail\PaymentReturn;
use App\Models\Client;
use App\Models\PaymentSaleReturns;
use App\Models\Role;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use \Nwidart\Modules\Facades\Module;
use App\Models\sms_gateway;
use DB;
use PDF;

class PaymentSaleReturnsController extends BaseController
{

    //------------- Get All Payment Sale Returns --------------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Reports_payments_Sale_Returns', PaymentSaleReturns::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // Filter fields With Params to retriever
        $param = array(0 => 'like', 1 => '=', 2 => 'like');
        $columns = array(0 => 'Ref', 1 => 'sale_return_id', 2 => 'Reglement');
        $data = array();

        // Check If User Has Permission View  All Records
        $Payments = PaymentSaleReturns::with('SaleReturn', 'SaleReturn.client')
            ->where('deleted_at', '=', null)
            ->whereBetween('date', array($request->from, $request->to))
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })

        // Multiple Filter
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('client_id'), function ($query) use ($request) {
                    return $query->whereHas('SaleReturn.client', function ($q) use ($request) {
                        $q->where('id', '=', $request->client_id);
                    });
                });
            });
        $Filtred = $helpers->filter($Payments, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('date', 'LIKE', "%{$request->search}%")
                        ->orWhere('Reglement', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('SaleReturn', function ($q) use ($request) {
                                $q->where('Ref', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('SaleReturn.client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $Payments = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($Payments as $Payment) {

            $item['date'] = $Payment->date;
            $item['Ref'] = $Payment->Ref;
            $item['Ref_return'] = $Payment['SaleReturn']->Ref;
            $item['client_name'] = $Payment['SaleReturn']['client']->name;
            $item['Reglement'] = $Payment->Reglement;
            $item['montant'] = $Payment->montant;
            // $item['montant'] = number_format($Payment->montant, 2, '.', '');
            $data[] = $item;
        }

        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $sale_returns = SaleReturn::get(['Ref', 'id']);

        return response()->json([
            'totalRows' => $totalRows,
            'payments' => $data,
            'sale_returns' => $sale_returns,
            'clients' => $clients,
        ]);
    }

    //----------- Store New Payment Sale Return --------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', PaymentSaleReturns::class);
        
        if($request['montant'] > 0){
            \DB::transaction(function () use ($request) {
                $role = Auth::user()->roles()->first();
                $view_records = Role::findOrFail($role->id)->inRole('record_view');
                $SaleReturn = SaleReturn::findOrFail($request['sale_return_id']);
        
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === Sale Return->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $SaleReturn);
                }

                $total_paid = $SaleReturn->paid_amount + $request['montant'];
                $due = $SaleReturn->GrandTotal - $total_paid;

                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due !== $SaleReturn->GrandTotal) {
                    $payment_statut = 'partial';
                } else if ($due === $SaleReturn->GrandTotal) {
                    $payment_statut = 'unpaid';
                }

                PaymentSaleReturns::create([
                    'sale_return_id' => $request['sale_return_id'],
                    'Ref' => $this->getNumberOrder(),
                    'date' => $request['date'],
                    'Reglement' => $request['Reglement'],
                    'montant' => $request['montant'],
                    'change' => $request['change'],
                    'notes' => $request['notes'],
                    'user_id' => Auth::user()->id,
                ]);

                $SaleReturn->update([
                    'paid_amount' => $total_paid,
                    'payment_statut' => $payment_statut,
                ]);

            }, 10);
        }

        return response()->json(['success' => true, 'message' => 'Payment Create successfully'], 200);
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    //----------- Update Payment Sale Return --------------\\

    public function update(Request $request, $id)
    {
       
        $this->authorizeForUser($request->user('api'), 'update', PaymentSaleReturns::class);

        \DB::transaction(function () use ($id, $request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $payment = PaymentSaleReturns::findOrFail($id);
            
    
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === payment->id
                $this->authorizeForUser($request->user('api'), 'check_record', $payment);
            }

            $SaleReturn = SaleReturn::find($payment->sale_return_id);
            $old_total_paid = $SaleReturn->paid_amount - $payment->montant;
            $new_total_paid = $old_total_paid + $request['montant'];
            $due = $SaleReturn->GrandTotal - $new_total_paid;

            if ($due === 0.0 || $due < 0.0) {
                $payment_statut = 'paid';
            } else if ($due !== $SaleReturn->GrandTotal) {
                $payment_statut = 'partial';
            } else if ($due === $SaleReturn->GrandTotal) {
                $payment_statut = 'unpaid';
            }

            $payment->update([
                'date' => $request['date'],
                'Reglement' => $request['Reglement'],
                'montant' => $request['montant'],
                'change' => $request['change'],
                'notes' => $request['notes'],
            ]);
    
            $SaleReturn->update([
                'paid_amount' => $new_total_paid,
                'payment_statut' => $payment_statut,
            ]);
         
        }, 10);

        return response()->json(['success' => true, 'message' => 'Payment Update successfully'], 200);
    }

    //----------- Remove Payment Sale Return --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', PaymentSaleReturns::class);
        
        \DB::transaction(function () use ($id, $request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $payment = PaymentSaleReturns::findOrFail($id);
    
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === payment->id
                $this->authorizeForUser($request->user('api'), 'check_record', $payment);
            }

            $SaleReturn = SaleReturn::find($payment->sale_return_id);
            $total_paid = $SaleReturn->paid_amount - $payment->montant;
            $due = $SaleReturn->GrandTotal - $total_paid;

            if ($due === 0.0 || $due < 0.0) {
                $payment_statut = 'paid';
            } else if ($due !== $SaleReturn->GrandTotal) {
                $payment_statut = 'partial';
            } else if ($due === $SaleReturn->GrandTotal) {
                $payment_statut = 'unpaid';
            }

            PaymentSaleReturns::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            $SaleReturn->update([
                'paid_amount' => $total_paid,
                'payment_statut' => $payment_statut,
            ]);

        }, 10);

        return response()->json(['success' => true, 'message' => 'Payment Delete successfully'], 200);

    }

    //----------- Number Order Payment Sale Return --------------\\

    public function getNumberOrder()
    {
        $last = DB::table('payment_sale_returns')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'INV/RT_1111';
        }
        return $code;
    }

    //------------- Send Payment Sale Return on Email -----------\\

    public function SendEmail(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'view', PaymentSaleReturns::class);

        $payment['id'] = $request->id;
        $payment['Ref'] = $request->Ref;
        $settings = Setting::where('deleted_at', '=', null)->first();
        $payment['company_name'] = $settings->CompanyName;
        
        $pdf = $this->payment_return($request, $payment['id']);
        $this->Set_config_mail(); // Set_config_mail => BaseController
        $mail = Mail::to($request->to)->send(new PaymentReturn($payment, $pdf));
        return $mail;
    }

    //----------- Payment Sale Return PDF --------------\\

    public function payment_return(Request $request, $id)
    {
       
        $payment = PaymentSaleReturns::with('SaleReturn', 'SaleReturn.client')->findOrFail($id);

        $payment_data['return_Ref'] = $payment['SaleReturn']->Ref;
        $payment_data['client_name'] = $payment['SaleReturn']['client']->name;
        $payment_data['client_phone'] = $payment['SaleReturn']['client']->phone;
        $payment_data['client_adr'] = $payment['SaleReturn']['client']->adresse;
        $payment_data['client_email'] = $payment['SaleReturn']['client']->email;
        $payment_data['montant'] = $payment->montant;
        $payment_data['Ref'] = $payment->Ref;
        $payment_data['date'] = $payment->date;
        $payment_data['Reglement'] = $payment->Reglement;

        $helpers = new helpers();
        $settings = Setting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        $pdf = \PDF::loadView('pdf.Payment_Sale_Return', [
            'symbol' => $symbol,
            'setting' => $settings,
            'payment' => $payment_data,
        ]);

        return $pdf->download('Payment_Sale_Return.pdf');

    }

     //-------------------Sms Notifications -----------------\\
     public function Send_SMS(Request $request)
     {
        $payment = PaymentSaleReturns::with('SaleReturn', 'SaleReturn.client')->findOrFail($request->id);
        $settings = Setting::where('deleted_at', '=', null)->first();
        $gateway = sms_gateway::where('id' , $settings->sms_gateway)
        ->where('deleted_at', '=', null)->first();

         $url = url('/api/payment_return_sale_pdf/' . $request->id);
         $receiverNumber = $payment['SaleReturn']['client']->phone;
         $message = "Dear" .' '.$payment['SaleReturn']['client']->name." \n We are contacting you in regard to a Payment #".$payment['SaleReturn']->Ref.' '.$url.' '. "that has been created on your account. \n We look forward to conducting future business with you.";
         
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
