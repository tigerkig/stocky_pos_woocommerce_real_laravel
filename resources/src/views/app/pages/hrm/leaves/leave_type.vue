<template>
  <div class="main-content">
    <breadcumb :page="$t('Leave_Type')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="leave_types"
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
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button
            @click="New_Type()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Type(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" class="cursor-pointer" v-b-tooltip.hover @click="Remove_Type(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Type">
      <b-modal hide-footer size="md" id="New_Type" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Type">
          <b-row>
           
              <!-- Leave Type -->
              <b-col md="12">
                <validation-provider
                  name="title"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('title') + ' ' + '*'">
                    <b-form-input
                      :placeholder="$t('Enter_title')"
                      :state="getValidationState(validationContext)"
                      aria-describedby="title-feedback"
                      label="title"
                      v-model="leave_type.title"
                    ></b-form-input>
                    <b-form-invalid-feedback id="title-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
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
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Leave Type"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing:false,
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
      editmode: false,
     leave_types: [], 
     leave_type: {
        title: "",
     },
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Leave_Type"),
          field: "title",
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
    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_leaves_type(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_leaves_type(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //---- Event Sort Change

    onSortChange(params) {
     
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_leaves_type(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_leaves_type(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit leave type
    Submit_Type() {
      this.$refs.Create_Type.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Type();
          } else {
            this.Update_Type();
          }
        }
      });
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------------------------------ Modal (create leave type) -------------------------------\\
    New_Type() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Type");
    },

    //------------------------------ Modal (Update leave type) -------------------------------\\
    Edit_Type(leave_type) {
      this.Get_leaves_type(this.serverParams.page);
      this.reset_Form();
      this.leave_type = leave_type;
      this.editmode = true;
      this.$bvModal.show("New_Type");
    },



    //--------------------------Get ALL leave_type ---------------------------\\

    Get_leaves_type(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "leave_type?page=" +
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
          this.totalRows = response.data.totalRows;
          this.leave_types = response.data.leave_types;

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

    //------------------------------- Create leave_type ------------------------\\
    Create_Type() {
      
      this.SubmitProcessing = true;
      axios
        .post("leave_type", {
          title: this.leave_type.title,
          
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Type");
          this.makeToast(
            "success",
            this.$t("Created_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Update leave_type ------------------------\\
    Update_Type() {
      this.SubmitProcessing = true;
      axios
        .put("leave_type/" + this.leave_type.id, {
          title: this.leave_type.title,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Type");

          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- reset Form ------------------------\\
    reset_Form() {
     this.leave_type = {
          id: "",
          title: "",
      };
    },

    //------------------------------- Delete leave_type ------------------------\\
    Remove_Type(id) {
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
            .delete("leave_type/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Type");
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
    },

    //---- Delete leave type by selection

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
            .post("leave_type/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Type");
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
    }
  },

  //----------------------------- Created function-------------------\\

  created: function() {
    this.Get_leaves_type(1);

    Fire.$on("Event_Type", () => {
      setTimeout(() => {
        this.Get_leaves_type(this.serverParams.page);
        this.$bvModal.hide("New_Type");
      }, 500);
    });

    Fire.$on("Delete_Type", () => {
      setTimeout(() => {
        this.Get_leaves_type(this.serverParams.page);
      }, 500);
    });
  }
};
</script>