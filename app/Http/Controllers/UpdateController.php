<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TestDbController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use App\Models\Role;
use App\Models\Setting;

use Auth;
use App\Models\Permission;

class UpdateController extends Controller
{

    public function get_version_info(request $request){

        $this->authorizeForUser($request->user('api'), 'update', Setting::class);
        $version = $this->check();
        
        return response()->json($version);
    }


    /*
    * Return current version (as plain text).
    */
    public function getCurrentVersion(){
        // todo: env file version
        $version = File::get(base_path().'/version.txt');
        return $version;
    }

    /*
    * Check if a new Update exist.
    */
    public function check()
    {
        $lastVersionInfo = $this->getLastVersion();
        if( version_compare($lastVersionInfo['version'], $this->getCurrentVersion(), ">") )
            return $lastVersionInfo['version'];

        return '';
    }

    private function getLastVersion(){
        $content = file_get_contents('https://update-stocky.ui-lib.com/stocky_version.json');
        $content = json_decode($content, true);
        return $content;
    }

    
    public function viewStep1(Request $request)
    {
        $role = Auth::user()->roles()->first();
        $permission = Role::findOrFail($role->id)->inRole('setting_system');
        if($permission){
            return view('update.viewStep1');
        }
    }
    
    public function lastStep(Request $request)
    {
        $role = Auth::user()->roles()->first();
        $permission = Role::findOrFail($role->id)->inRole('setting_system');

        if($permission){
            ini_set('max_execution_time', 600); //600 seconds = 10 minutes 

            try {
            
                Artisan::call('config:cache');
                Artisan::call('config:clear');

                Artisan::call('migrate --force');

                $role = Role::findOrFail(1);
                $role->permissions()->detach();

                $permissions = array(
                    0 => 'view_employee',
                    1 => 'add_employee',
                    2 => 'edit_employee',
                    3 => 'delete_employee',
                    4 => 'company',
                    5 => 'department',
                    6 => 'designation',
                    7 => 'office_shift',
                    8 => 'attendance',
                    9 => 'leave',
                    10 => 'holiday',
                    11 => 'Top_products',
                    12 => 'Top_customers',
                    13 => 'shipment',
                    14 => 'users_report',
                    15 => 'stock_report',
                    16 => 'sms_settings',
                    17 => 'pos_settings',
                    18 => 'payment_gateway',
                    19 => 'mail_settings',
                    20 => 'dashboard',
                    21 => 'pay_due',
                    22 => 'pay_sale_return_due',
                    23 => 'pay_supplier_due',
                    24 => 'pay_purchase_return_due',
                );

                foreach ($permissions as $permission_slug) {
                    $perm = Permission::firstOrCreate(['name' => $permission_slug]);
                }

                $permissions_data = Permission::pluck('id')->toArray();
                $role->permissions()->attach($permissions_data);
                
            } catch (\Exception $e) {
                
                return $e->getMessage();
                
                return 'Something went wrong';
            }
            
            return view('update.finishedUpdate');
        }
    }

}
