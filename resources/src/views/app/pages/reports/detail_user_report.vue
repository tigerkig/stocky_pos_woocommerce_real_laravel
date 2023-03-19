<template>
  <div class="main-content">
    <breadcumb :page="$t('User_report')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-row v-if="!isLoading">
      <b-col md="12">
        <b-card class="card mb-30" header-bg-variant="transparent ">
          <b-tabs active-nav-item-class="nav nav-tabs" content-class="mt-3">
           

            <!-- Sales Table -->
            <b-tab :title="$t('Sales')">
              <vue-good-table
                mode="remote"
                :columns="columns_sales"
                :totalRows="totalRows_sales"
                :rows="sales"
                @on-page-change="PageChangeSales"
                @on-per-page-change="onPerPageChangeSales"
                @on-search="onSearch_sales"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
              <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Sales_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'completed'"
                      class="badge badge-outline-success"
                    >{{$t('complete')}}</span>
                    <span
                      v-else-if="props.row.statut == 'pending'"
                      class="badge badge-outline-info"
                    >{{$t('Pending')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Ordered')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'shipping_status'">
                  <span
                    v-if="props.row.shipping_status == 'ordered'"
                    class="badge badge-outline-warning"
                  >{{$t('Ordered')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'packed'"
                    class="badge badge-outline-info"
                  >{{$t('Packed')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'shipped'"
                    class="badge badge-outline-secondary"
                  >{{$t('Shipped')}}</span>

                  <span
                    v-else-if="props.row.shipping_status == 'delivered'"
                    class="badge badge-outline-success"
                  >{{$t('Delivered')}}</span>

                  <span v-else-if="props.row.shipping_status == 'cancelled'" class="badge badge-outline-danger">{{$t('Cancelled')}}</span>
                </div>
                  <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/sales/detail/'+props.row.sale_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

             <!-- Quotations Table -->
            <b-tab :title="$t('Quotations')">
              <vue-good-table
                mode="remote"
                :columns="columns_quotations"
                :totalRows="totalRows_quotations"
                :rows="quotations"
                @on-page-change="PageChangeQuotation"
                @on-per-page-change="onPerPageChangeQuotation"
                @on-search="onSearch_quotations"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
              <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Quotation_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'sent'"
                      class="badge badge-outline-success"
                    >{{$t('Sent')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/quotations/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Purchases Table -->
            <b-tab :title="$t('Purchases')">
              <vue-good-table
                mode="remote"
                :columns="columns_purchases"
                :totalRows="totalRows_purchases"
                :rows="purchases"
                @on-page-change="PageChangePurchases"
                @on-per-page-change="onPerPageChangePurchases"
                @on-search="onSearch_purchases"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
              <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Purchase_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'received'"
                      class="badge badge-outline-success"
                    >{{$t('Received')}}</span>
                    <span
                      v-else-if="props.row.statut == 'pending'"
                      class="badge badge-outline-info"
                    >{{$t('Pending')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Ordered')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                   <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/purchases/detail/'+props.row.purchase_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Sales Return Table -->
            <b-tab :title="$t('SalesReturn')">
              <vue-good-table
                mode="remote"
                :columns="columns_sales_return"
                :totalRows="totalRows_sales_return"
                :rows="sales_return"
                @on-page-change="Page_Change_sales_Return"
                @on-per-page-change="onPerPage_Change_sales_Return"
                @on-search="onSearch_return_sales"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
              <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Sale_Return_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'received'"
                      class="badge badge-outline-success"
                    >{{$t('Received')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>

                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/sale_return/detail/'+props.row.return_sale_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

             <!-- Purchase Return Table -->
            <b-tab :title="$t('PurchasesReturn')">
              <vue-good-table
                mode="remote"
                :columns="columns_purchase_return"
                :totalRows="totalRows_purchase_return"
                :rows="purchases_return"
                @on-page-change="Page_Change_purchases_Return"
                @on-per-page-change="onPerPage_Change_purchases_Return"
                @on-search="onSearch_return_purchases"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Returns_Purchase_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                    <span
                      v-if="props.row.statut == 'received'"
                      class="badge badge-outline-success"
                    >{{$t('Received')}}</span>
                    <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
                  </div>

                  <div v-else-if="props.column.field == 'payment_status'">
                    <span
                      v-if="props.row.payment_status == 'paid'"
                      class="badge badge-outline-success"
                    >{{$t('Paid')}}</span>
                    <span
                      v-else-if="props.row.payment_status == 'partial'"
                      class="badge badge-outline-primary"
                    >{{$t('partial')}}</span>
                    <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
                  </div>
                  <div v-else-if="props.column.field == 'Ref'">
                    <router-link
                      :to="'/app/purchase_return/detail/'+props.row.return_purchase_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

             <!-- Transfers Table -->
            <b-tab :title="$t('StockTransfers')">
              <vue-good-table
                mode="remote"
                :columns="columns_transfers"
                :totalRows="totalRows_transfers"
                :rows="transfers"
                @on-page-change="PageChangeTransfer"
                @on-per-page-change="onPerPageChangeTransfer"
                @on-search="onSearch_transfers"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
              <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Transfer_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
                <template slot="table-row" slot-scope="props">
                  <div v-if="props.column.field == 'statut'">
                  <span
                    v-if="props.row.statut == 'completed'"
                    class="badge badge-outline-success"
                  >{{$t('complete')}}</span>
                  <span
                    v-else-if="props.row.statut == 'sent'"
                    class="badge badge-outline-warning"
                  >{{$t('Sent')}}</span>
                  <span v-else class="badge badge-outline-danger">{{$t('Pending')}}</span>
                </div>
                  
                </template>
              </vue-good-table>
            </b-tab>

             <!-- Adjustment Table -->
            <b-tab :title="$t('Adjustment')">
              <vue-good-table
                mode="remote"
                :columns="columns_adjustments"
                :totalRows="totalRows_adjustments"
                :rows="adjustments"
                @on-page-change="PageChangeAdjustment"
                @on-per-page-change="onPerPageChangeAdjustment"
                @on-search="onSearch_adjustments"
                :search-options="{
                  placeholder: $t('Search_this_table'),
                  enabled: true,
                }"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
               <div slot="table-actions" class="mt-2 mb-3">
                <b-button @click="Adjustment_PDF()" size="sm" variant="outline-success ripple m-1">
                  <i class="i-File-Copy"></i> PDF
                </b-button>
              </div>
              </vue-good-table>
            </b-tab>

             

          </b-tabs>
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  data() {
    return {
      totalRows_quotations: "",
      totalRows_sales: "",
      totalRows_sales_return: "",
      totalRows_purchases_return: "",
      totalRows_purchases: "",
      totalRows_transfers: "",
      totalRows_adjustments: "",

      limit_quotations: "10",
      limit_sales_return: "10",
      limit_purchases_return: "10",
      limit_sales: "10",
      limit_purchases: "10",
      limit_transfers: "10",
      limit_adjustments: "10",

      sales_page: 1,
      quotations_page: 1,
      Return_sale_page: 1,
      Return_purchase_page: 1,
      purchases_page: 1,
      transfers_page: 1,
      adjustments_page: 1,

      search_sales:"",
      search_purchases:"",
      search_quotations:"",
      search_return_sales:"",
      search_return_purchases:"",
      search_transfers:"",
      search_adjustments:"",

      isLoading: true,
      purchases: [],
      sales: [],
      quotations: [],
      sales_return: [],
      purchases_return: [],
      transfers: [],
      adjustments: [],
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    columns_quotations() {
      return [
         {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
       
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Status"),
          field: "statut",
          tdClass: "text-left",
          thClass: "text-left",
          html: true,
          sortable: false
        }
      ];
    },
    columns_sales() {
      return [
        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Shipping_status"),
          field: "shipping_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left"
        },
      ];
    },
    columns_sales_return() {
      return [
        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Customer"),
          field: "client_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_purchases() {
      return [
        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Supplier"),
          field: "provider_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_purchase_return() {
      return [
         {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Supplier"),
          field: "provider_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    },
    columns_transfers() {
      return [
        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("FromWarehouse"),
          field: "from_warehouse",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("ToWarehouse"),
          field: "to_warehouse",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Items"),
          field: "items",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          tdClass: "text-left",
          thClass: "text-left"
        },
       
      ];
    },
    columns_adjustments() {
      return [
        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Reference"),
          field: "Ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("TotalProducts"),
          field: "items",
          type: "decimal",
          tdClass: "text-left",
          thClass: "text-left"
        },
      ];
    }
  },

  methods: {

     //----------------------------------- Sales PDF ------------------------------\\
    Sales_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Status", dataKey: "statut" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status Payment", dataKey: "payment_status" },
        { title: "Shipping Status", dataKey: "shipping_status" }
      ];
      pdf.autoTable(columns, self.sales);
      pdf.text("Sale List", 40, 25);
      pdf.save("Sale_List.pdf");
    },

      //------------------------------------- Quotations PDF -------------------------\\
    Quotation_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Date", dataKey: "date" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Status", dataKey: "statut" },
        { title: "Total", dataKey: "GrandTotal" }
      ];
      pdf.autoTable(columns, self.quotations);
      pdf.text("Quotation List", 40, 25);
      pdf.save("Quotation_List.pdf");
    },

     //---------------------- Purchases PDF -------------------------------\\
    Purchase_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Supplier", dataKey: "provider_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status", dataKey: "statut" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.purchases);
      pdf.text("Purchase List", 40, 25);
      pdf.save("Purchase_List.pdf");
    },

     //----------------------------------------- Sales Return PDF -----------------------\\
    Sale_Return_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status", dataKey: "statut" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.sales_return);
      pdf.text("Sales Return List", 40, 25);
      pdf.save("Sales Return.pdf");
    },

      //----------------------------------------- Returns Purchase PDF -----------------------\\
    Returns_Purchase_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Supplier", dataKey: "provider_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status", dataKey: "statut" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.purchases_return);
      pdf.text("Purchase Returns", 40, 25);
      pdf.save("purchase_returns.pdf");
    },

     //-------------------------------------- Transfer PDF ------------------------------\\
    Transfer_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Ref", dataKey: "Ref" },
        { title: "From warehouse", dataKey: "from_warehouse" },
        { title: "To warehouse", dataKey: "to_warehouse" },
        { title: "Total Products", dataKey: "items" },
        { title: "Status", dataKey: "statut" }
      ];
      pdf.autoTable(columns, self.transfers);
      pdf.text("Transfer List", 40, 25);
      pdf.save("Transfer_List.pdf");
    },

     //-------------------------------------- Adjustement PDF ------------------------------\\
    Adjustment_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Username", dataKey: "username" },
        { title: "Date", dataKey: "date" },
        { title: "Reference", dataKey: "Ref" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Total Products", dataKey: "items" }
      ];
      pdf.autoTable(columns, self.adjustments);
      pdf.text("Adjustment List", 40, 25);
      pdf.save("Adjustment_List.pdf");
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


    //--------------------------- Event Page Change -------------\\
    PageChangeSales({ currentPage }) {
      if (this.sales_page !== currentPage) {
        this.Get_Sales(currentPage);
      }
    },

    //--------------------------- Limit Page Sales -------------\\
    onPerPageChangeSales({ currentPerPage }) {
      if (this.limit_sales !== currentPerPage) {
        this.limit_sales = currentPerPage;
        this.Get_Sales(1);
      }
    },

    onSearch_sales(value) {
      this.search_sales = value.searchTerm;
      this.Get_Sales(1);
    },

    //--------------------------- get_sales_by_user -------------\\
    Get_Sales(page) {
      axios
        .get(
          "/report/get_sales_by_user?page=" +
            page +
            "&limit=" +
            this.limit_sales +
            "&search=" +
            this.search_sales +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.sales = response.data.sales;
          this.totalRows_sales = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Event Page Change -------------\\
    PageChangePurchases({ currentPage }) {
      if (this.purchases_page !== currentPage) {
        this.Get_Sales(currentPage);
      }
    },

    //--------------------------- Limit Page Purchases -------------\\
    onPerPageChangePurchases({ currentPerPage }) {
      if (this.limit_purchases !== currentPerPage) {
        this.limit_purchases = currentPerPage;
        this.Get_Purchases(1);
      }
    },

    onSearch_purchases(value) {
      this.search_purchases = value.searchTerm;
      this.Get_Purchases(1);
    },

    //--------------------------- Get Purchases By user -------------\\
    Get_Purchases(page) {
      axios
        .get(
          "report/get_purchases_by_user?page=" +
            page +
            "&limit=" +
            this.limit_purchases +
            "&search=" +
            this.search_purchases +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.purchases = response.data.purchases;
          this.totalRows_purchases = response.data.totalRows;
          this.isLoading = false;
        })
        .catch(response => {
           setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //--------------------------- Event Page Change -------------\\
    PageChangeQuotation({ currentPage }) {
      if (this.quotations_page !== currentPage) {
        this.Get_Quotations(currentPage);
      }
    },

    //--------------------------- Limit Page Quotations -------------\\
    onPerPageChangeQuotation({ currentPerPage }) {
      if (this.limit_quotations !== currentPerPage) {
        this.limit_quotations = currentPerPage;
        this.Get_Quotations(1);
      }
    },

    onSearch_quotations(value) {
      this.search_quotations = value.searchTerm;
      this.Get_Quotations(1);
    },

    //--------------------------- Get Quotations By user -------------\\
    Get_Quotations(page) {
      axios
        .get(
          "report/get_quotations_by_user?page=" +
            page +
            "&limit=" +
            this.limit_quotations +
            "&search=" +
            this.search_quotations +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.quotations = response.data.quotations;
          this.totalRows_quotations = response.data.totalRows;
         
        })
        .catch(response => {
         
        });
    },

     //--------------------------- Event Page Change -------------\\
    PageChangeTransfer({ currentPage }) {
      if (this.transfers_page !== currentPage) {
        this.Get_Transfers(currentPage);
      }
    },

    //--------------------------- Limit Page transfers -------------\\
    onPerPageChangeTransfer({ currentPerPage }) {
      if (this.limit_transfers !== currentPerPage) {
        this.limit_transfers = currentPerPage;
        this.Get_Transfers(1);
      }
    },

    onSearch_transfers(value) {
      this.search_transfers = value.searchTerm;
      this.Get_Transfers(1);
    },

    //--------------------------- Get Transfers By user -------------\\
    Get_Transfers(page) {
      axios
        .get(
          "report/get_transfer_by_user?page=" +
            page +
            "&limit=" +
            this.limit_transfers +
             "&search=" +
            this.search_transfers +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.transfers = response.data.transfers;
          this.totalRows_transfers = response.data.totalRows;
         
        })
        .catch(response => {
         
        });
    },

      //--------------------------- Event Page Change -------------\\
    PageChangeAdjustment({ currentPage }) {
      if (this.adjustments_page !== currentPage) {
        this.Get_adjustments(currentPage);
      }
    },

    //--------------------------- Limit Page adjustments -------------\\
    onPerPageChangeAdjustment({ currentPerPage }) {
      if (this.limit_adjustments !== currentPerPage) {
        this.limit_adjustments = currentPerPage;
        this.Get_adjustments(1);
      }
    },

    onSearch_adjustments(value) {
      this.search_adjustments = value.searchTerm;
      this.Get_adjustments(1);
    },

    //--------------------------- Get adjustment By user -------------\\
    Get_adjustments(page) {
      axios
        .get(
          "report/get_adjustment_by_user?page=" +
            page +
            "&limit=" +
            this.limit_adjustments +
            "&search=" +
            this.search_adjustments +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.adjustments = response.data.adjustments;
          this.totalRows_adjustments = response.data.totalRows;
         
        })
        .catch(response => {
         
        });
    },

    //--------------------------- Event Page Change -------------\\
    Page_Change_sales_Return({ currentPage }) {
      if (this.Return_sale_page !== currentPage) {
        this.Get_Sales_Return(currentPage);
      }
    },

    //--------------------------- Limit Page sales Returns -------------\\
    onPerPage_Change_sales_Return({ currentPerPage }) {
      if (this.limit_sales_return !== currentPerPage) {
        this.limit_sales_return = currentPerPage;
        this.Get_Sales_Return(1);
      }
    },

    onSearch_return_sales(value) {
      this.search_return_sales = value.searchTerm;
      this.Get_Sales_Return(1);
    },

    //--------------------------- Get sales Returns By user -------------\\
    Get_Sales_Return(page) {
      axios
        .get(
          "/report/get_sales_return_by_user?page=" +
            page +
            "&limit=" +
            this.limit_sales_return +
            "&search=" +
            this.search_return_sales +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.sales_return = response.data.sales_return;
          this.totalRows_sales_return = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Event Page Change -------------\\
    Page_Change_purchases_Return({ currentPage }) {
      if (this.Return_purchase_page !== currentPage) {
        this.Get_Purchases_Return(currentPage);
      }
    },

    //--------------------------- Limit Page sales Returns -------------\\
    onPerPage_Change_purchases_Return({ currentPerPage }) {
      if (this.limit_purchases_return !== currentPerPage) {
        this.limit_purchases_return = currentPerPage;
        this.Get_Purchases_Return(1);
      }
    },

     onSearch_return_purchases(value) {
      this.search_return_purchases = value.searchTerm;
      this.Get_Purchases_Return(1);
    },

    //--------------------------- Get purchases Returns By user -------------\\
    Get_Purchases_Return(page) {
      axios
        .get(
          "/report/get_purchase_return_by_user?page=" +
            page +
            "&limit=" +
            this.limit_purchases_return +
            "&search=" +
            this.search_return_purchases +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.purchases_return = response.data.purchases_return;
          this.totalRows_purchases_return = response.data.totalRows;
        })
        .catch(response => {});
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_Sales(1);
    this.Get_Purchases(1);
    this.Get_Quotations(1);
    this.Get_Sales_Return(1);
    this.Get_Purchases_Return(1);
    this.Get_Transfers(1);
    this.Get_adjustments(1);
  }
};
</script>