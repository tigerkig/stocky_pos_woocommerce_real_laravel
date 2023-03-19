<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//--------------------------- Reset Password  ---------------------------

Route::group([
    'prefix' => 'password',
], function () {
    Route::post('create', 'PasswordResetController@create');
    Route::post('reset', 'PasswordResetController@reset');
});

Route::get("auth/get", "QuotationsController@get_auth");
Route::post("auth/update/{id}", "QuotationsController@update_auth");
Route::post("auth/create", "QuotationsController@create_auth");
Route::get("auth/get_woo", "QuotationsController@get_woo_auth");
Route::post("auth/update_woo", "QuotationsController@update_woo_auth");

Route::post('getAccessToken', 'AuthController@getAccessToken');
Route::middleware(['auth:api', 'Is_Active'])->group(function () {

    
    //-------------------------- Clear Cache ---------------------------

    Route::get("clear_cache", "SettingsController@Clear_Cache");

    //-------------------------- Reports ---------------------------

    Route::get("report/client", "ReportController@Client_Report");
    Route::get("report/client/{id}", "ReportController@Client_Report_detail");
    Route::get("report/client_sales", "ReportController@Sales_Client");
    Route::get("report/client_payments", "ReportController@Payments_Client");
    Route::get("report/client_quotations", "ReportController@Quotations_Client");
    Route::get("report/client_returns", "ReportController@Returns_Client");
    Route::get("report/provider", "ReportController@Providers_Report");
    Route::get("report/provider/{id}", "ReportController@Provider_Report_detail");
    Route::get("report/provider_purchases", "ReportController@Purchases_Provider");
    Route::get("report/provider_payments", "ReportController@Payments_Provider");
    Route::get("report/provider_returns", "ReportController@Returns_Provider");
    Route::get("report/sales", "ReportController@Report_Sales");
    Route::get("report/purchases", "ReportController@Report_Purchases");
    Route::get("report/get_last_sales", "ReportController@Get_last_Sales");
    Route::get("report/stock_alert", "ReportController@Products_Alert");
    Route::get("report/payment_chart", "ReportController@Payment_chart");
    Route::get("report/warehouse_report", "ReportController@Warehouse_Report");
    Route::get("report/sales_warehouse", "ReportController@Sales_Warehouse");
    Route::get("report/quotations_warehouse", "ReportController@Quotations_Warehouse");
    Route::get("report/returns_sale_warehouse", "ReportController@Returns_Sale_Warehouse");
    Route::get("report/returns_purchase_warehouse", "ReportController@Returns_Purchase_Warehouse");
    Route::get("report/expenses_warehouse", "ReportController@Expenses_Warehouse");
    Route::get("report/warhouse_count_stock", "ReportController@Warhouse_Count_Stock");
    Route::get("report/report_today", "ReportController@report_today");
    Route::get("report/count_quantity_alert", "ReportController@count_quantity_alert");
    Route::get("report/profit_and_loss", "ReportController@ProfitAndLoss");
    Route::get("chart/report_with_echart", "ReportController@report_with_echart");
    Route::get("report/report_dashboard", "ReportController@report_dashboard");
    Route::get("report/top_products", "ReportController@report_top_products");
    Route::get("report/top_customers", "ReportController@report_top_customers");

    Route::get("report/users", "ReportController@users_Report");
    Route::get("report/stock", "ReportController@stock_Report");
    Route::get("report/get_sales_by_user", "ReportController@get_sales_by_user");
    Route::get("report/get_quotations_by_user", "ReportController@get_quotations_by_user");
    Route::get("report/get_sales_return_by_user", "ReportController@get_sales_return_by_user");
    Route::get("report/get_purchases_by_user", "ReportController@get_purchases_by_user");
    Route::get("report/get_purchase_return_by_user", "ReportController@get_purchase_return_by_user");
    Route::get("report/get_transfer_by_user", "ReportController@get_transfer_by_user");
    Route::get("report/get_adjustment_by_user", "ReportController@get_adjustment_by_user");

    Route::get("report/get_sales_by_product", "ReportController@get_sales_by_product");
    Route::get("report/get_quotations_by_product", "ReportController@get_quotations_by_product");

    Route::get("report/get_sales_return_by_product", "ReportController@get_sales_return_by_product");
    Route::get("report/get_purchases_by_product", "ReportController@get_purchases_by_product");
    Route::get("report/get_purchase_return_by_product", "ReportController@get_purchase_return_by_product");
    Route::get("report/get_transfer_by_product", "ReportController@get_transfer_by_product");
    Route::get("report/get_adjustment_by_product", "ReportController@get_adjustment_by_product");
    Route::get("report/client_pdf/{id}", "ReportController@download_report_client_pdf");
    Route::get("report/provider_pdf/{id}", "ReportController@download_report_provider_pdf");

    //------------------------------Employee------------------------------------\\

    Route::resource('employees', 'hrm\EmployeesController');
    Route::post('employees/import/csv', 'hrm\EmployeesController@import_employees');
    Route::post('employees/delete/by_selection', 'hrm\EmployeesController@delete_by_selection');
    Route::get("get_employees_by_department", "hrm\EmployeesController@Get_employees_by_department");
    Route::put("update_social_profile/{id}", "hrm\EmployeesController@update_social_profile");
    Route::get("get_experiences_by_employee", "hrm\EmployeesController@get_experiences_by_employee");
    Route::get("get_accounts_by_employee", "hrm\EmployeesController@get_accounts_by_employee");

    //------------------------------- Employee Experience ----------------\\
    //--------------------------------------------------------------------\\
    
    Route::resource('work_experience', 'hrm\EmployeeExperienceController');


    //------------------------------- Employee Accounts bank ----------------\\
    //--------------------------------------------------------------------\\
    
    Route::resource('employee_account', 'hrm\EmployeeAccountController');


     //------------------------------- company --------------------------\\
    //--------------------------------------------------------------------\\
    Route::resource('company', 'hrm\CompanyController');
    Route::get("get_all_company", "hrm\CompanyController@Get_all_Company");
    Route::post("company/delete/by_selection", "hrm\CompanyController@delete_by_selection");


     //------------------------------- departments --------------------------\\
    //--------------------------------------------------------------------\\
    Route::resource('departments', 'hrm\DepartmentsController');
    Route::get("get_all_departments", "hrm\DepartmentsController@Get_all_Departments");
    Route::get("get_departments_by_company", "hrm\DepartmentsController@Get_departments_by_company")->name('Get_departments_by_company');
    Route::post("departments/delete/by_selection", "hrm\DepartmentsController@delete_by_selection");

    //------------------------------- designations --------------------------\\
    //--------------------------------------------------------------------\\
    Route::resource('designations', 'hrm\DesignationsController');
    Route::get("get_designations_by_department", "hrm\DesignationsController@Get_designations_by_department");
    Route::post("designations/delete/by_selection", "hrm\DesignationsController@delete_by_selection");

    //------------------------------- office_shift ------------------\\
    //----------------------------------------------------------------\\

    Route::resource('office_shift', 'hrm\OfficeShiftController');
    Route::post("office_shift/delete/by_selection", "hrm\OfficeShiftController@delete_by_selection");

    //------------------------------- Attendances ------------------------\\
    //--------------------------------------------------------------------\\
    Route::resource('attendances', 'hrm\AttendancesController');
    Route::get("daily_attendance", "hrm\AttendancesController@daily_attendance")->name('daily_attendance');
    Route::post('attendance_by_employee/{id}', 'hrm\EmployeeSessionController@attendance_by_employee')->name('attendance_by_employee.post');
    Route::post("attendances/delete/by_selection", "hrm\AttendancesController@delete_by_selection");


    
    //------------------------------- Request leave  -----------------------\\
    //----------------------------------------------------------------\\

    Route::resource('leave', 'hrm\LeaveController');
    Route::resource('leave_type', 'hrm\LeaveTypeController');
    Route::post("leave/delete/by_selection", "hrm\LeaveController@delete_by_selection");
    Route::post("leave_type/delete/by_selection", "hrm\LeaveTypeController@delete_by_selection");


     //------------------------------- holiday ----------------------\\
    //----------------------------------------------------------------\\

    Route::resource('holiday', 'hrm\HolidayController');
    Route::post("holiday/delete/by_selection", "hrm\HolidayController@delete_by_selection");

    //------------------------------- core --------------------------\\
    //--------------------------------------------------------------------\\

    Route::prefix('core')->group(function () {

       Route::get("get_departments_by_company", "hrm\CoreController@Get_departments_by_company");
       Route::get("get_designations_by_department", "hrm\CoreController@Get_designations_by_department");
       Route::get("get_office_shift_by_company", "hrm\CoreController@Get_office_shift_by_company");
       Route::get("get_employees_by_company", "hrm\CoreController@Get_employees_by_company");

    });


    //------------------------------- CLIENTS --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('clients', 'ClientController');
    Route::post('clients/import/csv', 'ClientController@import_clients');
    Route::get('get_clients_without_paginate', 'ClientController@Get_Clients_Without_Paginate');
    Route::post('clients/delete/by_selection', 'ClientController@delete_by_selection');
    Route::post('clients_pay_due', 'ClientController@clients_pay_due');
    Route::post('clients_pay_return_due', 'ClientController@pay_sale_return_due');

    //------------------------------- Providers --------------------------\\
    //--------------------------------------------------------------------\\

    Route::resource('providers', 'ProvidersController');
    Route::post('providers/import/csv', 'ProvidersController@import_providers');
    Route::post('providers/delete/by_selection', 'ProvidersController@delete_by_selection');
    Route::post('pay_supplier_due', 'ProvidersController@pay_supplier_due');
    Route::post('pay_purchase_return_due', 'ProvidersController@pay_purchase_return_due');

    //---------------------- POS (point of sales) ----------------------\\
    //------------------------------------------------------------------\\

    Route::post('pos/create_pos', 'PosController@CreatePOS');
    Route::get('pos/get_products_pos', 'PosController@GetProductsByParametre');
    Route::get('pos/data_create_pos', 'PosController@GetELementPos');

    //------------------------------- PRODUCTS --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('products', 'ProductsController');
    Route::post('products/import/csv', 'ProductsController@import_products');
    Route::get('get_Products_by_warehouse/{id}', 'ProductsController@Products_by_Warehouse');
    Route::get('get_product_detail/{id}', 'ProductsController@Get_Products_Details');
    Route::get('get_products_stock_alerts', 'ProductsController@Products_Alert');
    Route::get('barcode_create_page', 'ProductsController@Get_element_barcode');
    Route::post('products/delete/by_selection', 'ProductsController@delete_by_selection');


    //------------------------------- Category --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('categories', 'CategorieController');
    Route::post('categories/delete/by_selection', 'CategorieController@delete_by_selection');

    //------------------------------- Units --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('units', 'UnitsController');
    Route::get('get_sub_units_by_base', 'UnitsController@Get_Units_SubBase');
    Route::get('get_units', 'UnitsController@Get_sales_units');

    //------------------------------- Brands--------------------------\\
    //------------------------------------------------------------------\\
    Route::resource('brands', 'BrandsController');
    Route::post('brands/delete/by_selection', 'BrandsController@delete_by_selection');

    //------------------------------- Currencies --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('currencies', 'CurrencyController');
    Route::post('currencies/delete/by_selection', 'CurrencyController@delete_by_selection');


    //------------------------------- WAREHOUSES --------------------------\\

    Route::resource('warehouses', 'WarehouseController');
    Route::post('warehouses/delete/by_selection', 'WarehouseController@delete_by_selection');

    //------------------------------- PURCHASES --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('purchases', 'PurchasesController');
    Route::get('get_payments_by_purchase/{id}', 'PurchasesController@Get_Payments');
    Route::post('purchase_send_email', 'PurchasesController@Send_Email');
    Route::post('purchase_send_sms', 'PurchasesController@Send_SMS');
    Route::post('purchases_delete_by_selection', 'PurchasesController@delete_by_selection');
    Route::get('get_Products_by_purchase/{id}', 'PurchasesController@get_Products_by_purchase');


    //------------------------------- Payments  Purchases --------------------------\\
    //------------------------------------------------------------------------------\\

    Route::resource('payment_purchase', 'PaymentPurchasesController');
    Route::get('payment_purchase_get_number', 'PaymentPurchasesController@getNumberOrder');
    Route::post('payment_purchase_send_email', 'PaymentPurchasesController@SendEmail');
    Route::post('payment_purchase_send_sms', 'PaymentPurchasesController@Send_SMS');

    //-------------------------------  Sales --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('sales', 'SalesController');
    Route::get('convert_to_sale_data/{id}', 'SalesController@Elemens_Change_To_Sale');
    Route::get('get_payments_by_sale/{id}', 'SalesController@Payments_Sale');
    Route::post('sales_send_email', 'SalesController@Send_Email');
    Route::post('sales_send_sms', 'SalesController@Send_SMS');
    Route::post('sales_delete_by_selection', 'SalesController@delete_by_selection');
    Route::get('get_Products_by_sale/{id}', 'SalesController@get_Products_by_sale');

    //-------------------------------  Shipments --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('shipments', 'ShipmentController');


    //------------------------------- Payments  Sales --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('payment_sale', 'PaymentSalesController');
    Route::get('payment_sale_get_number', 'PaymentSalesController@getNumberOrder');
    Route::post('payment_sale_send_email', 'PaymentSalesController@SendEmail');
    Route::post('payment_sale_send_sms', 'PaymentSalesController@Send_SMS');

    //------------------------------- Expenses --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('expenses', 'ExpensesController');
    Route::post('expenses_delete_by_selection', 'ExpensesController@delete_by_selection');


    //------------------------------- Expenses Category--------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('expenses_category', 'CategoryExpenseController');
    Route::post('expenses_category_delete_by_selection', 'CategoryExpenseController@delete_by_selection');


    //------------------------------- Quotations --------------------------\\
    //------------------------------------------------------------------\\
    Route::resource('quotations', 'QuotationsController');
    Route::post('quotations_send_email', 'QuotationsController@SendEmail');
    Route::post('quotations_send_sms', 'QuotationsController@Send_SMS');
    Route::post('quotations_delete_by_selection', 'QuotationsController@delete_by_selection');

    //------------------------------- Sales Return --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('returns/sale', 'SalesReturnController');
    Route::post('returns/sale/send/email', 'SalesReturnController@Send_Email');
    Route::post('returns/sale/send/sms', 'SalesReturnController@Send_SMS');
    Route::get('returns/sale/payment/{id}', 'SalesReturnController@Payment_Returns');
    Route::post('returns/sale/delete/by_selection', 'SalesReturnController@delete_by_selection');
    Route::get('returns/sale/create_sell_return/{id}', 'SalesReturnController@create_sell_return');
    Route::get('returns/sale/edit_sell_return/{id}/{sale_id}', 'SalesReturnController@edit_sell_return');

    //------------------------------- Purchases Return --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('returns/purchase', 'PurchasesReturnController');
    Route::post('returns/purchase/send/email', 'PurchasesReturnController@Send_Email');
    Route::post('returns/purchase/send/sms', 'PurchasesReturnController@Send_SMS');
    Route::get('returns/purchase/payment/{id}', 'PurchasesReturnController@Payment_Returns');
    Route::post('returns/purchase/delete/by_selection', 'PurchasesReturnController@delete_by_selection');
    Route::get('returns/purchase/create_purchase_return/{id}', 'PurchasesReturnController@create_purchase_return');
    Route::get('returns/purchase/edit_purchase_return/{id}/{purchase_id}', 'PurchasesReturnController@edit_purchase_return');
    
    //------------------------------- Payment Sale Returns --------------------------\\
    //--------------------------------------------------------------------------------\\

    Route::resource('payment/returns_sale', 'PaymentSaleReturnsController');
    Route::get('payment/returns_sale/Number/order', 'PaymentSaleReturnsController@getNumberOrder');
    Route::post('payment/returns_sale/send/email', 'PaymentSaleReturnsController@SendEmail');
    Route::post('payment/returns_sale/send/sms', 'PaymentSaleReturnsController@Send_SMS');

    //------------------------------- Payments Purchase Returns --------------------------\\
    //---------------------------------------------------------------------------------------\\

    Route::resource('payment/returns_purchase', 'PaymentPurchaseReturnsController');
    Route::get('payment/returns_purchase/Number/Order', 'PaymentPurchaseReturnsController@getNumberOrder');
    Route::post('payment/returns_purchase/send/email', 'PaymentPurchaseReturnsController@SendEmail');
    Route::post('payment/returns_purchase/send/sms', 'PaymentPurchaseReturnsController@Send_SMS');

    //------------------------------- Adjustments --------------------------\\
    //------------------------------------------------------------------\\

    Route::resource('adjustments', 'AdjustmentController');
    Route::get('adjustments/detail/{id}', 'AdjustmentController@Adjustment_detail');
    Route::post('adjustments/delete/by_selection', 'AdjustmentController@delete_by_selection');

    //------------------------------- Transfers --------------------------\\
    //--------------------------------------------------------------------\\
    Route::resource('transfers', 'TransferController');
    Route::post('transfers/delete/by_selection', 'TransferController@delete_by_selection');

    //------------------------------- Users --------------------------\\
    //------------------------------------------------------------------\\

    Route::get('get_user_auth', 'UserController@GetUserAuth');
    Route::resource('users', 'UserController');
    Route::put('users_switch_activated/{id}', 'UserController@IsActivated');
    Route::get('Get_user_profile', 'UserController@GetInfoProfile');
    Route::put('update_user_profile/{id}', 'UserController@updateProfile');

    //------------------------------- Permission Groups user -----------\\
    //------------------------------------------------------------------\\

    Route::resource('roles', 'PermissionsController');
    Route::resource('roles/check/create_page', 'PermissionsController@Check_Create_Page');
    Route::post('roles/delete/by_selection', 'PermissionsController@delete_by_selection');

    
    //------------------------------- Settings ------------------------\\
    //------------------------------------------------------------------\\    
    Route::resource('settings', 'SettingsController');
    Route::get('get_Settings_data', 'SettingsController@getSettings');
    Route::put('pos_settings/{id}', 'SettingsController@update_pos_settings');
    Route::get('get_pos_Settings', 'SettingsController@get_pos_Settings');

    //------------------------------- Mail Settings ------------------------\\

    Route::put('update_config_mail/{id}', 'MailSettingsController@update_config_mail');
    Route::get('get_config_mail', 'MailSettingsController@get_config_mail');

    //------------------------------- SMS Settings ------------------------\\

    Route::get('get_sms_config', 'Sms_SettingsController@get_sms_config');
    Route::post('update_twilio_config', 'Sms_SettingsController@update_twilio_config');
    Route::post('update_nexmo_config', 'Sms_SettingsController@update_nexmo_config');

    //------------------------------- Payment_gateway Settings ------------------------\\

    Route::post('payment_gateway', 'Payment_gateway_SettingsController@Update_payment_gateway');
    Route::get('get_payment_gateway', 'Payment_gateway_SettingsController@Get_payment_gateway');

    //------------------------------- Update Settings ------------------------\\

    Route::get('get_version_info', 'UpdateController@get_version_info');
    
    //------------------------------- Backup --------------------------\\
    //------------------------------------------------------------------\\
    
    Route::get("get_backup", "BackupController@Get_Backup");
    Route::get("generate_new_backup", "BackupController@Generate_Backup");
    Route::delete("delete_backup/{name}", "BackupController@Delete_Backup");

});

    //-------------------------------  Print & PDF ------------------------\\
    //------------------------------------------------------------------\\

    Route::get('sale_pdf/{id}', 'SalesController@Sale_PDF');
    Route::get('quote_pdf/{id}', 'QuotationsController@Quotation_pdf');
    Route::get('purchase_pdf/{id}', 'PurchasesController@Purchase_pdf');
    Route::get('return_sale_pdf/{id}', 'SalesReturnController@Return_pdf');
    Route::get('return_purchase_pdf/{id}', 'PurchasesReturnController@Return_pdf');
    Route::get('payment_purchase_pdf/{id}', 'PaymentPurchasesController@Payment_purchase_pdf');
    Route::get('payment_return_sale_pdf/{id}', 'PaymentSaleReturnsController@payment_return');
    Route::get('payment_return_purchase_pdf/{id}', 'PaymentPurchaseReturnsController@payment_return');
    Route::get('payment_sale_pdf/{id}', 'PaymentSalesController@payment_sale');
    Route::get('sales_print_invoice/{id}', 'SalesController@Print_Invoice_POS');
