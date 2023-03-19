<template>
  <div class="main-content">
    <breadcumb :page="$t('CustomerManagement')" :folder="$t('Customers')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="clients"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        enabled: true,
        placeholder: $t('Search_this_table'),  
      }"
        :select-options="{ 
          enabled: true ,
          clearSelectionText: '',
        }"
        @on-selected-rows-change="selectionChanged"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
       :styleClass="showDropdown?'tableOne table-hover vgt-table full-height':'tableOne table-hover vgt-table non-height'"
      >
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="clients_PDF()" size="sm" variant="outline-success m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
           <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="clients"
              :columns="columns"
              :file-name="'clients'"
              :file-type="'xlsx'"
              :sheet-name="'clients'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
          <b-button
            @click="Show_import_clients()"
            size="sm"
            variant="info m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('customers_import')"
          >
            <i class="i-Download"></i>
            {{ $t("Import_Customers") }}
          </b-button>
          <b-button
            @click="New_Client()"
            size="sm"
            variant="btn btn-primary btn-icon m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('Customers_add')"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <div>
              <b-dropdown
                id="dropdown-right"
                variant="link"
                text="right align"
                toggle-class="text-decoration-none"
                size="lg"
                right
                no-caret
              >
                <template v-slot:button-content class="_r_btn border-0">
                  <span class="_dot _r_block-dot bg-dark"></span>
                  <span class="_dot _r_block-dot bg-dark"></span>
                  <span class="_dot _r_block-dot bg-dark"></span>
                </template>

                <b-dropdown-item
                  v-if="props.row.due > 0 && currentUserPermissions && currentUserPermissions.includes('pay_due')"
                  @click="Pay_due(props.row)"
                >
                  <i class="nav-icon i-Dollar font-weight-bold mr-2"></i>
                  {{$t('pay_all_sell_due_at_a_time')}}
                </b-dropdown-item>

                <b-dropdown-item
                  v-if="props.row.return_Due > 0 && currentUserPermissions && currentUserPermissions.includes('pay_sale_return_due')"
                  @click="Pay_return_due(props.row)"
                >
                  <i class="nav-icon i-Dollar font-weight-bold mr-2"></i>
                  {{$t('pay_all_sell_return_due_at_a_time')}}
                </b-dropdown-item>
               
                <b-dropdown-item
                  @click="showDetails(props.row)"
                >
                  <i class="nav-icon i-Eye font-weight-bold mr-2"></i>
                  {{$t('Customer_details')}}
                </b-dropdown-item>

                <b-dropdown-item
                 v-if="currentUserPermissions && currentUserPermissions.includes('Customers_edit')"
                  @click="Edit_Client(props.row)"
                >
                  <i class="nav-icon i-Edit font-weight-bold mr-2"></i>
                  {{$t('Edit_Customer')}}
                </b-dropdown-item>

                <b-dropdown-item
                  title="Delete"
                  v-if="currentUserPermissions.includes('Customers_delete')"
                  @click="Remove_Client(props.row.id)"
                >
                  <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i>
                  {{$t('Delete_Customer')}}
                </b-dropdown-item>
                </b-dropdown>
            </div>
          </span>
        </template>

      </vue-good-table>
    </div>

    <!-- Multiple filters -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- Code Customer   -->
          <b-col md="12">
            <b-form-group :label="$t('CustomerCode')">
              <b-form-input label="Code" :placeholder="$t('SearchByCode')" v-model="Filter_Code"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Name Customer   -->
          <b-col md="12">
            <b-form-group :label="$t('CustomerName')">
              <b-form-input label="Name" :placeholder="$t('SearchByName')" v-model="Filter_Name"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Phone Customer   -->
          <b-col md="12">
            <b-form-group :label="$t('Phone')">
              <b-form-input label="Phone" :placeholder="$t('SearchByPhone')" v-model="Filter_Phone"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Email Customer   -->
          <b-col md="12">
            <b-form-group :label="$t('Email')">
              <b-form-input label="Email" :placeholder="$t('SearchByEmail')" v-model="Filter_Email"></b-form-input>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button @click="Get_Clients(serverParams.page)" variant="primary m-1" size="sm" block>
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger m-1" size="sm" block>
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>

    <!-- Modal Pay_due-->
    <validation-observer ref="ref_pay_due">
      <b-modal
        hide-footer
        size="md"
        id="modal_Pay_due"
        title="Pay Due"
      >
        <b-form @submit.prevent="Submit_Payment_sell_due">
          <b-row>
          
            <!-- Paying Amount  -->
            <b-col lg="6" md="12" sm="12">
              <validation-provider
                name="Amount"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Paying_Amount') + ' ' + '*'">
                  <b-form-input
                   @keyup="Verified_paidAmount(payment.amount)"
                    label="Amount"
                    :placeholder="$t('Paying_Amount')"
                    v-model.number="payment.amount"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Amount-feedback"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Amount-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  <span class="badge badge-danger">{{$t('Due')}} : {{currentUser.currency}} {{payment.due}}</span>
                </b-form-group>
              </validation-provider>
            </b-col>


            <!-- Payment choice -->
            <b-col lg="6" md="12" sm="12">
              <validation-provider name="Payment choice" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Paymentchoice')+ ' ' + '*'">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="payment.Reglement"
                    :reduce="label => label.value"
                    :placeholder="$t('PleaseSelect')"
                    :options="
                          [
                          {label: 'Cash', value: 'Cash'},
                          {label: 'credit card', value: 'credit card'},
                          {label: 'cheque', value: 'cheque'},
                          {label: 'Western Union', value: 'Western Union'},
                          {label: 'bank transfer', value: 'bank transfer'},
                          {label: 'other', value: 'other'},
                          ]"
                  ></v-select>
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Note -->
            <b-col lg="12" md="12" sm="12" class="mt-3">
              <b-form-group :label="$t('Please_provide_any_details')">
                <b-form-textarea id="textarea" v-model="payment.notes" rows="3" max-rows="6"></b-form-textarea>
              </b-form-group>
            </b-col>

            <b-col md="12" class="mt-3">
              <b-button
                variant="primary"
                type="submit"
                :disabled="paymentProcessing"
              >{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="paymentProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
            </b-col>

          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>

    <!-- Modal Pay_return_Due-->
    <validation-observer ref="ref_pay_return_due">
      <b-modal
        hide-footer
        size="md"
        id="modal_Pay_return_due"
        title="Pay Sell Return Due"
      >
        <b-form @submit.prevent="Submit_Payment_sell_return_due">
          <b-row>
          
            <!-- Paying Amount -->
            <b-col lg="6" md="12" sm="12">
              <validation-provider
                name="Amount"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Paying_Amount') + ' ' + '*'">
                  <b-form-input
                   @keyup="Verified_return_paidAmount(payment_return.amount)"
                    label="Amount"
                    :placeholder="$t('Paying_Amount')"
                    v-model.number="payment_return.amount"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Amount-feedback"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Amount-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  <span class="badge badge-danger">{{$t('Due')}} : {{currentUser.currency}} {{payment_return.return_Due}}</span>
                </b-form-group>
              </validation-provider>
            </b-col>


            <!-- Payment choice -->
            <b-col lg="6" md="12" sm="12">
              <validation-provider name="Payment choice" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Paymentchoice')+ ' ' + '*'">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="payment_return.Reglement"
                    :reduce="label => label.value"
                    :placeholder="$t('PleaseSelect')"
                    :options="
                          [
                          {label: 'Cash', value: 'Cash'},
                          {label: 'credit card', value: 'credit card'},
                          {label: 'cheque', value: 'cheque'},
                          {label: 'Western Union', value: 'Western Union'},
                          {label: 'bank transfer', value: 'bank transfer'},
                          {label: 'other', value: 'other'},
                          ]"
                  ></v-select>
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Note -->
            <b-col lg="12" md="12" sm="12" class="mt-3">
              <b-form-group :label="$t('Please_provide_any_details')">
                <b-form-textarea id="textarea" v-model="payment_return.notes" rows="3" max-rows="6"></b-form-textarea>
              </b-form-group>
            </b-col>

            <b-col md="12" class="mt-3">
              <b-button
                variant="primary"
                type="submit"
                :disabled="payment_return_Processing"
              >{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="payment_return_Processing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
            </b-col>

          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>

    <!-- Modal Show Customer_Invoice-->
    <b-modal hide-footer size="sm" scrollable id="Show_invoice" :title="$t('Customer_Credit_Note')">
        <div id="invoice-POS">
          <div style="max-width:400px;margin:0px auto">
          <div class="info" >
            <h2 class="text-center">{{company_info.CompanyName}}</h2>

            <p>
                <span>{{$t('date')}} : {{payment.date}} <br></span>
                <span >{{$t('Adress')}} : {{company_info.CompanyAdress}} <br></span>
                <span >{{$t('Phone')}} : {{company_info.CompanyPhone}} <br></span>
                <span >{{$t('Customer')}} : {{payment.client_name}} <br></span>
              </p>
          </div>

           <table
                class="change mt-3"
                style=" font-size: 10px;"
              >
                <thead>
                  <tr style="background: #eee; ">
                    <th style="text-align: left;" colspan="1">{{$t('PayeBy')}}:</th>
                    <th style="text-align: center;" colspan="2">{{$t('Amount')}}:</th>
                    <th style="text-align: right;" colspan="1">{{$t('Due')}}:</th>
                  </tr>
                </thead>

                <tbody>
                  <tr>
                    <td style="text-align: left;" colspan="1">{{payment.Reglement}}</td>
                    <td
                      style="text-align: center;"
                      colspan="2"
                    >{{formatNumber(payment.amount ,2)}}</td>
                    <td
                      style="text-align: right;"
                      colspan="1"
                    >{{formatNumber(payment.due - payment.amount ,2)}}</td>
                  </tr>
                </tbody>
              </table>
          </div>
        </div>
      <button @click="print_it()" class="btn btn-outline-primary">
        <i class="i-Billing"></i>
        {{$t('print')}}
      </button>
    </b-modal>

    <!-- Modal Show_invoice_return-->
    <b-modal hide-footer size="sm" scrollable id="Show_invoice_return" :title="$t('Sell_return_due')">
        <div id="invoice-POS-return">
          <div style="max-width:400px;margin:0px auto">
          <div class="info" >
            <h2 class="text-center">{{company_info.CompanyName}}</h2>

            <p>
                <span>{{$t('date')}} : {{payment_return.date}} <br></span>
                <span >{{$t('Adress')}} : {{company_info.CompanyAdress}} <br></span>
                <span >{{$t('Phone')}} : {{company_info.CompanyPhone}} <br></span>
                <span >{{$t('Customer')}} : {{payment_return.client_name}} <br></span>
              </p>
          </div>

           <table
                class="change mt-3"
                style=" font-size: 10px;"
              >
                <thead>
                  <tr style="background: #eee; ">
                    <th style="text-align: left;" colspan="1">{{$t('PayeBy')}}:</th>
                    <th style="text-align: center;" colspan="2">{{$t('Amount')}}:</th>
                    <th style="text-align: right;" colspan="1">{{$t('Due')}}:</th>
                  </tr>
                </thead>

                <tbody>
                  <tr>
                    <td style="text-align: left;" colspan="1">{{payment_return.Reglement}}</td>
                    <td
                      style="text-align: center;"
                      colspan="2"
                    >{{formatNumber(payment_return.amount ,2)}}</td>
                    <td
                      style="text-align: right;"
                      colspan="1"
                    >{{formatNumber(payment_return.return_Due - payment_return.amount ,2)}}</td>
                  </tr>
                </tbody>
              </table>
          </div>
        </div>
      <button @click="print_return_due()" class="btn btn-outline-primary">
        <i class="i-Billing"></i>
        {{$t('print')}}
      </button>
    </b-modal>

    <!-- Modal Create & Edit Customer -->
    <validation-observer ref="Create_Customer">
      <b-modal hide-footer size="lg" id="New_Customer" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Customer">
          <b-row>
            <!-- Customer Name -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Name Customer"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('CustomerName') + ' ' + '*'">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="name-feedback"
                    label="name"
                    :placeholder="$t('CustomerName')"
                    v-model="client.name"
                  ></b-form-input>
                  <b-form-invalid-feedback id="name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Customer Email -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Email')">
                  <b-form-input
                    label="Email"
                    v-model="client.email"
                    :placeholder="$t('Email')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Customer Phone -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Phone')">
                  <b-form-input
                    label="Phone"
                    v-model="client.phone"
                    :placeholder="$t('Phone')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Customer Country -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Country')">
                  <b-form-input
                    label="Country"
                    v-model="client.country"
                    :placeholder="$t('Country')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Customer City -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('City')">
                  <b-form-input
                    label="City"
                    v-model="client.city"
                    :placeholder="$t('City')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

             <!-- Customer Tax Number -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Tax_Number')">
                  <b-form-input
                    label="Tax Number"
                    v-model="client.tax_number"
                    :placeholder="$t('Tax_Number')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Customer Adress -->
            <b-col md="12" sm="12">
                <b-form-group :label="$t('Adress')">
                  <textarea
                    label="Adress"
                    class="form-control"
                    rows="4"
                    v-model="client.adresse"
                    :placeholder="$t('Adress')"
                 ></textarea>
                </b-form-group>
            </b-col>

            <b-col md="12" class="mt-3">
                <b-button variant="primary" type="submit"  :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
                  <div v-once class="typo__p" v-if="SubmitProcessing">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
            </b-col>

          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>

    <!-- Modal Show Customer Details -->
    <b-modal ok-only size="md" id="showDetails" :title="$t('CustomerDetails')">
      <b-row>
        <b-col lg="12" md="12" sm="12" class="mt-3">
          <table class="table table-striped table-md">
            <tbody>
              <tr>
                <!-- Customer Code -->
                <td>{{$t('CustomerCode')}}</td>
                <th>{{client.code}}</th>
              </tr>
              <tr>
                <!-- Customer Name -->
                <td>{{$t('CustomerName')}}</td>
                <th>{{client.name}}</th>
              </tr>
              <tr>
                <!-- Customer Phone -->
                <td>{{$t('Phone')}}</td>
                <th>{{client.phone}}</th>
              </tr>
              <tr>
                <!-- Customer Email -->
                <td>{{$t('Email')}}</td>
                <th>{{client.email}}</th>
              </tr>
              <tr>
                <!-- Customer country -->
                <td>{{$t('Country')}}</td>
                <th>{{client.country}}</th>
              </tr>
              <tr>
                <!-- Customer City -->
                <td>{{$t('City')}}</td>
                <th>{{client.city}}</th>
              </tr>
              <tr>
                <!-- Customer Adress -->
                <td>{{$t('Adress')}}</td>
                <th>{{client.adresse}}</th>
              </tr>
              <tr>
                <!-- Tax Number -->
                <td>{{$t('Tax_Number')}}</td>
                <th>{{client.tax_number}}</th>
              </tr>

              <tr>
                <!-- Total_Sale_Due -->
                <td>{{$t('Total_Sale_Due')}}</td>
                <th>{{currentUser.currency}} {{client.due}}</th>
              </tr>

               <tr>
                <!-- Total_Sell_Return_Due -->
                <td>{{$t('Total_Sell_Return_Due')}}</td>
                <th>{{currentUser.currency}} {{client.return_Due}}</th>
              </tr>
            </tbody>
          </table>
        </b-col>
      </b-row>
    </b-modal>

    <!-- Modal Show Import Clients -->
    <b-modal ok-only ok-title="Cancel" size="md" id="importClients" :title="$t('Import_Customers')">
      <b-form @submit.prevent="Submit_import" enctype="multipart/form-data">
        <b-row>
          <!-- File -->
          <b-col md="12" sm="12" class="mb-3">
            <b-form-group>
              <input type="file" @change="onFileSelected" label="Choose File">
              <b-form-invalid-feedback
                id="File-feedback"
                class="d-block"
              >{{$t('field_must_be_in_csv_format')}}</b-form-invalid-feedback>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button type="submit" variant="primary" :disabled="ImportProcessing" size="sm" block>{{ $t("submit") }}</b-button>
              <div v-once class="typo__p" v-if="ImportProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
          </b-col>

          <b-col md="6" sm="12">
            <b-button
              :href="'/import/exemples/import_clients.csv'"
              variant="info"
              size="sm"
              block
            >{{ $t("Download_exemple") }}</b-button>
          </b-col>

          <b-col md="12" sm="12">
            <table class="table table-bordered table-sm mt-4">
              <tbody>
                <tr>
                  <td>{{$t('Name')}}</td>
                  <th>
                    <span class="badge badge-outline-success">{{$t('Field_is_required')}}</span>
                  </th>
                </tr>

                <tr>
                  <td>{{$t('Phone')}}</td>
                 
                </tr>

                <tr>
                  <td>{{$t('Email')}}</td>
                  <th>
                    <span class="badge badge-outline-success">{{$t('Field_is_required')}} | unique</span>
                  </th>
                </tr>

                <tr>
                  <td>{{$t('Country')}}</td>
                </tr>

                <tr>
                  <td>{{$t('City')}}</td>
                </tr>

                <tr>
                  <td>{{$t('Adress')}}</td>
                </tr>
                 <tr>
                  <td>{{$t('Tax_Number')}}</td>
                </tr>
              </tbody>
            </table>
          </b-col>
        </b-row>
      </b-form>
    </b-modal>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Customer"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing:false,
      ImportProcessing:false,
      paymentProcessing:false,
      payment_return_Processing:false,
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      showDropdown: false,
      payment: {
        client_id: "",
        client_name: "",
        date:"",
        due: "",
        amount: "",
        notes: "",
        Reglement: "",
      },
       payment_return: {
        client_id: "",
        client_name: "",
        date:"",
        return_Due: "",
        amount: "",
        notes: "",
        Reglement: "",
      },
      company_info:{},
      email_exist:"",
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      Filter_Name: "",
      Filter_Code: "",
      Filter_Phone: "",
      Filter_Email: "",
      clients: [],
      editmode: false,
      import_clients: "",
      data: new FormData(),
      client: {
        id: "",
        name: "",
        code: "",
        email: "",
        phone: "",
        country: "",
        city: "",
        adresse: "",
        tax_number: "",

      }
    };
  },

   mounted() {
    this.$root.$on("bv::dropdown::show", bvEvent => {
      this.showDropdown = true;
    });
    this.$root.$on("bv::dropdown::hide", bvEvent => {
      this.showDropdown = false;
    });
  },

  computed: {
    ...mapGetters(["currentUserPermissions", "currentUser"]),
    columns() {
      return [
        {
          label: this.$t("Code"),
          field: "code",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Name"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left"
        },

        {
          label: this.$t("Phone"),
          field: "phone",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Email"),
          field: "email",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Tax_Number"),
          field: "tax_number",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Total_Sale_Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total_Sell_Return_Due"),
          field: "return_Due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },

        {
          label: this.$t("Action"),
          field: "actions",
          html: true,
          tdClass: "text-right",
          thClass: "text-right",
          sortable: false
        }
      ];
    }
  },

  methods: {

    //------------- Submit Validation Create & Edit Customer
    Submit_Customer() {
      this.$refs.Create_Customer.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Client();
          } else {
            this.Update_Client();
          }
        }
      });
    },

    //------ update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Clients(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Clients(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //------ Event Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Clients(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Clients(this.serverParams.page);
    },

    //------ Event Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_Name = "";
      this.Filter_Code = "";
      this.Filter_Phone = "";
      this.Filter_Email = "";
      this.Get_Clients(this.serverParams.page);
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //--------------------------------- Customers PDF -------------------------------\\
    clients_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Code", dataKey: "code" },
        { title: "Name", dataKey: "name" },
        { title: "Sale Due", dataKey: "due" },
        { title: "Sell Return Due", dataKey: "return_Due" },
        { title: "Tax Number", dataKey: "tax_number" },
        { title: "Phone", dataKey: "phone" },
        { title: "Email", dataKey: "email" },
        { title: "Country", dataKey: "country" },
        { title: "City", dataKey: "city" },
      ];
      pdf.autoTable(columns, self.clients);
      pdf.text("Customer List", 40, 25);
      pdf.save("Customer_List.pdf");
    },


    //--------------------------------------- Get All Clients -------------------------------\\
    Get_Clients(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "clients?page=" +
            page +
            "&name=" +
            this.Filter_Name +
            "&code=" +
            this.Filter_Code +
            "&phone=" +
            this.Filter_Phone +
            "&email=" +
            this.Filter_Email +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.clients = response.data.clients;
          this.company_info = response.data.company_info;
          this.totalRows = response.data.totalRows;

          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //----------------------------------- Show import Client -------------------------------\\
    Show_import_clients() {
      this.$bvModal.show("importClients");
    },

    //------------------------------ Event Import clients -------------------------------\\
    onFileSelected(e) {
      this.import_clients = "";
      let file = e.target.files[0];
      let errorFilesize;

      if (file["size"] < 1048576) {
        // 1 mega = 1,048,576 Bytes
        errorFilesize = false;
      } else {
        this.makeToast(
          "danger",
          this.$t("file_size_must_be_less_than_1_mega"),
          this.$t("Failed")
        );
      }

      if (errorFilesize === false) {
        this.import_clients = file;
      }
    },

    //----------------------------------------Submit  import clients-----------------\\
    Submit_import() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.ImportProcessing = true;
      self.data.append("clients", self.import_clients);
      axios
        .post("clients/import/csv", self.data)
        .then(response => {
          self.ImportProcessing = false;
          if (response.data.status === true) {
            this.makeToast(
              "success",
              this.$t("Successfully_Imported"),
              this.$t("Success")
            );
            Fire.$emit("Event_import");
          } else if (response.data.status === false) {
            this.makeToast(
              "danger",
              this.$t("field_must_be_in_csv_format"),
              this.$t("Failed")
            );
          }
          // Complete the animation of theprogress bar.
          NProgress.done();
        })
        .catch(error => {
          self.ImportProcessing = false;
          NProgress.done();
            this.makeToast(
              "danger",
              this.$t("Please_follow_the_import_instructions"),
              this.$t("Failed")
            );
        });
    },

    //----------------------------------- Show Details Client -------------------------------\\
    showDetails(client) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.client = client;
      Fire.$emit("Get_Details_customers");
    },

    //------------------------------ Show Modal (create Client) -------------------------------\\
    New_Client() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Customer");
    },

    //------------------------------ Show Modal (Edit Client) -------------------------------\\
    Edit_Client(client) {
      this.Get_Clients(this.serverParams.page);
      this.reset_Form();
      this.client = client;
      this.editmode = true;
      this.$bvModal.show("New_Customer");
    },

    //---------------------------------------- Create new Client -------------------------------\\
    Create_Client() {
      this.SubmitProcessing = true;
      axios
        .post("clients", {
          name: this.client.name,
          email: this.client.email,
          phone: this.client.phone,
          tax_number: this.client.tax_number,
          country: this.client.country,
          city: this.client.city,
          adresse: this.client.adresse
        })
        .then(response => {
          Fire.$emit("Event_Customer");

          this.makeToast(
            "success",
            this.$t("Create.TitleCustomer"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          if (error.errors.email.length > 0) {
            this.email_exist = error.errors.email[0];
          }
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //----------------------------------- Update Client -------------------------------\\
    Update_Client() {
      this.SubmitProcessing = true;
      axios
        .put("clients/" + this.client.id, {
          name: this.client.name,
          email: this.client.email,
          phone: this.client.phone,
          tax_number: this.client.tax_number,
          country: this.client.country,
          city: this.client.city,
          adresse: this.client.adresse
        })
        .then(response => {
          Fire.$emit("Event_Customer");
          this.makeToast(
            "success",
            this.$t("Update.TitleCustomer"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          if (error.errors.email.length > 0) {
            this.email_exist = error.errors.email[0];
          }
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //-------------------------------- Reset Form -------------------------------\\
    reset_Form() {
      this.email_exist= "";
      this.client = {
        id: "",
        name: "",
        email: "",
        phone: "",
        country: "",
        tax_number: "",
        city: "",
        adresse: ""
      };
    },

    //------------------------------- Remove Client -------------------------------\\
    Remove_Client(id) {
      this.$swal({
        title: this.$t("Delete.Title"),
        text: this.$t("Delete.Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete.cancelButtonText"),
        confirmButtonText: this.$t("Delete.confirmButtonText")
      }).then(result => {
        if (result.value) {
          axios
            .delete("clients/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.CustomerDeleted"),
                "success"
              );
              Fire.$emit("Delete_Customer");
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.ClientError"),
                "warning"
              );
            });
        }
      });
    },

    //---- Delete Clients by selection

    delete_by_selected() {
      this.$swal({
        title: this.$t("Delete.Title"),
        text: this.$t("Delete.Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete.cancelButtonText"),
        confirmButtonText: this.$t("Delete.confirmButtonText")
      }).then(result => {
        if (result.value) {
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .post("clients/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.CustomerDeleted"),
                "success"
              );

              Fire.$emit("Delete_Customer");
            })
            .catch(() => {
              // Complete the animation of theprogress bar.
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    },


    //------ Validate Form Submit_Payment_sell_due
    Submit_Payment_sell_due() {
      this.$refs.ref_pay_due.validate().then(success => {
        if (!success) {
           this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else if (this.payment.amount > this.payment.due) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Total_Due"),
            this.$t("Warning")
          );
          this.payment.amount = 0;
        }
       else {
            this.Submit_Pay_due();
        }

      });
    },

      //---------- keyup paid Amount

    Verified_paidAmount() {
      if (isNaN(this.payment.amount)) {
        this.payment.amount = 0;
      } else if (this.payment.amount > this.payment.due) {
        this.makeToast(
          "warning",
          this.$t("Paying_amount_is_greater_than_Total_Due"),
          this.$t("Warning")
        );
        this.payment.amount = 0;
      } 
    },

      //-------------------------------- reset_Form_payment-------------------------------\\
    reset_Form_payment() {
      this.payment = {
        client_id: "",
        client_name: "",
        date: "",
        due: "",
        amount: "",
        notes: "",
        Reglement: "",
      };
    },

    //------------------------------ Show Modal Pay_due-------------------------------\\
    Pay_due(row) {
      this.reset_Form_payment();
      this.payment.client_id = row.id;
      this.payment.client_name = row.name;
      this.payment.due = row.due;
      this.payment.date = new Date().toISOString().slice(0, 10);
      setTimeout(() => {
        this.$bvModal.show("modal_Pay_due");
      }, 500);
      
    },

     //------------------------------ Print Customer_Invoice -------------------------\\
    print_it() {
      var divContents = document.getElementById("invoice-POS").innerHTML;
      var a = window.open("", "", "height=500, width=500");
      a.document.write(
        '<link rel="stylesheet" href="/css/pos_print.css"><html>'
      );
      a.document.write("<body >");
      a.document.write(divContents);
      a.document.write("</body></html>");
      a.document.close();
      setTimeout(() => {
         a.print();
      }, 1000);
    },

     //---------------------------------------- Submit_Pay_due-------------------------------\\
    Submit_Pay_due() {
      this.paymentProcessing = true;
      axios
        .post("clients_pay_due", {
          client_id: this.payment.client_id,
          amount: this.payment.amount,
          notes: this.payment.notes,
          Reglement: this.payment.Reglement,
        })
        .then(response => {
          Fire.$emit("Event_pay_due");

          this.makeToast(
            "success",
            this.$t("Create.TitlePayment"),
            this.$t("Success")
          );
          this.paymentProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.paymentProcessing = false;
        });
    },

    //-------------------------------Pay sell return due -----------------------------------\\

     //------ Validate Form Submit_Payment_sell_return_due
    Submit_Payment_sell_return_due() {
      this.$refs.ref_pay_return_due.validate().then(success => {
        if (!success) {
           this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else if (this.payment_return.amount > this.payment_return.return_Due) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Total_Due"),
            this.$t("Warning")
          );
          this.payment_return.amount = 0;
        }
       else {
            this.Submit_Pay_return_due();
        }

      });
    },

      //---------- keyup paid Amount

    Verified_return_paidAmount() {
      if (isNaN(this.payment_return.amount)) {
        this.payment_return.amount = 0;
      } else if (this.payment_return.amount > this.payment_return.return_Due) {
        this.makeToast(
          "warning",
          this.$t("Paying_amount_is_greater_than_Total_Due"),
          this.$t("Warning")
        );
        this.payment_return.amount = 0;
      } 
    },

      //-------------------------------- reset_Form_payment-------------------------------\\
    reset_Form_payment_return_due() {
      this.payment_return = {
        client_id: "",
        client_name: "",
        date: "",
        return_Due: "",
        amount: "",
        notes: "",
        Reglement: "",
      };
    },

    //------------------------------ Show Modal Pay_return_due-------------------------------\\
    Pay_return_due(row) {
      this.reset_Form_payment_return_due();
      this.payment_return.client_id = row.id;
      this.payment_return.client_name = row.name;
      this.payment_return.return_Due = row.return_Due;
      this.payment_return.date = new Date().toISOString().slice(0, 10);
      setTimeout(() => {
        this.$bvModal.show("modal_Pay_return_due");
      }, 500);
      
    },

     //------------------------------ Print Customer_Invoice -------------------------\\
    print_return_due() {
      var divContents = document.getElementById("invoice-POS-return").innerHTML;
      var a = window.open("", "", "height=500, width=500");
      a.document.write(
        '<link rel="stylesheet" href="/css/pos_print.css"><html>'
      );
      a.document.write("<body >");
      a.document.write(divContents);
      a.document.write("</body></html>");
      a.document.close();
      setTimeout(() => {
         a.print();
      }, 1000);
    },

     //---------------------------------------- Submit_Pay_due-------------------------------\\
    Submit_Pay_return_due() {
      this.payment_return_Processing = true;
      axios
        .post("clients_pay_return_due", {
          client_id: this.payment_return.client_id,
          amount: this.payment_return.amount,
          notes: this.payment_return.notes,
          Reglement: this.payment_return.Reglement,
        })
        .then(response => {
          Fire.$emit("Event_pay_return_due");

          this.makeToast(
            "success",
            this.$t("Create.TitlePayment"),
            this.$t("Success")
          );
          this.payment_return_Processing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.payment_return_Processing = false;
        });
    },

     //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },



  }, // END METHODS

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Clients(1);

    Fire.$on("Get_Details_customers", () => {
      // Complete the animation of theprogress bar.
      setTimeout(() => NProgress.done(), 500);
      this.$bvModal.show("showDetails");
    });

    Fire.$on("Event_pay_due", () => {
      setTimeout(() => {
        this.Get_Clients(this.serverParams.page);
        this.$bvModal.hide("modal_Pay_due");
      }, 500);
       this.$bvModal.show("Show_invoice");
      //  setTimeout(() => this.print_it(), 1000);
    });

    Fire.$on("Event_pay_return_due", () => {
      setTimeout(() => {
        this.Get_Clients(this.serverParams.page);
        this.$bvModal.hide("modal_Pay_return_due");
      }, 500);
       this.$bvModal.show("Show_invoice_return");
      //  setTimeout(() => this.print_return_due(), 1000);
    });


    Fire.$on("Event_Customer", () => {
      setTimeout(() => {
        this.Get_Clients(this.serverParams.page);
        this.$bvModal.hide("New_Customer");
      }, 500);
    });

    Fire.$on("Delete_Customer", () => {
      setTimeout(() => {
        this.Get_Clients(this.serverParams.page);
      }, 500);
    });

    Fire.$on("Event_import", () => {
      setTimeout(() => {
        this.Get_Clients(this.serverParams.page);
        this.$bvModal.hide("importClients");
      }, 500);
    });
  }
};
</script>
