<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;


class Payment_gateway_SettingsController extends Controller
{

    //-------------- Get Payment Gateway ---------------\\

    public function Get_payment_gateway(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'payment_gateway', Setting::class);
        Artisan::call('config:cache');
        Artisan::call('config:clear');

        $item['stripe_key'] = env('STRIPE_KEY');
        $item['stripe_secret'] = '';
        $item['deleted'] = false;

        return response()->json(['gateway' => $item], 200);
    }

      //-------------- Update  Payment Gateway ---------------\\

      public function Update_payment_gateway(Request $request)
      {
          $this->authorizeForUser($request->user('api'), 'payment_gateway', Setting::class);

          if($request['deleted'] == 'true'){
            $this->setEnvironmentValue([
                'STRIPE_KEY' => '',
                'STRIPE_SECRET' => '',
            ]);

        }else{
            $this->setEnvironmentValue([
                'STRIPE_KEY' => $request['stripe_key'] !== null?'"' . $request['stripe_key'] . '"':'',
                'STRIPE_SECRET' => $request['stripe_secret'] !== null?'"' . $request['stripe_secret'] . '"':'"' . env('STRIPE_SECRET') . '"',
            ]);
        }

            Artisan::call('config:cache');
            Artisan::call('config:clear');

        return response()->json(['success' => true]);

      }

   
    //-------------- Set Environment Value ---------------\\

    public function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        $str .= "\r\n";
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
    
                $keyPosition = strpos($str, "$envKey=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
    
                if (is_bool($keyPosition) && $keyPosition === false) {
                    // variable doesnot exist
                    $str .= "$envKey=$envValue";
                    $str .= "\r\n";
                } else {
                    // variable exist                    
                    $str = str_replace($oldLine, "$envKey=$envValue", $str);
                }            
            }
        }
    
        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) {
            return false;
        }
    
        app()->loadEnvironmentFrom($envFile);    
    
        return true;
    }

}
