<template>
  <div class="main-content">
    <breadcumb :page="$t('CustomersReport')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-row v-if="!isLoading">
      <!-- ICON BG -->

      <b-col lg="3" md="6" sm="12">
        <b-card class="card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
          <i class="i-Full-Cart"></i>
          <div class="content">
            <p class="text-muted mt-2 mb-0">{{$t('Sales')}}</p>
            <p class="text-primary text-24 line-height-1 mb-2">{{client.total_sales}}</p>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" sm="12">
        <b-card class="card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
          <i class="i-Financial"></i>
          <div class="content">
            <p class="text-muted mt-2 mb-0">{{$t('TotalAmount')}}</p>
            <p
              class="text-primary text-24 line-height-1 mb-2"
            >{{currentUser.currency}} {{formatNumber((client.total_amount),2)}}</p>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" sm="12">
        <b-card class="card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
          <i class="i-Money-2"></i>
          <div class="content">
            <p class="text-muted mt-2 mb-0">{{$t('TotalPaid')}}</p>
            <p
              class="text-primary text-24 line-height-1 mb-2"
            >{{currentUser.currency}} {{formatNumber((client.total_paid),2)}}</p>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" sm="12">
        <b-card class="card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
          <i class="i-Money-Bag"></i>
          <div class="content">
            <p class="text-muted mt-2 mb-0">{{$t('Due')}}</p>
            <p
              class="text-primary text-24 line-height-1 mb-2"
            >{{currentUser.currency}} {{formatNumber((client.due),2)}}</p>
          </div>
        </b-card>
      </b-col>
    </b-row>

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
                      :to="'/app/sales/detail/'+props.row.id"
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

            <!-- Returns Table -->
            <b-tab :title="$t('Returns')">
              <vue-good-table
                mode="remote"
                :columns="columns_returns"
                :totalRows="totalRows_returns"
                :rows="returns_customer"
                @on-page-change="PageChangeReturn"
                @on-per-page-change="onPerPageChangeReturn"
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
                      :to="'/app/sale_return/detail/'+props.row.id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.Ref}}</span>
                    </router-link>
                  </div>
                  <div v-else-if="props.column.field == 'sale_ref' && props.row.sale_id">
                    <router-link
                      :to="'/app/sales/detail/'+props.row.sale_id"
                    >
                      <span class="ul-btn__text ml-1">{{props.row.sale_ref}}</span>
                    </router-link>
                  </div>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- Payments Table -->
            <b-tab :title="$t('SalesInvoice')">
              <vue-good-table
                mode="remote"
                :columns="columns_payments"
                :totalRows="totalRows_payments"
                :rows="payments"
                @on-page-change="PageChangePayments"
                @on-per-page-change="onPerPageChangePayments"
                @on-search="onSearch_payments"
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
                <b-button @click="Payments_PDF()" size="sm" variant="outline-success ripple m-1">
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
      totalRows_returns: "",
      totalRows_payments: "",
      limit_quotations: "10",
      limit_returns: "10",
      limit_sales: "10",
      limit_payments: "10",
      sales_page: 1,
      quotations_page: 1,
      Return_sale_page: 1,
      Payment_sale_page: 1,
      isLoading: true,
      payments: [],
      sales: [],
      quotations: [],
      returns_customer: [],

      search_sales:"",
      search_payments:"",
      search_quotations:"",
      search_return_sales:"",

      client: {
        id: "",
        name: "",
        total_sales: 0,
        total_amount: 0,
        total_paid: 0,
        due: 0
      }
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    columns_quotations() {
      return [
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
          label: this.$t("Status"),
          field: "statut",
          html: true,
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
    columns_returns() {
      return [
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
          label: this.$t("Sale_Ref"),
          field: "sale_ref",
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
          label: this.$t("Status"),
          field: "statut",
          html: true,
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
    columns_payments() {
      return [
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
          label: this.$t("Sale"),
          field: "Sale_Ref",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("ModePaiement"),
          field: "Reglement",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Amount"),
          field: "montant",
          tdClass: "text-left",
          thClass: "text-left",
          type: "decimal",
          sortable: false
        }
      ];
    }
  },

  methods: {

     //----------------------------------- Sales PDF ------------------------------\\
    Sales_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");
      let columns = [
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

     //----------------------------------------- Sales Return PDF -----------------------\\
    Sale_Return_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "sale_ref", dataKey: "sale_ref" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status", dataKey: "statut" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.returns_customer);
      pdf.text("Sales Return List", 40, 25);
      pdf.save("Sales Return.pdf");
    },

       //----------------------------------- Sales PDF ------------------------------\\
    Payments_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Date", dataKey: "date" },
        { title: "Ref", dataKey: "Ref" },
        { title: "Sale", dataKey: "Sale_Ref" },
        { title: "Reglement", dataKey: "Reglement" },
        { title: "Amount", dataKey: "montant" },
      ];
      pdf.autoTable(columns, self.payments);
      pdf.text("Payments List", 40, 25);
      pdf.save("Payments_List.pdf");
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

    //------------------------------ Show Reports -------------------------\\
    Get_Reports() {
      let id = this.$route.params.id;
      axios
        .get(`report/client/${id}`)
        .then(response => {
          this.client = response.data.report;
        })
        .catch(response => {});
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

    //--------------------------- Get sales By Customer -------------\\
    Get_Sales(page) {
      axios
        .get(
          "/report/client_sales?page=" +
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
    PageChangePayments({ currentPage }) {
      if (this.Payment_sale_page !== currentPage) {
        this.Get_Payments(currentPage);
      }
    },

    //--------------------------- Limit Page Payments -------------\\
    onPerPageChangePayments({ currentPerPage }) {
      if (this.limit_payments !== currentPerPage) {
        this.limit_payments = currentPerPage;
        this.Get_Payments(1);
      }
    },

     onSearch_payments(value) {
      this.search_payments = value.searchTerm;
      this.Get_Payments(1);
    },

    //--------------------------- Get Payments By Customer -------------\\
    Get_Payments(page) {
      axios
        .get(
          "report/client_payments?page=" +
            page +
            "&limit=" +
            this.limit_payments +
            "&search=" +
            this.search_payments +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.payments = response.data.payments;
          this.totalRows_payments = response.data.totalRows;
        })
        .catch(response => {});
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

    //--------------------------- Get Quotations By Customer -------------\\
    Get_Quotations(page) {
      axios
        .get(
          "report/client_quotations?page=" +
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
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //--------------------------- Event Page Change -------------\\
    PageChangeReturn({ currentPage }) {
      if (this.Return_sale_page !== currentPage) {
        this.Get_Returns(currentPage);
      }
    },

    //--------------------------- Limit Page Returns -------------\\
    onPerPageChangeReturn({ currentPerPage }) {
      if (this.limit_returns !== currentPerPage) {
        this.limit_returns = currentPerPage;
        this.Get_Returns(1);
      }
    },

     onSearch_return_sales(value) {
      this.search_return_sales = value.searchTerm;
      this.Get_Returns(1);
    },

    //--------------------------- Get Returns By Customer -------------\\
    Get_Returns(page) {
      axios
        .get(
          "/report/client_returns?page=" +
            page +
            "&limit=" +
            this.limit_returns +
            "&search=" +
            this.search_return_sales +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.returns_customer = response.data.returns_customer;
          this.totalRows_returns = response.data.totalRows;
        })
        .catch(response => {});
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_Reports();
    this.Get_Sales(1);
    this.Get_Payments(1);
    this.Get_Quotations(1);
    this.Get_Returns(1);
  }
};
</script>