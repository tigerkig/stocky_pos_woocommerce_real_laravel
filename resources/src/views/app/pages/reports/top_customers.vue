<template>
  <div class="main-content">
    <breadcumb :page="$t('Top_customers')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <vue-good-table
      v-if="!isLoading"
      mode="remote"
      :columns="columns"
      :totalRows="totalRows"
      :rows="customers"
      @on-page-change="onPageChange"
      @on-per-page-change="onPerPageChange"
      :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
      styleClass="table-hover tableOne vgt-table"
    >
     <div slot="table-actions" class="mt-2 mb-3">
        <b-button @click="export_PDF()" size="sm" variant="outline-success ripple m-1">
          <i class="i-File-Copy"></i> PDF
        </b-button>
      </div>

     <template slot="table-row" slot-scope="props">
      
        <div v-if="props.column.field == 'total'">
          <span>{{currentUser.currency}} {{props.row.total}}</span>
        </div>
      </template>

    </vue-good-table>
    <!-- </b-card> -->
  </div>
</template>

<script>
import NProgress from "nprogress";
import { mapGetters } from "vuex";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Top Customers"
  },
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
      totalRows: "",
      customers: [],
    };
  },

  computed: {
     ...mapGetters(["currentUser"]),
    columns() {
      return [
        {
          label: this.$t("Name"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Phone"),
          field: "phone",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Email"),
          field: "email",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("TotalSales"),
          field: "total_sales",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
         {
          label: this.$t("TotalAmount"),
          field: "total",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
       
      ];
    }
  },

  methods: {

     //----------------------------------- Export PDF ------------------------------\\
    export_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Customer Name", dataKey: "name" },
        { title: "Phone", dataKey: "phone" },
        { title: "Email", dataKey: "email" },
        { title: "Total Sales", dataKey: "total_sales" },
        { title: "Total Amount", dataKey: "total" },
      ];
      pdf.autoTable(columns, self.customers);
      pdf.text("Top Customers", 40, 25);
      pdf.save("Top_Customers.pdf");
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_top_Customers(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_top_Customers(1);
      }
    },

    //----------------------------- Get_top_Customers-------------------\\
    Get_top_Customers(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "report/top_customers?page=" +
            page +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.customers = response.data.customers;
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
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_top_Customers(1);
  }
};
</script>