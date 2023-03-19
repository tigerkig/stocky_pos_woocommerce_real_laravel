<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Nwidart\Modules\Facades\Module;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

     /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(\Illuminate\Http\Request $request)
    {
        return ['email' => $request->{$this->username()}, 'password' => $request->password, 'statut' => 1];
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(){
        $allModules = Module::all();
        $allEnabledModules = Module::allEnabled();

        $ModulesInstalled = [];
        $ModulesEnabled = [];

        foreach($allModules as $key => $modules_name){
            $ModulesInstalled[] = $key;
        }

        foreach($allEnabledModules as $key => $modules_name){
            $ModulesEnabled[] = $key;
        }

        return view('auth.login',[
            'ModulesInstalled' => $ModulesInstalled, 
            'ModulesEnabled' => $ModulesEnabled, 
        ]);
    }
}
