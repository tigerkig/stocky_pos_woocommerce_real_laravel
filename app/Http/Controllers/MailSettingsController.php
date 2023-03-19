<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;


class MailSettingsController extends Controller
{

    //-------------- Get mail_settings ---------------\\

      public function get_config_mail(Request $request)
      {
          $this->authorizeForUser($request->user('api'), 'mail_settings', Setting::class);
  
          $server = Server::where('deleted_at', '=', null)->first();
  
          if ($server) {
              return response()->json(['server' => $server], 200);
          } else {
              return response()->json(['statut' => 'error'], 500);
          }
      }

    
    //-------------- Update mail settings ---------------\\

    public function update_config_mail(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'mail_settings', Setting::class);

        Server::whereId($id)->update([
            'mail_mailer' => $request['mail_mailer'],
            'host' => $request['host'],
            'port' => $request['port'],
            'sender_name' => $request['sender_name'],
            'username' => $request['username'],
            'password' => $request['password'],
            'encryption' => $request['encryption'],
        ]);

        return response()->json(['success' => true]);

    }

  

}
