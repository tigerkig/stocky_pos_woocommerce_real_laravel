<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\sms_gateway;
use File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Nexmo\Laravel\Facade\Nexmo;

class Sms_SettingsController extends Controller
{


    //-------------- Get_sms_config ---------------\\

    public function get_sms_config(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'sms_settings', Setting::class);
        Artisan::call('config:cache');
        Artisan::call('config:clear');

        $twilio['TWILIO_SID'] = env('TWILIO_SID');
        $twilio['TWILIO_FROM'] = env('TWILIO_FROM');
        $twilio['TWILIO_TOKEN'] = '';

        $nexmo['nexmo_key'] = env('NEXMO_KEY');
        $nexmo['nexmo_secret'] = env('NEXMO_SECRET');
        $nexmo['nexmo_from'] = env('NEXMO_FROM');

        return response()->json(['twilio' => $twilio,'nexmo' => $nexmo ], 200);
    }


    //-------------- update_twilio_config ---------------\\

    public function update_twilio_config(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'sms_settings', Setting::class);

        
            $this->setEnvironmentValue([
                'TWILIO_SID' => $request['TWILIO_SID'] !== null?'"' . $request['TWILIO_SID'] . '"':'"' . env('TWILIO_SID') . '"',
                'TWILIO_TOKEN' => $request['TWILIO_TOKEN'] !== null?'"' . $request['TWILIO_TOKEN'] . '"':'"' . env('TWILIO_TOKEN') . '"',
                'TWILIO_FROM' => $request['TWILIO_FROM'] !== null?'"' . $request['TWILIO_FROM'] . '"':'"' . env('TWILIO_FROM') . '"',
            ]);

            Artisan::call('config:cache');
            Artisan::call('config:clear');

        return response()->json(['success' => true]);

    }
    



     //-------------- Update nexmo_sms_config ---------------\\

     public function update_nexmo_config(Request $request)
     {
         $this->authorizeForUser($request->user('api'), 'sms_settings', Setting::class);

        $this->setEnvironmentValue([
            'NEXMO_KEY' => $request['nexmo_key'] !== null?'"' . $request['nexmo_key'] . '"':'"' . env('NEXMO_KEY') . '"',
            'NEXMO_SECRET' => $request['nexmo_secret'] !== null?'"' . $request['nexmo_secret'] . '"':'"' . env('NEXMO_SECRET') . '"',
            'NEXMO_FROM' => $request['nexmo_from'] !== null?'"' . $request['nexmo_from'] . '"':'"' . env('NEXMO_FROM') . '"',
        ]);

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
