<template>
  <div class="main-content">
    <breadcumb :page="$t('Designation')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="designations"
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
            @click="New_Designation()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Designation(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover class="cursor-pointer" @click="Remove_Designation(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Designation">
      <b-modal hide-footer size="md" id="New_Designation" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Designation">
          <b-row>
           

               <!-- Company -->
                 <b-col md="12">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="designation.company_id"
                        class="required"
                        required
                        @input="Selected_Company"
                        :placeholder="$t('Choose_Company')"
                        :reduce="label => label.value"
                        :options="companies.map(companies => ({label: companies.name, value: companies.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Department -->
                <b-col md="12">
                  <validation-provider name="Department" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Department') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="designation.department_id"
                        class="required"
                        required
                        @input="Selected_Department"
                        :placeholder="$t('Department')"
                        :reduce="label => label.value"
                        :options="departments.map(departments => ({label: departments.department, value: departments.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

              <!-- Designation -->
              <b-col md="12">
                <validation-provider
                  name="Designation"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Designation') + ' ' + '*'">
                    <b-form-input
                      :placeholder="$t('Designation')"
                      :state="getValidationState(validationContext)"
                      aria-describedby="Designation-feedback"
                      label="Designation"
                      v-model="designation.designation"
                    ></b-form-input>
                    <b-form-invalid-feedback id="designation-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
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
    title: "Designation"
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
      designations: [], 
      departments: [],
      companies :[],
      designation: {
          designation: "",
          company_id: "",
          department_id: "",
      }, 
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Designation"),
          field: "designation",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Company"),
          field: "company_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Department"),
          field: "department_name",
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
        this.Get_Designation(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Designation(1);
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
      let field = "";
      if (params[0].field == "company_name") {
        field = "company_id";
      } else if (params[0].field == "department_name") {
        field = "department_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Designation(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Designation(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Designation
    Submit_Designation() {
      this.$refs.Create_Designation.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Designation();
          } else {
            this.Update_Designation();
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

    //------------------------------ Modal (create Designation) -------------------------------\\
    New_Designation() {
      this.reset_Form();
      this.editmode = false;
      this.Get_Data_Create();
      this.$bvModal.show("New_Designation");
    },

    //------------------------------ Modal (Update Designation) -------------------------------\\
    Edit_Designation(designation) {
      this.Get_Designation(this.serverParams.page);
      this.reset_Form();
      this.Get_Data_Edit(designation.id);
      this.Get_departments_by_company(designation.company_id);
      this.designation = designation;
      this.editmode = true;
      this.$bvModal.show("New_Designation");
    },

     
    Selected_Department(value) {
        if (value === null) {
            this.designation.department_id = "";
        }
    },


    Selected_Company(value) {
        if (value === null) {
            this.designation.company_id = "";
            this.designation.department_id = "";
        }
        this.departments = [];
        this.designation.department_id = "";
        this.Get_departments_by_company(value);
    },

      //---------------------- Get_departments_by_company ------------------------------\\
        Get_departments_by_company(value) {
            axios
                .get("/core/get_departments_by_company?id=" + value)
                .then(({ data }) => (this.departments = data));
        },

       //---------------------- Get_Data_Create  ------------------------------\\
          Get_Data_Create() {
            axios
                .get("/designations/create")
                .then(response => {
                    this.companies   = response.data.companies;
                })
                .catch(error => {
                    
                });
        },

        //---------------------- Get_Data_Edit  ------------------------------\\
        Get_Data_Edit(id) {
          axios
              .get("/designations/"+id+"/edit")
              .then(response => {
                  this.companies   = response.data.companies;
              })
              .catch(error => {
                  
              });
      },


    //--------------------------Get ALL designations ---------------------------\\

    Get_Designation(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "designations?page=" +
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
          this.designations = response.data.designations;

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

    //------------------------------- Create designation ------------------------\\
    Create_Designation() {
      
      this.SubmitProcessing = true;
      axios
        .post("designations", {
          designation: this.designation.designation,
          company_id: this.designation.company_id,
          department: this.designation.department_id,
          
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Designation");
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

    //------------------------------- Update designation ------------------------\\
    Update_Designation() {
      this.SubmitProcessing = true;
      axios
        .put("designations/" + this.designation.id, {
          designation: this.designation.designation,
          company_id: this.designation.company_id,
          department: this.designation.department_id,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Designation");

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
     this.designation = {
          id: "",
          designation: "",
          company_id:"",
          department_id: "",
      };
    },

    //------------------------------- Delete designation ------------------------\\
    Remove_Designation(id) {
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
            .delete("designations/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Designation");
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

    //---- Delete department by selection

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
            .post("designations/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Designation");
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
    this.Get_Designation(1);

    Fire.$on("Event_Designation", () => {
      setTimeout(() => {
        this.Get_Designation(this.serverParams.page);
        this.$bvModal.hide("New_Designation");
      }, 500);
    });

    Fire.$on("Delete_Designation", () => {
      setTimeout(() => {
        this.Get_Designation(this.serverParams.page);
      }, 500);
    });
  }
};
</script>