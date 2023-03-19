<template>
  <div class="main-content">
    <breadcumb :page="$t('SuppliersReport')" :folder="$t('Reports')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

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
        styleClass="tableOne table-hover vgt-table mt-4"
      >
       <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a title="PDF" class="cursor-pointer" v-b-tooltip.hover @click="Download_PDF(props.row , props.row.id)">
              <i class="i-File-Copy text-25 text-success"></i>
            </a>
            <router-link title="Report" :to="'/app/reports/detail_supplier/'+props.row.id">
             <i class="i-Eye text-25 text-info"></i>
            </router-link>
          </span>
        </template>
      </vue-good-table>
    </b-card>
  </div>
</template>


<script>
import NProgress from "nprogress";
import { mapActions, mapGetters } from "vuex";

export default {
  metaInfo: {
    title: "Report Providers"
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
      search: "",
      totalRows: "",
      providers: [],
      provider: {},
       rows: [{
          total_purchase: 'Total',
         
          children: [
             
          ],
      },],
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    columns() {
      return [
        {
          label: this.$t("SupplierName"),
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
          label: this.$t("Purchases"),
          field: "total_purchase",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("TotalAmount"),
          field: "total_amount",
          type: "decimal",
          headerField: this.sumCount,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Paid"),
          field: "total_paid",
          type: "decimal",
          headerField: this.sumCount2,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total_Purchase_Due"),
          field: "due",
          type: "decimal",
          headerField: this.sumCount3,
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Total_Purchase_Return_Due"),
          field: "return_Due",
          type: "decimal",
          headerField: this.sumCount4,
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

    sumCount(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].total_amount;
      }
      return sum;
    },
    sumCount2(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].total_paid;
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
    sumCount4(rowObj) {
     
    	let sum = 0;
      for (let i = 0; i < rowObj.children.length; i++) {
        sum += rowObj.children[i].return_Due;
      }
      return sum;
    },

    //--------------------------- Download_PDF -------------------------------\\
    Download_PDF(provider , id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
     
       axios
        .get("report/provider_pdf/" + id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "report-" + provider.name + ".pdf");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Provider_Report(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Provider_Report(1);
      }
    },

    //---- Event on Sort Change

    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Provider_Report(this.serverParams.page);
    },

    //---- Event on Search

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Provider_Report(this.serverParams.page);
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

    //--------------------------- Get Customer Report -------------\\

    Get_Provider_Report(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "report/provider?page=" +
            page +
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
          this.providers = response.data.report;
          this.totalRows = response.data.totalRows;
          this.rows[0].children = this.providers;
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
    this.Get_Provider_Report(1);
  }
};
</script>