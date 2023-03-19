<template>
  <div class="main-content">
    <breadcumb :page="$t('PurchasesReport')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

     <b-col md="12" class="text-center" v-if="!isLoading">
        <date-range-picker 
          v-model="dateRange" 
          :startDate="startDate" 
          :endDate="endDate" 
           @update="Submit_filter_dateRange"
          :locale-data="locale" > 

          <template v-slot:input="picker" style="min-width: 350px;">
              {{ picker.startDate.toJSON().slice(0, 10)}} - {{ picker.endDate.toJSON().slice(0, 10)}}
          </template>        
        </date-range-picker>
      </b-col>

    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="rows"
        :group-options="{
          enabled: true,
          headerPosition: 'bottom',
        }"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
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
        :styleClass="'order-table vgt-table'"
      >
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info ripple m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="Purchase_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="purchases"
              :columns="columns"
              :file-name="'purchases_report'"
              :file-type="'xlsx'"
              :sheet-name="'purchases_report'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
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
        </template>
      </vue-good-table>
    </b-card>

    <!-- Sidebar Filter -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- Reference -->
          <b-col md="12">
            <b-form-group :label="$t('Reference')">
              <b-form-input label="Reference" :placeholder="$t('Reference')" v-model="Filter_Ref"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Supplier  -->
          <b-col md="12">
            <b-form-group :label="$t('Supplier')">
              <v-select
                :reduce="label => label.value"
                :placeholder="$t('Choose_Supplier')"
                v-model="Filter_Supplier"
                :options="suppliers.map(suppliers => ({label: suppliers.name, value: suppliers.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Status  -->
          <b-col md="12">
            <b-form-group :label="$t('Status')">
              <select v-model="Filter_status" type="text" class="form-control">
                <option value selected>All</option>
                <option value="received">Received</option>
                <option value="pending">Pending</option>
                <option value="ordered">Ordered</option>
              </select>
            </b-form-group>
          </b-col>

          <!-- Payment Status  -->
          <b-col md="12">
            <b-form-group :label="$t('PaymentStatus')">
              <select v-model="Filter_Payment" type="text" class="form-control">
                <option value selected>All</option>
                <option value="paid">Paid</option>
                <option value="partial">partial</option>
                <option value="unpaid">UnPaid</option>
              </select>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button
              @click="Get_Purchases(serverParams.page)"
              variant="primary ripple m-1"
              size="sm"
              block
            >
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger ripple m-1" size="sm" block>
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>
  </div>
</template>

<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";
import DateRangePicker from 'vue2-daterange-picker'
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'
import moment from 'moment'

export default {
  metaInfo: {
    title: "Report Purchases"
  },
components: { DateRangePicker },
  data() {
    return {
      isLoading: true,
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      limit: "10",
      search: "",
      totalRows: "",
      Filter_Supplier: "",
      Filter_status: "",
      Filter_Payment: "",
      Filter_Ref: "",
      suppliers: [],
      purchases: [],
      rows: [{
          statut: 'Total',
         
          children: [
             
          ],
      },],
      today_mode: true,
      startDate: "", 
      endDate: "", 
      dateRange: { 
       startDate: "", 
       endDate: "" 
      }, 
      locale:{ 
          //separator between the two ranges apply
          Label: "Apply", 
          cancelLabel: "Cancel", 
          weekLabel: "W", 
          customRangeLabel: "Custom Range", 
          daysOfWeek: moment.weekdaysMin(), 
          //array of days - see moment documenations for details 
          monthNames: moment.monthsShort(), //array of month names - see moment documenations for details 
          firstDay: 1 //ISO first day of week - see moment documenations for details
        },
    };
  },

  computed: {
    columns() {
      return [
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
          label: this.$t("Supplier"),
          field: "provider_name",
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
        {
          label: this.$t("Total"),
          field: "GrandTotal",
          type: "decimal",
          headerField: this.sumCount,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Paid"),
          field: "paid_amount",
          type: "decimal",
          headerField: this.sumCount2,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Due"),
          field: "due",
          type: "decimal",
          headerField: this.sumCount3,
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PaymentStatus"),
          field: "payment_status",
          html: true,
          tdClass: "text-left",
          thClass: "text-left"
        }
      ];
    }
  },

  methods: {

    sumCount(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].GrandTotal;
      }
      return sum;
    },
    sumCount2(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].paid_amount;
      }
      return sum;
    },
    sumCount3(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].due;
      }
      return sum;
    },
    
    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Purchases(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Purchases(1);
      }
    },

    //---- Event on Sort Change
    onSortChange(params) {
      let field = "";
      if (params[0].field == "provider_name") {
        field = "provider_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: field
        }
      });
      this.Get_Purchases(this.serverParams.page);
    },

    //---- Event on Search

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Purchases(this.serverParams.page);
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_Supplier = "";
      this.Filter_status = "";
      this.Filter_Payment = "";
      this.Filter_Ref = "";
      this.Get_Purchases(this.serverParams.page);
    },

    //---------------------- Purchases PDF -------------------------------\\
    Purchase_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Ref", dataKey: "Ref" },
        { title: "Provider", dataKey: "provider_name" },
        { title: "Status", dataKey: "statut" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status Payment", dataKey: "payment_status" }
      ];
      pdf.autoTable(columns, self.purchases);
      pdf.text("Purchase report", 40, 25);
      pdf.save("Purchase_report.pdf");
    },

    //---------------------------------------- Set To Strings-------------------------\\
    setToStrings() {
      // Simply replaces null values with strings=''
      if (this.Filter_Supplier === null) {
        this.Filter_Supplier = "";
      }
    },

    //----------------------------- Submit Date Picker -------------------\\
    Submit_filter_dateRange() {
      var self = this;
      self.startDate =  self.dateRange.startDate.toJSON().slice(0, 10);
      self.endDate = self.dateRange.endDate.toJSON().slice(0, 10);
      self.Get_Purchases(1);
    },


    get_data_loaded() {
      var self = this;
      if (self.today_mode) {
        let today = new Date()

        self.startDate = today.getFullYear();
        self.endDate = new Date().toJSON().slice(0, 10);

        self.dateRange.startDate = today.getFullYear();
        self.dateRange.endDate = new Date().toJSON().slice(0, 10);
        
      }
    },


    //------------------------------------------------ Get Report Purchases -------------------------------\\
    Get_Purchases(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();
      this.get_data_loaded();

      axios
        .get(
          "/report/purchases?page=" +
            page +
            "&Ref=" +
            this.Filter_Ref +
            "&provider_id=" +
            this.Filter_Supplier +
            "&statut=" +
            this.Filter_status +
            "&payment_statut=" +
            this.Filter_Payment +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit +
            "&to=" +
            this.endDate +
            "&from=" +
            this.startDate

        )
        .then(response => {
          this.purchases = response.data.purchases;
          this.suppliers = response.data.suppliers;
          this.totalRows = response.data.totalRows;
          this.rows[0].children = this.purchases;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
          this.today_mode = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
            this.today_mode = false;
          }, 500);
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
    }
  },

  //-----------------------------Created function-------------------
  created: function() {
    this.Get_Purchases(1);
  }
};
</script>