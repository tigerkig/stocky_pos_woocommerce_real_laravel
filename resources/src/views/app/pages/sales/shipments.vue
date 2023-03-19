<template>
  <div class="main-content">
    <breadcumb :page="$t('Shipments')" :folder="$t('Sales')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="shipments"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        placeholder: $t('Search_this_table'),
        enabled: true,
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
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button @click="Shipments_pdf()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
           <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="shipments"
              :columns="columns"
              :file-name="'shipments'"
              :file-type="'xlsx'"
              :sheet-name="'shipments'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a
              @click="Edit_Shipment(props.row)"
              v-if="currentUserPermissions && currentUserPermissions.includes('shipment')"
              title="Edit"
              class="cursor-pointer"
              v-b-tooltip.hover
            >
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a
              title="Delete"
              class="cursor-pointer"
              v-b-tooltip.hover
              v-if="currentUserPermissions && currentUserPermissions.includes('shipment')"
              @click="Remove_Shipment(props.row.id)"
            >
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>

          <div v-else-if="props.column.field == 'status'">
            <span
              v-if="props.row.status == 'ordered'"
              class="badge badge-outline-warning"
            >{{$t('Ordered')}}</span>

            <span
              v-else-if="props.row.status == 'packed'"
              class="badge badge-outline-info"
            >{{$t('Packed')}}</span>

            <span
              v-else-if="props.row.status == 'shipped'"
              class="badge badge-outline-secondary"
            >{{$t('Shipped')}}</span>

             <span
              v-else-if="props.row.status == 'delivered'"
              class="badge badge-outline-success"
            >{{$t('Delivered')}}</span>

            <span v-else class="badge badge-outline-danger">{{$t('Cancelled')}}</span>
          </div>
        </template>
      </vue-good-table>
    </div>

    <!-- Modal Edit Shipment -->
    <validation-observer ref="shipment_ref">
      <b-modal hide-footer size="md" id="modal_shipment" :title="$t('Edit')">
        <b-form @submit.prevent="Submit_Shipment">
          <b-row>
            <!-- Status  -->
            <b-col md="12">
              <validation-provider name="Status" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Status') + ' ' + '*'">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="shipment.status"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Status')"
                    :options="
                                [
                                  {label: 'Ordered', value: 'ordered'},
                                  {label: 'Packed', value: 'packed'},
                                  {label: 'Shipped', value: 'shipped'},
                                  {label: 'Delivered', value: 'delivered'},
                                  {label: 'Cancelled', value: 'cancelled'},
                                ]"
                  ></v-select>
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <b-col md="12">
              <b-form-group :label="$t('delivered_to')">
                <b-form-input
                  label="delivered_to"
                  v-model="shipment.delivered_to"
                  :placeholder="$t('delivered_to')"
                ></b-form-input>
              </b-form-group>
            </b-col>

            <b-col md="12">
              <b-form-group :label="$t('Adress')">
                <textarea
                  v-model="shipment.shipping_address"
                  rows="4"
                  class="form-control"
                  :placeholder="$t('Enter_Address')"
                ></textarea>
              </b-form-group>
            </b-col>

            <b-col md="12">
              <b-form-group :label="$t('Please_provide_any_details')">
                <textarea
                  v-model="shipment.shipping_details"
                  rows="4"
                  class="form-control"
                  :placeholder="$t('Please_provide_any_details')"
                ></textarea>
              </b-form-group>
            </b-col>

            <b-col md="12" class="mt-3">
              <b-button
                variant="primary"
                type="submit"
                :disabled="SubmitProcessing"
              >{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
            </b-col>
          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Shipment"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing: false,
      ImportProcessing: false,
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      totalRows: "",
      search: "",
      limit: "10",
      shipments: [],
      shipment: {}
    };
  },

  computed: {
    ...mapGetters(["currentUserPermissions"]),
    columns() {
      return [
        {
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("shipment_ref"),
          field: "shipment_ref",
          tdClass: "text-left",
          thClass: "text-left"
        },

        {
          label: this.$t("sale_ref"),
          field: "sale_ref",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Customer"),
          field: "customer_name",
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
          label: this.$t("Status"),
          field: "status",
          tdClass: "text-left",
          thClass: "text-left"
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
    //------------- Submit Validation Edit shipment
    Submit_Shipment() {
      this.$refs.shipment_ref.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Update_Shipment();
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
        this.Get_shipments(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_shipments(1);
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
      this.Get_shipments(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_shipments(this.serverParams.page);
    },

    //------ Event Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //--------------------------------- Shipments PDF -------------------------------\\
    Shipments_pdf() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Date", dataKey: "date" },
        { title: "Shipment Ref", dataKey: "shipment_ref" },
        { title: "Sale Ref", dataKey: "sale_ref" },
        { title: "Customer", dataKey: "customer_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Status", dataKey: "status" }
      ];
      pdf.autoTable(columns, self.shipments);
      pdf.text("Shipments", 40, 25);
      pdf.save("Shipments.pdf");
    },

    //--------------------------------------- Get All Shipments -------------------------------\\
    Get_shipments(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "shipments?page=" +
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
          this.shipments = response.data.shipments;
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


    //------------------------------ Show Modal (Edit shipment) -------------------------------\\
    Edit_Shipment(shipment) {
      NProgress.start();
      NProgress.set(0.1);
      this.Get_shipments(this.serverParams.page);
      this.reset_Form();
      this.shipment = shipment;

      setTimeout(() => {
        NProgress.done();
        this.$bvModal.show("modal_shipment");
      }, 800);
     
    },

    //----------------------- Update_Shipment ---------------------------\\
    Update_Shipment() {
      var self = this;
      self.SubmitProcessing = true;
      axios
        .put("shipments/" + self.shipment.id, {
          sale_id: self.shipment.sale_id,
          shipping_address: self.shipment.shipping_address,
          delivered_to: self.shipment.delivered_to,
          shipping_details: self.shipment.shipping_details,
          status: self.shipment.status
        })
        .then(response => {
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
          Fire.$emit("event_update_shipment");
          self.SubmitProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.SubmitProcessing = false;
        });
    },

    //-------------------------------- Reset Form -------------------------------\\
    reset_Form() {
      this.shipment = {
        id: "",
        date: "",
        Ref: "",
        sale_id: "",
        attachment: "",
        delivered_to: "",
        shipping_address: "",
        status: "",
        shipping_details: ""
      };
    },

    //------------------------------- Remove shipment -------------------------------\\
    Remove_Shipment(id) {
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
            .delete("shipments/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );
              Fire.$emit("event_delete_shipment");
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    }
  }, // END METHODS

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_shipments(1);

    Fire.$on("event_update_shipment", () => {
      setTimeout(() => {
        this.Get_shipments(this.serverParams.page);
        this.$bvModal.hide("modal_shipment");
      }, 500);
    });

    Fire.$on("event_delete_shipment", () => {
      setTimeout(() => {
        this.Get_shipments(this.serverParams.page);
      }, 500);
    });
  }
};
</script>
