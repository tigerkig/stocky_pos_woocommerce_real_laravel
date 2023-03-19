<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\ExpenseCategory'           => 'App\Policies\ExpenseCategoryPolicy',
        'App\Models\Purchase'                  => 'App\Policies\PurchasePolicy',
        'App\Models\Adjustment'                => 'App\Policies\AdjustmentPolicy',
        'App\Models\Product'                   => 'App\Policies\ProductPolicy',
        'App\Models\Brand'                     => 'App\Policies\BrandPolicy',
        'App\Models\Category'                  => 'App\Policies\CategoryPolicy',
        'App\Models\Client'                    => 'App\Policies\ClientPolicy',
        'App\Models\Currency'                  => 'App\Policies\CurrencyPolicy',
        'App\Models\Expense'                   => 'App\Policies\ExpensePolicy',
        'App\Models\PaymentPurchase'           => 'App\Policies\PaymentPurchasePolicy',
        'App\Models\PaymentSaleReturns'        => 'App\Policies\PaymentSaleReturnsPolicy',
        'App\Models\PaymentPurchaseReturns'    => 'App\Policies\PaymentPurchaseReturnsPolicy',
        'App\Models\PaymentSale'               => 'App\Policies\PaymentSalePolicy',
        'App\Models\Warehouse'                 => 'App\Policies\WarehousePolicy',
        'App\Models\Provider'                  => 'App\Policies\ProviderPolicy',
        'App\Models\Quotation'                 => 'App\Policies\QuotationPolicy',
        'App\Models\SaleReturn'                => 'App\Policies\SaleReturnPolicy',
        'App\Models\PurchaseReturn'            => 'App\Policies\PurchaseReturnPolicy',
        'App\Models\Role'                      => 'App\Policies\RolePolicy',
        'App\Models\Server'                    => 'App\Policies\ServerPolicy',
        'App\Models\Setting'                   => 'App\Policies\SettingPolicy',
        'App\Models\Transfer'                  => 'App\Policies\TransferPolicy',
        'App\Models\Unit'                      => 'App\Policies\UnitPolicy',
        'App\Models\Sale'                      => 'App\Policies\SalePolicy',
        'App\Models\User'                      => 'App\Policies\UserPolicy',
        'App\Models\Shipment'                  => 'App\Policies\ShipmentPolicy',
        
        //hrm
        'App\Models\Employee'                  => 'App\Policies\EmployeePolicy',
        'App\Models\Company'                   => 'App\Policies\CompanyPolicy',
        'App\Models\Department'                => 'App\Policies\DepartmentPolicy',
        'App\Models\Designation'               => 'App\Policies\DesignationPolicy',
        'App\Models\OfficeShift'               => 'App\Policies\Office_ShiftPolicy',
        'App\Models\Attendance'                => 'App\Policies\AttendancePolicy',
        'App\Models\Leave'                     => 'App\Policies\LeavePolicy',
        'App\Models\Holiday'                   => 'App\Policies\HolidayPolicy',

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(){
        $this->registerPolicies();

        Passport::routes();
    
    }
}
