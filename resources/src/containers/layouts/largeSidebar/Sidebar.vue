<template>
  <div
    class="side-content-wrap"
    @mouseenter="isMenuOver = true"
    @mouseleave="isMenuOver = false"
    @touchstart="isMenuOver = true"
  >
    <vue-perfect-scrollbar
      :settings="{ suppressScrollX: true, wheelPropagation: false }"
      :class="{ open: getSideBarToggleProperties.isSideNavOpen }"
      ref="myData"
      class="sidebar-left rtl-ps-none ps scroll"
    >
      <div>
        <ul class="navigation-left">
          <li
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'dashboard' }"
            class="nav-item"
            data-item="dashboard"
          >
            <router-link tag="a" class="nav-item-hold" to="/app/dashboard">
              <i class="nav-icon i-Bar-Chart"></i>
              <span class="nav-text">{{ $t("dashboard") }}</span>
            </router-link>
          </li>
          <li
            v-show="currentUserPermissions 
            && (currentUserPermissions.includes('products_add')
            || currentUserPermissions.includes('products_view') 
            || currentUserPermissions.includes('barcode_view')
             || currentUserPermissions.includes('brand') 
             || currentUserPermissions.includes('unit')  
             || currentUserPermissions.includes('category'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'products' }"
            data-item="products"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Library-2"></i>
              <span class="nav-text">{{$t('Products')}}</span>
            </a>
            <div class="triangle"></div>
          </li>
          <li
            v-show="currentUserPermissions 
              && (currentUserPermissions.includes('adjustment_view')
              || currentUserPermissions.includes('adjustment_add'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'adjustments' }"
            data-item="adjustments"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Edit-Map"></i>
              <span class="nav-text">{{$t('StockAdjustement')}}</span>
            </a>
            <div class="triangle"></div>
          </li>

        

         
          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('Quotations_view') 
                      || currentUserPermissions.includes('Quotations_add'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'quotations' }"
            data-item="quotations"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Checkout-Basket"></i>
              <span class="nav-text">{{$t('Quotations')}}</span>
            </a>
            <div class="triangle"></div>
          </li>
          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('Purchases_view') 
                        || currentUserPermissions.includes('Purchases_add'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'purchases' }"
            data-item="purchases"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Receipt"></i>
              <span class="nav-text">{{$t('Purchases')}}</span>
            </a>
            <div class="triangle"></div>
          </li>
          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('Sales_view') 
                        || currentUserPermissions.includes('Sales_add')
                        || currentUserPermissions.includes('Pos_view')
                        || currentUserPermissions.includes('shipment'))"
            class="nav-item"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'sales' }"
            data-item="sales"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Full-Cart"></i>
              <span class="nav-text">{{$t('Sales')}}</span>
            </a>
            <div class="triangle"></div>
          </li>

            <li
            v-if="currentUserPermissions && currentUserPermissions.includes('Sale_Returns_view')"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'sale_return' }"
            class="nav-item"
            data-item="sale_return"
          >

           <router-link tag="a" class="nav-item-hold" to="/app/sale_return/list">
              <i class="nav-icon i-Right"></i>
              <span class="nav-text">{{ $t("SalesReturn") }}</span>
            </router-link>
          </li>

          <li
            v-if="currentUserPermissions && currentUserPermissions.includes('Purchase_Returns_view')"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'purchase_return' }"
            class="nav-item"
            data-item="purchase_return"
          >
          <router-link tag="a" class="nav-item-hold" to="/app/purchase_return/list">
              <i class="nav-icon i-Left"></i>
              <span class="nav-text">{{ $t("PurchasesReturn") }}</span>
            </router-link>
          </li>

            <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('company')
                     || currentUserPermissions.includes('department')
                     || currentUserPermissions.includes('designation')
                     || currentUserPermissions.includes('office_shift')
                     || currentUserPermissions.includes('view_employee')
                     || currentUserPermissions.includes('attendance')
                     || currentUserPermissions.includes('leave')
                     || currentUserPermissions.includes('holiday'))"
                     
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'hrm' }"
            class="nav-item"
            data-item="hrm"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Library"></i>
              <span class="nav-text">{{$t('hrm')}}</span>
            </a>
            <div class="triangle"></div>
          </li>

           <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('transfer_view')
                     || currentUserPermissions.includes('transfer_add'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'transfers' }"
            data-item="transfers"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Back"></i>
              <span class="nav-text">{{$t('StockTransfers')}}</span>
            </a>
            <div class="triangle"></div>
          </li>
          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('expense_view')
              || currentUserPermissions.includes('expense_add'))"
            @mouseenter="toggleSubMenu"
            class="nav-item"
            :class="{ active: selectedParentMenu == 'expenses' }"
            data-item="expenses"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Wallet"></i>
              <span class="nav-text">{{$t('Expenses')}}</span>
            </a>
            <div class="triangle"></div>
          </li>


          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('Customers_view') 
                        ||currentUserPermissions.includes('Suppliers_view')
                        ||currentUserPermissions.includes('users_view'))"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'People' }"
            class="nav-item"
            data-item="People"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Business-Mens"></i>
              <span class="nav-text">{{$t('People')}}</span>
            </a>
            <div class="triangle"></div>
          </li>

         

          <li
            v-show="currentUserPermissions && (currentUserPermissions.includes('setting_system') 
                        || currentUserPermissions.includes('sms_settings')
                        || currentUserPermissions.includes('pos_settings')
                        || currentUserPermissions.includes('payment_gateway')
                        || currentUserPermissions.includes('mail_settings')
                        || currentUserPermissions.includes('warehouse') 
                        || currentUserPermissions.includes('backup') 
                        || currentUserPermissions.includes('currency') 
                        || currentUserPermissions.includes('permissions_view'))"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'settings' }"
            class="nav-item"
            data-item="settings"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="nav-text">{{$t('Settings')}}</span>
            </a>
            <div class="triangle"></div>
          </li>

          <li
            v-show="currentUserPermissions && 
                     (currentUserPermissions.includes('Reports_payments_Sales') 
                     || currentUserPermissions.includes('Reports_payments_Purchases')
                     || currentUserPermissions.includes('Reports_payments_Sale_Returns')
                     || currentUserPermissions.includes('Reports_payments_purchase_Return')
                     || currentUserPermissions.includes('Warehouse_report')
                     || currentUserPermissions.includes('Reports_profit')
                     || currentUserPermissions.includes('Reports_purchase') 
                     || currentUserPermissions.includes('Reports_quantity_alerts')
                     || currentUserPermissions.includes('Reports_sales') 
                     || currentUserPermissions.includes('Reports_suppliers')
                     || currentUserPermissions.includes('Reports_customers')
                     || currentUserPermissions.includes('Top_products')
                     || currentUserPermissions.includes('Top_customers')
                     || currentUserPermissions.includes('users_report')
                     || currentUserPermissions.includes('stock_report'))"
            @mouseenter="toggleSubMenu"
            :class="{ active: selectedParentMenu == 'reports' }"
            class="nav-item"
            data-item="reports"
            :data-submenu="true"
          >
            <a class="nav-item-hold" href="#">
              <i class="nav-icon i-Line-Chart"></i>
              <span class="nav-text">{{$t('Reports')}}</span>
            </a>
            <div class="triangle"></div>
          </li>    
                
        </ul>
      </div>
    </vue-perfect-scrollbar>

    <vue-perfect-scrollbar
      :class="{ open: getSideBarToggleProperties.isSecondarySideNavOpen }"
      :settings="{ suppressScrollX: true, wheelPropagation: false }"
      class="sidebar-left-secondary ps rtl-ps-none"
    >
      <div ref="sidebarChild">
        <ul
          class="childNav d-none"
          data-parent="products"
          :class="{ 'd-block': selectedParentMenu == 'products' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('products_add')"
          >
            <router-link tag="a" class to="/app/products/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('AddProduct')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('products_view')"
          >
            <router-link tag="a" class to="/app/products/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('productsList')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('barcode_view')"
          >
            <router-link tag="a" class to="/app/products/barcode">
              <i class="nav-icon i-Bar-Code"></i>
              <span class="item-name">{{$t('Printbarcode')}}</span>
            </router-link>
          </li>
           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('category')"
          >
            <router-link tag="a" class to="/app/products/Categories">
              <i class="nav-icon i-Duplicate-Layer"></i>
              <span class="item-name">{{$t('Categories')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('brand')"
          >
            <router-link tag="a" class to="/app/products/Brands">
              <i class="nav-icon i-Bookmark"></i>
              <span class="item-name">{{$t('Brand')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('unit')"
          >
            <router-link tag="a" class to="/app/products/Units">
              <i class="nav-icon i-Quotes"></i>
              <span class="item-name">{{$t('Units')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="adjustments"
          :class="{ 'd-block': selectedParentMenu == 'adjustments' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('adjustment_add')"
          >
            <router-link tag="a" class to="/app/adjustments/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('CreateAdjustment')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('adjustment_view')"
          >
            <router-link tag="a" class to="/app/adjustments/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListAdjustments')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="transfers"
          :class="{ 'd-block': selectedParentMenu == 'transfers' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('transfer_add')"
          >
            <router-link tag="a" class to="/app/transfers/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('CreateTransfer')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('transfer_view')"
          >
            <router-link tag="a" class to="/app/transfers/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListTransfers')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="expenses"
          :class="{ 'd-block': selectedParentMenu == 'expenses' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('expense_add')"
          >
            <router-link tag="a" class to="/app/expenses/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('Create_Expense')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('expense_view')"
          >
            <router-link tag="a" class to="/app/expenses/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListExpenses')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('expense_view')"
          >
            <router-link tag="a" class to="/app/expenses/category">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('Expense_Category')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="quotations"
          :class="{ 'd-block': selectedParentMenu == 'quotations' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Quotations_add')"
          >
            <router-link tag="a" class to="/app/quotations/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('AddQuote')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Quotations_view')"
          >
            <router-link tag="a" class to="/app/quotations/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListQuotations')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="purchases"
          :class="{ 'd-block': selectedParentMenu == 'purchases' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Purchases_add')"
          >
            <router-link tag="a" class to="/app/purchases/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('AddPurchase')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Purchases_view')"
          >
            <router-link tag="a" class to="/app/purchases/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListPurchases')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="sales"
          :class="{ 'd-block': selectedParentMenu == 'sales' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Sales_add')"
          >
            <router-link tag="a" class to="/app/sales/store">
              <i class="nav-icon i-Add-File"></i>
              <span class="item-name">{{$t('AddSale')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Sales_view')"
          >
            <router-link tag="a" class to="/app/sales/list">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('ListSales')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Pos_view')"
          >
            <router-link tag="a" class to="/app/pos">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">POS</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('shipment')"
          >
            <router-link tag="a" class to="/app/sales/shipment">
              <i class="nav-icon i-Files"></i>
              <span class="item-name">{{$t('Shipments')}}</span>
            </router-link>
          </li>
        </ul>

      

       
      <!-- hrm -->
        <ul
          class="childNav d-none"
          data-parent="hrm"
          :class="{ 'd-block': selectedParentMenu == 'hrm' }"
        >
         <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('company')"
          >
            <router-link tag="a" class to="/app/hrm/company">
              <i class="nav-icon i-Management"></i>
              <span class="item-name">{{$t('Company')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('department')"
          >
            <router-link tag="a" class to="/app/hrm/departments">
              <i class="nav-icon i-Shop"></i>
              <span class="item-name">{{$t('Departments')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('designation')"
          >
            <router-link tag="a" class to="/app/hrm/designations">
              <i class="nav-icon i-Shutter"></i>
              <span class="item-name">{{$t('Designations')}}</span>
            </router-link>
          </li>
           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('office_shift')"
          >
            <router-link tag="a" class to="/app/hrm/office_Shift">
              <i class="nav-icon i-Clock"></i>
              <span class="item-name">{{$t('Office_Shift')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('view_employee')"
          >
            <router-link tag="a" class to="/app/hrm/employees">
              <i class="nav-icon i-Engineering"></i>
              <span class="item-name">{{$t('Employees')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('attendance')"
          >
            <router-link tag="a" class to="/app/hrm/attendance">
              <i class="nav-icon i-Clock"></i>
              <span class="item-name">{{$t('Attendance')}}</span>
            </router-link>
          </li>
           <li
            v-if="currentUserPermissions && (currentUserPermissions.includes('leave'))"

           @click.prevent="toggleSidebarDropdwon($event)"
            class="nav-item dropdown-sidemenu"
          >

            <a href="#">
              <i class="nav-icon i-Calendar"></i>
              <span class="item-name">{{$t('Leave_request')}}</span>
              <i class="dd-arrow i-Arrow-Down"></i>
            </a>
            <ul class="submenu">
              <li
              >
                <router-link tag="a" class to="/app/hrm/leaves/list">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('Leave_request')}}</span>
                </router-link>
              </li>
              <li
              >
                <router-link tag="a" class to="/app/hrm/leaves/type">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('Leave_type')}}</span>
                </router-link>
              </li>
              
            </ul>
          </li>
           
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('holiday')"
          >
            <router-link tag="a" class to="/app/hrm/holidays">
              <i class="nav-icon i-Christmas-Bell"></i>
              <span class="item-name">{{$t('Holidays')}}</span>
            </router-link>
          </li>
        </ul>

         <!-- People -->
        <ul
          class="childNav d-none"
          data-parent="People"
          :class="{ 'd-block': selectedParentMenu == 'People' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Customers_view')"
          >
            <router-link tag="a" class to="/app/People/Customers">
              <i class="nav-icon i-Administrator"></i>
              <span class="item-name">{{$t('Customers')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Suppliers_view')"
          >
            <router-link tag="a" class to="/app/People/Suppliers">
              <i class="nav-icon i-Administrator"></i>
              <span class="item-name">{{$t('Suppliers')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('users_view')"
          >
            <router-link tag="a" class to="/app/People/Users">
              <i class="nav-icon i-Administrator"></i>
              <span class="item-name">{{$t('Users')}}</span>
            </router-link>
          </li>
        </ul>

        <ul
          class="childNav d-none"
          data-parent="settings"
          :class="{ 'd-block': selectedParentMenu == 'settings' }"
        >
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('setting_system')"
          >
            <router-link tag="a" class to="/app/settings/System_settings">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="item-name">{{$t('SystemSettings')}}</span>
            </router-link>
          </li>

          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('sms_settings')"
          >
            <router-link tag="a" class to="/app/settings/sms_settings">
              <i class="nav-icon i-Speach-Bubble"></i>
              <span class="item-name">{{$t('sms_settings')}}</span>
            </router-link>
          </li>

           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('pos_settings')"
          >
            <router-link tag="a" class to="/app/settings/pos_settings">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="item-name">{{$t('pos_settings')}}</span>
            </router-link>
          </li>

            <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('setting_system')"
          >
            <router-link tag="a" class to="/app/settings/update_settings">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="item-name">{{$t('update_settings')}}</span>
            </router-link>
          </li>

           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('payment_gateway')"
          >
            <router-link tag="a" class to="/app/settings/payment_gateway">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="item-name">{{$t('payment_gateway')}}</span>
            </router-link>
          </li>

           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('mail_settings')"
          >
            <router-link tag="a" class to="/app/settings/mail_settings">
              <i class="nav-icon i-Data-Settings"></i>
              <span class="item-name">{{$t('mail_settings')}}</span>
            </router-link>
          </li>

          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('permissions_view')"
          >
            <router-link tag="a" class to="/app/settings/permissions">
              <i class="nav-icon i-Key"></i>
              <span class="item-name">{{$t('GroupPermissions')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('warehouse')"
          >
            <router-link tag="a" class to="/app/settings/Warehouses">
              <i class="nav-icon i-Clothing-Store"></i>
              <span class="item-name">{{$t('Warehouses')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('currency')"
          >
            <router-link tag="a" class to="/app/settings/Currencies">
              <i class="nav-icon i-Dollar-Sign"></i>
              <span class="item-name">{{$t('Currencies')}}</span>
            </router-link>
          </li>
         
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('backup')"
          >
            <router-link tag="a" class to="/app/settings/Backup">
              <i class="nav-icon i-Data-Backup"></i>
              <span class="item-name">{{$t('Backup')}}</span>
            </router-link>
          </li>

        </ul>

        <ul
          class="childNav d-none"
          data-parent="reports"
          :class="{ 'd-block': selectedParentMenu == 'reports' }"
        >
          <li
            v-if="currentUserPermissions &&
             (currentUserPermissions.includes('Reports_payments_Purchases') 
           || currentUserPermissions.includes('Reports_payments_Sales')
           || currentUserPermissions.includes('Reports_payments_Sale_Returns')
           || currentUserPermissions.includes('Reports_payments_purchase_Return'))"
            @click.prevent="toggleSidebarDropdwon($event)"
            class="nav-item dropdown-sidemenu"
          >
            <a href="#">
              <i class="nav-icon i-Credit-Card"></i>
              <span class="item-name">{{$t('Payments')}}</span>
              <i class="dd-arrow i-Arrow-Down"></i>
            </a>
            <ul class="submenu">
              <li
                v-if="currentUserPermissions && currentUserPermissions.includes('Reports_payments_Purchases')"
              >
                <router-link tag="a" class to="/app/reports/payments_purchase">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('Purchases')}}</span>
                </router-link>
              </li>
              <li
                v-if="currentUserPermissions && currentUserPermissions.includes('Reports_payments_Sales')"
              >
                <router-link tag="a" class to="/app/reports/payments_sale">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('Sales')}}</span>
                </router-link>
              </li>
              <li
                v-if="currentUserPermissions && currentUserPermissions.includes('Reports_payments_Sale_Returns')"
              >
                <router-link tag="a" class to="/app/reports/payments_sales_returns">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('SalesReturn')}}</span>
                </router-link>
              </li>
              <li
                v-if="currentUserPermissions && currentUserPermissions.includes('Reports_payments_purchase_Return')"
              >
                <router-link tag="a" class to="/app/reports/payments_purchases_returns">
                  <i class="nav-icon i-ID-Card"></i>
                  <span class="item-name">{{$t('PurchasesReturn')}}</span>
                </router-link>
              </li>
            </ul>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_profit')"
          >
            <router-link tag="a" class to="/app/reports/profit_and_loss">
              <i class="nav-icon i-Split-FourSquareWindow"></i>
              <span class="item-name">{{$t('ProfitandLoss')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_quantity_alerts')"
          >
            <router-link tag="a" class to="/app/reports/quantity_alerts">
              <i class="nav-icon i-Dollar"></i>
              <span class="item-name">{{$t('ProductQuantityAlerts')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Warehouse_report')"
          >
            <router-link tag="a" class to="/app/reports/warehouse_report">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('Warehouse_report')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('stock_report')"
          >
            <router-link tag="a" class to="/app/reports/stock_report">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('stock_report')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_sales')"
          >
            <router-link tag="a" class to="/app/reports/sales_report">
              <i class="nav-icon i-Line-Chart"></i>
              <span class="item-name">{{$t('SalesReport')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_purchase')"
          >
            <router-link tag="a" class to="/app/reports/purchase_report">
              <i class="nav-icon i-Bar-Chart5"></i>
              <span class="item-name">{{$t('PurchasesReport')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_customers')"
          >
            <router-link tag="a" class to="/app/reports/customers_report">
              <i class="nav-icon i-Bar-Chart"></i>
              <span class="item-name">{{$t('CustomersReport')}}</span>
            </router-link>
          </li>
          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Reports_suppliers')"
          >
            <router-link tag="a" class to="/app/reports/providers_report">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('SuppliersReport')}}</span>
            </router-link>
          </li>

           <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Top_products')"
          >
            <router-link tag="a" class to="/app/reports/top_selling_products">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('Top_Selling_Products')}}</span>
            </router-link>
          </li>

          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('Top_customers')"
          >
            <router-link tag="a" class to="/app/reports/top_customers">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('Top_customers')}}</span>
            </router-link>
          </li>

          <li
            class="nav-item"
            v-if="currentUserPermissions && currentUserPermissions.includes('users_report')"
          >
            <router-link tag="a" class to="/app/reports/users_report">
              <i class="nav-icon i-Pie-Chart"></i>
              <span class="item-name">{{$t('Users_Report')}}</span>
            </router-link>
          </li>

          


        </ul>
      </div>
    </vue-perfect-scrollbar>
    <div
      @click="removeOverlay()"
      class="sidebar-overlay"
      :class="{ open: getSideBarToggleProperties.isSecondarySideNavOpen }"
    ></div>
  </div>
  <!--=============== Left side End ================-->
</template>

<script>
import Topnav from "./TopNav";
import { isMobile } from "mobile-device-detect";

import { mapGetters, mapActions } from "vuex";

export default {
  components: {
    Topnav
  },

  data() {
    return {
      isDisplay: true,
      isMenuOver: false,
      isStyle: true,
      selectedParentMenu: "",
      isMobile
    };
  },
  mounted() {
    this.toggleSelectedParentMenu();
    window.addEventListener("resize", this.handleWindowResize);
    document.addEventListener("click", this.returnSelectedParentMenu);
    this.handleWindowResize();
  },

  beforeDestroy() {
    document.removeEventListener("click", this.returnSelectedParentMenu);
    window.removeEventListener("resize", this.handleWindowResize);
  },

  computed: {
    ...mapGetters(["getSideBarToggleProperties", "currentUserPermissions"])
  },

  methods: {
    ...mapActions([
      "changeSecondarySidebarProperties",
      "changeSecondarySidebarPropertiesViaMenuItem",
      "changeSecondarySidebarPropertiesViaOverlay",
      "changeSidebarProperties"
    ]),

    handleWindowResize() {
      if (window.innerWidth <= 1200) {
        if (this.getSideBarToggleProperties.isSideNavOpen) {
          this.changeSidebarProperties();
        }
        if (this.getSideBarToggleProperties.isSecondarySideNavOpen) {
          this.changeSecondarySidebarProperties();
        }
      } else {
        if (!this.getSideBarToggleProperties.isSideNavOpen) {
          this.changeSidebarProperties();
        }
      }
    },
    toggleSelectedParentMenu() {
      const currentParentUrl = this.$route.path
        .split("/")
        .filter(x => x !== "")[1];
      if (currentParentUrl !== undefined || currentParentUrl !== null) {
        this.selectedParentMenu = currentParentUrl.toLowerCase();
      } else {
        this.selectedParentMenu = "dashboard";
      }
    },
    toggleSubMenu(e) {
      let hasSubmenu = e.target.dataset.submenu;
      let parent = e.target.dataset.item;

      if (hasSubmenu) {
        this.selectedParentMenu = parent;

        this.changeSecondarySidebarPropertiesViaMenuItem(true);
      } else {
        this.selectedParentMenu = parent;
        this.changeSecondarySidebarPropertiesViaMenuItem(false);
      }
    },

    removeOverlay() {
      this.changeSecondarySidebarPropertiesViaOverlay();
      if (window.innerWidth <= 1200) {
        this.changeSidebarProperties();
      }
      this.toggleSelectedParentMenu();
    },
    returnSelectedParentMenu() {
      if (!this.isMenuOver) {
        this.toggleSelectedParentMenu();
      }
    },

    toggleSidebarDropdwon(event) {
      let dropdownMenus = this.$el.querySelectorAll(".dropdown-sidemenu.open");

      event.currentTarget.classList.toggle("open");

      dropdownMenus.forEach(dropdown => {
        dropdown.classList.remove("open");
      });
    }
  }
};
</script>

<style lang="" scoped>
</style>

