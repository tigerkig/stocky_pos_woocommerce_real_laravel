<template>
  <div class="main-content">
    <breadcumb :page="$t('SuppliersManagement')" :folder="$t('Suppliers')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="providers"
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
          <b-button @click="Providers_PDF()" size="sm" variant="outline-success m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="providers"
              :columns="columns"
              :file-name="'providers'"
              :file-type="'xlsx'"
              :sheet-name="'providers'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
          <b-button
            @click="Show_import_providers()"
            size="sm"
            variant="info m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('Suppliers_import')"
          >
            <i class="i-Download"></i>
            {{ $t("Import_Suppliers") }}
          </b-button>
          <b-button
            @click="New_Provider()"
            size="sm"
            variant="btn btn-primary btn-icon m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('Suppliers_add')"
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
                  v-if="props.row.due > 0 && currentUserPermissions && currentUserPermissions.includes('pay_supplier_due')"
                  @click="Pay_due(props.row)"
                >
                  <i class="nav-icon i-Dollar font-weight-bold mr-2"></i>
                  {{$t('pay_all_purchase_due_at_a_time')}}
                </b-dropdown-item>

                 <b-dropdown-item
                  v-if="props.row.return_Due > 0 && currentUserPermissions && currentUserPermissions.includes('pay_purchase_return_due')"
                  @click="Pay_return_due(props.row)"
                >
                  <i class="nav-icon i-Dollar font-weight-bold mr-2"></i>
                  {{$t('pay_all_purchase_return_due_at_a_time')}}
                </b-dropdown-item>

                <b-dropdown-item
                  @click="showDetails(props.row)"
                >
                  <i class="nav-icon i-Eye font-weight-bold mr-2"></i>
                  {{$t('Provider_details')}}
                </b-dropdown-item>

                <b-dropdown-item
                 v-if="currentUserPermissions && currentUserPermissions.includes('Suppliers_edit')"
                  @click="Edit_Provider(props.row)"
                >
                  <i class="nav-icon i-Edit font-weight-bold mr-2"></i>
                  {{$t('Edit_Provider')}}
                </b-dropdown-item>

                <b-dropdown-item
                  title="Delete"
                  v-if="currentUserPermissions.includes('Suppliers_delete')"
                  @click="Remove_Provider(props.row.id)"
                >
                  <i class="nav-icon i-Close-Window font-weight-bold mr-2"></i>
                  {{$t('Delete_Provider')}}
                </b-dropdown-item>
                </b-dropdown>
            </div>
          </span>

        </template>
      </vue-good-table>
    </div>

    <!-- Multiple Filters  -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- Code Provider   -->
          <b-col md="12">
            <b-form-group :label="$t('SupplierCode')">
              <b-form-input label="Code" :placeholder="$t('SearchByCode')" v-model="Filter_Code"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Name Provider   -->
          <b-col md="12">
            <b-form-group :label="$t('SupplierName')">
              <b-form-input label="Name" :placeholder="$t('SearchByName')" v-model="Filter_Name"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Phone Provider   -->
          <b-col md="12">
            <b-form-group :label="$t('Phone')">
              <b-form-input label="Phone" :placeholder="$t('SearchByPhone')" v-model="Filter_Phone"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Email Provider   -->
          <b-col md="12">
            <b-form-group :label="$t('Email')">
              <b-form-input label="Email" :placeholder="$t('SearchByEmail')" v-model="Filter_Email"></b-form-input>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button
              @click="Get_Providers(serverParams.page)"
              variant="primary m-1"
              size="sm"
              block
            >
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

    <!-- Add & Edit Provider -->
    <validation-observer ref="Create_Provider">
      <b-modal hide-footer size="lg" id="New_Provider" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Provider">
          <b-row>
            <!-- Provider Name -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Name Provider"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('SupplierName') + ' ' + '*'">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="name-feedback"
                    label="name"
                    v-model="provider.name"
                    :placeholder="$t('SupplierName')"
                  ></b-form-input>
                  <b-form-invalid-feedback id="name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Provider Email -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Email Provider"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Email') + ' ' + '*'">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="Email-feedback"
                    label="Email"
                    v-model="provider.email"
                    :placeholder="$t('Email')"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Email-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Provider Phone -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Phone')">
                  <b-form-input
                    label="Phone"
                    v-model="provider.phone"
                    :placeholder="$t('Phone')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Provider Country -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Country')">
                  <b-form-input
                    label="Country"
                    v-model="provider.country"
                    :placeholder="$t('Country')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Provider City -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('City')">
                  <b-form-input
                    label="City"
                    v-model="provider.city"
                    :placeholder="$t('City')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Provider Tax_Number -->
            <b-col md="6" sm="12">
                <b-form-group :label="$t('Tax_Number')">
                  <b-form-input
                    label="Tax_Number"
                    v-model="provider.tax_number"
                    :placeholder="$t('Tax_Number')"
                  ></b-form-input>
                </b-form-group>
            </b-col>

            <!-- Provider Adress -->
            <b-col md="12" sm="12">
                <b-form-group :label="$t('Adress')">
                  <textarea
                    label="Adress"
                    class="form-control"
                    rows="4"
                    v-model="provider.adresse"
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

    <!-- Modal Pay_due-->
    <validation-observer ref="ref_pay_due">
      <b-modal
        hide-footer
        size="md"
        id="modal_Pay_due"
        title="Pay Due"
      >
        <b-form @submit.prevent="Submit_Payment_Purchase_due">
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
        title="Pay Purchase Return Due"
      >
        <b-form @submit.prevent="Submit_Payment_purchase_return_due">
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
    <b-modal hide-footer size="sm" scrollable id="Show_invoice" :title="$t('Provider_Credit_Note')">
        <div id="invoice-POS">
          <div style="max-width:400px;margin:0px auto">
          <div class="info" >
            <h2 class="text-center">{{company_info.CompanyName}}</h2>

            <p>
                <span>{{$t('date')}} : {{payment.date}} <br></span>
                <span >{{$t('Adress')}} : {{company_info.CompanyAdress}} <br></span>
                <span >{{$t('Phone')}} : {{company_info.CompanyPhone}} <br></span>
                <span >{{$t('Customer')}} : {{payment.provider_name}} <br></span>
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
    <b-modal hide-footer size="sm" scrollable id="Show_invoice_return" :title="$t('Purchase_return_due')">
         <div id="invoice-POS-return">
          <div style="max-width:400px;margin:0px auto">
          <div class="info" >
            <h2 class="text-center">{{company_info.CompanyName}}</h2>

            <p>
                <span>{{$t('date')}} : {{payment_return.date}} <br></span>
                <span >{{$t('Adress')}} : {{company_info.CompanyAdress}} <br></span>
                <span >{{$t('Phone')}} : {{company_info.CompanyPhone}} <br></span>
                <span >{{$t('Customer')}} : {{payment_return.provider_name}} <br></span>
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

    <!-- Show details Provider -->
    <b-modal ok-only size="md" id="showDetails" :title="$t('SupplierDetails')">
      <b-row>
        <b-col lg="12" md="12" sm="12" class="mt-3">
          <table class="table table-striped table-md">
            <tbody>
              <tr>
                <!-- Provider Code -->
                <td>{{$t('SupplierCode')}}</td>
                <th>{{provider.code}}</th>
              </tr>
              <tr>
                <!-- Provider Name -->
                <td>{{$t('SupplierName')}}</td>
                <th>{{provider.name}}</th>
              </tr>
              <tr>
                <!-- Provider Phone -->
                <td>{{$t('Phone')}}</td>
                <th>{{provider.phone}}</th>
              </tr>
              <tr>
                <!-- Provider Email -->
                <td>{{$t('Email')}}</td>
                <th>{{provider.email}}</th>
              </tr>
              <tr>
                <!-- Provider country -->
                <td>{{$t('Country')}}</td>
                <th>{{provider.country}}</th>
              </tr>
              <tr>
                <!-- Provider City -->
                <td>{{$t('City')}}</td>
                <th>{{provider.city}}</th>
              </tr>
              <tr>
                <!-- Provider Adress -->
                <td>{{$t('Adress')}}</td>
                <th>{{provider.adresse}}</th>
              </tr>
              <tr>
                <!-- Provider Tax_Number -->
                <td>{{$t('Tax_Number')}}</td>
                <th>{{provider.tax_number}}</th>
              </tr>
               <tr>
                <!-- Total_Purchase_Due -->
                <td>{{$t('Total_Purchase_Due')}}</td>
                <th>{{currentUser.currency}} {{provider.due}}</th>
              </tr>

               <tr>
                <!-- Total_Purchase_Return_Due -->
                <td>{{$t('Total_Purchase_Return_Due')}}</td>
                <th>{{currentUser.currency}} {{provider.return_Due}}</th>
              </tr>
            </tbody>
          </table>
        </b-col>
      </b-row>
    </b-modal>

    <!-- Modal Show Import Providers -->
    <b-modal
      ok-only
      ok-title="Cancel"
      size="md"
      id="importProviders"
      :title="$t('Import_Suppliers')"
     >
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
              :href="'/import/exemples/import_providers.csv'"
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
                    <span class="badge badge-outline-success">{{$t('Field_is_required')}}</span>
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
    title: "Provider"
  },
  data() {
    return {
      editmode: false,
      isLoading: true,
      SubmitProcessing:false,
      ImportProcessing:false,
      paymentProcessing:false,
      payment_return_Processing:false,
      showDropdown: false,
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      Filter_Name: "",
      Filter_Code: "",
      Filter_Phone: "",
      Filter_Email: "",
      import_providers: "",
      data: new FormData(),
      company_info:{},
      providers: [],
      provider: {
        id: "",
        name: "",
        code: "",
        phone: "",
        email: "",
        tax_number: "",
        country: "",
        city: "",
        adresse: ""
      },
      payment: {
        provider_id: "",
        provider_name: "",
        date:"",
        due: "",
        amount: "",
        notes: "",
        Reglement: "",
      },
       payment_return: {
        provider_id: "",
        provider_name: "",
        date:"",
        return_Due: "",
        amount: "",
        notes: "",
        Reglement: "",
      },
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
          label: this.$t("City"),
          field: "city",
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
          label: this.$t("Total_Purchase_Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
         {
          label: this.$t("Total_Purchase_Return_Due"),
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
    //------------- Submit Validation Create & Edit Provider
    Submit_Provider() {
      this.$refs.Create_Provider.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Provider();
          } else {
            this.Update_provider();
          }
        }
      });
    },

    //----------------------------------- Show import providers -------------------------------\\
    Show_import_providers() {
      this.$bvModal.show("importProviders");
    },

    //------------------------------ Event Import providers -------------------------------\\
    onFileSelected(e) {
      this.import_providers = "";
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
        this.import_providers = file;
      }
    },

    //----------------------------------------Submit  import providers-----------------\\
    Submit_import() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.ImportProcessing = true;
      self.data.append("providers", self.import_providers);
      axios
        .post("providers/import/csv", self.data)
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
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.makeToast(
            "danger",
            this.$t("Please_follow_the_import_instructions"),
            this.$t("Failed")
          );
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
        this.Get_Providers(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Providers(1);
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
      this.Get_Providers(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Providers(this.serverParams.page);
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
      this.Get_Providers(this.serverParams.page);
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------------ Providers PDF -----------------------\\
    Providers_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Code", dataKey: "code" },
        { title: "Name", dataKey: "name" },
        { title: "Phone", dataKey: "phone" },
        { title: "Purchase Due", dataKey: "due" },
        { title: "Purchase Return Due", dataKey: "return_Due" },
        { title: "Tax Number", dataKey: "tax_number" },
        { title: "Email", dataKey: "email" },
        { title: "Country", dataKey: "country" },
        { title: "City", dataKey: "city" },
        { title: "Purchase Due", dataKey: "due" },
      ];
      pdf.autoTable(columns, self.providers);
      pdf.text("Provider List", 40, 25);
      pdf.save("Provider_List.pdf");
    },

    //------------------------------ Show Modal (create Provider) -------------------------------\\
    New_Provider() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Provider");
    },

    //------------------------------ Show Modal (Edit Provider) -------------------------------\\
    Edit_Provider(provider) {
      this.Get_Providers(this.serverParams.page);
      this.reset_Form();
      this.provider = provider;
      this.editmode = true;
      this.$bvModal.show("New_Provider");
    },

    //----------------------------  Get all Providers  -----------------------\\
    Get_Providers(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "providers?page=" +
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
          this.providers = response.data.providers;
          this.totalRows = response.data.totalRows;
          this.company_info = response.data.company_info;

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

    //---------------------------- Create Provider  -----------------------\\
    Create_Provider() {
      this.SubmitProcessing = true;
      axios
        .post("providers", {
          name: this.provider.name,
          email: this.provider.email,
          phone: this.provider.phone,
          tax_number: this.provider.tax_number,
          country: this.provider.country,
          city: this.provider.city,
          adresse: this.provider.adresse
        })
        .then(response => {
          Fire.$emit("Event_Provider");

          this.makeToast(
            "success",
            this.$t("Create.TitleSupplier"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //--------------------------- Update Provider -----------------------\\
    Update_provider() {
      this.SubmitProcessing = true;
      axios
        .put("providers/" + this.provider.id, {
          name: this.provider.name,
          email: this.provider.email,
          tax_number: this.provider.tax_number,
          phone: this.provider.phone,
          country: this.provider.country,
          city: this.provider.city,
          adresse: this.provider.adresse
        })
        .then(response => {
          Fire.$emit("Event_Provider");

          this.makeToast(
            "success",
            this.$t("Update.TitleSupplier"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //----------------------------------- Show Details provider -------------------------------\\
    showDetails(provider) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.provider = provider;
      Fire.$emit("Get_Details_Provider");
    },

    //--------------------------------- Reset Form -----------------------\\
    reset_Form() {
      this.provider = {
        id: "",
        name: "",
        phone: "",
        email: "",
        country: "",
        tax_number: "",
        city: "",
        adresse: ""
      };
    },

    //---------------------------- DELETE Provider -----------------------\\

    Remove_Provider(id) {
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
            .delete("providers/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.SupplierDeleted"),
                "success"
              );

              Fire.$emit("Delete_Provider");
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.ProviderError"),
                "warning"
              );
            });
        }
      });
    },

    //---- Delete providers by selection

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
            .post("providers/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.SupplierDeleted"),
                "success"
              );

              Fire.$emit("Delete_Provider");
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



     //------ Validate Form Submit_Payment_Purchase_due
    Submit_Payment_Purchase_due() {
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
        provider_id: "",
        provider_name: "",
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
      this.payment.provider_id = row.id;
      this.payment.provider_name = row.name;
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
        .post("pay_supplier_due", {
          provider_id: this.payment.provider_id,
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

    
    //-------------------------------Pay Purchase return due -----------------------------------\\

     //------ Validate Form Submit_Payment_purchase_return_due

    Submit_Payment_purchase_return_due() {
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
        provider_id: "",
        provider_name: "",
        date:"",
        return_Due: "",
        amount: "",
        notes: "",
        Reglement: "",
      };
    },

    //------------------------------ Show Modal Pay_return_due-------------------------------\\
    Pay_return_due(row) {
      this.reset_Form_payment_return_due();
      this.payment_return.provider_id = row.id;
      this.payment_return.provider_name = row.name;
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
        .post("pay_purchase_return_due", {
          provider_id: this.payment_return.provider_id,
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


    
  },

  //----------------------------- Created function-------------------\\

  created: function() {
    this.Get_Providers(1);

     Fire.$on("Event_pay_due", () => {
      setTimeout(() => {
        this.Get_Providers(this.serverParams.page);
        this.$bvModal.hide("modal_Pay_due");
      }, 500);
       this.$bvModal.show("Show_invoice");
      //  setTimeout(() => this.print_it(), 1000);
    });

    Fire.$on("Event_pay_return_due", () => {
      setTimeout(() => {
        this.Get_Providers(this.serverParams.page);
        this.$bvModal.hide("modal_Pay_return_due");
      }, 500);
       this.$bvModal.show("Show_invoice_return");
      //  setTimeout(() => this.print_return_due(), 1000);
    });

    Fire.$on("Get_Details_Provider", () => {
      // Complete the animation of theprogress bar.
      setTimeout(() => NProgress.done(), 500);
      this.$bvModal.show("showDetails");
    });

    Fire.$on("Event_Provider", () => {
      setTimeout(() => {
        this.Get_Providers(this.serverParams.page);
        this.$bvModal.hide("New_Provider");
      }, 500);
    });

    Fire.$on("Delete_Provider", () => {
      setTimeout(() => {
        this.Get_Providers(this.serverParams.page);
      }, 500);
    });

    Fire.$on("Event_import", () => {
      setTimeout(() => {
        this.Get_Providers(this.serverParams.page);
        this.$bvModal.hide("importProviders");
      }, 500);
    });
  }
};
</script>
