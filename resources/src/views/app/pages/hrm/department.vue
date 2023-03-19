<template>
  <div class="main-content">
    <breadcumb :page="$t('department')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="departments"
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
            @click="New_Department()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Department(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover class="cursor-pointer" @click="Remove_Department(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Department">
      <b-modal hide-footer size="md" id="New_Department" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Department">
          <b-row>
            <!-- Name -->
            <b-col md="12">
              <validation-provider
                name="department"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('department') + ' ' + '*'">
                  <b-form-input
                    :placeholder="$t('Enter_Department_Name')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="department-feedback"
                    label="department"
                    v-model="department.department"
                  ></b-form-input>
                  <b-form-invalid-feedback id="department-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

             <!-- Company -->
                 <b-col md="12">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="department.company_id"
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

                  <!-- Department_Head -->
                 <b-col md="12">
                    <b-form-group :label="$t('Department_Head')">
                      <v-select
                        v-model="department.department_head"
                        @input="Selected_Employee"
                        :placeholder="$t('Choose_Department_Head')"
                        :reduce="label => label.value"
                        :options="employees.map(employees => ({label: employees.username, value: employees.id}))"
                      />
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
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Department"
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
      departments: {}, 
      employees:[],
      companies:[],
      department: {
          department: "",
          company_id: "",
          department_head:"",
      },
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("department"),
          field: "department",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Department_Head"),
          field: "employee_head",
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
        this.Get_Department(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Department(1);
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
      }else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Department(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Department(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit department
    Submit_Department() {
      this.$refs.Create_Department.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Department();
          } else {
            this.Update_Department();
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

    //------------------------------ Modal (create department) -------------------------------\\
    New_Department() {
      this.reset_Form();
      this.editmode = false;
      this.Get_Data_Create();
      this.$bvModal.show("New_Department");
    },

    //------------------------------ Modal (Update department) -------------------------------\\
    Edit_Department(department) {
      this.Get_Department(this.serverParams.page);
      this.reset_Form();
      this.Get_Data_Edit(department.id);
      this.Get_employees_by_company(department.company_id);
      this.department = department;
      this.editmode = true;
      this.$bvModal.show("New_Department");
    },

      Selected_Company(value) {
          if (value === null) {
              this.department.company_id = "";
          }
          this.employees = [];
          this.department.department_head = "";
          this.Get_employees_by_company(value);
      },

      Selected_Employee(value) {
          if (value === null) {
              this.department.department_head = "";
          }
      },

      //---------------------- Get_employees_by_company ------------------------------\\
      
      Get_employees_by_company(value) {
          axios
          .get("/core/get_employees_by_company?id=" + value)
          .then(({ data }) => (this.employees = data));
      },

       //---------------------- Get_Data_Create  ------------------------------\\
          Get_Data_Create() {
            axios
                .get("/departments/create")
                .then(response => {
                    this.companies   = response.data.companies;
                })
                .catch(error => {
                    
                });
        },

        //---------------------- Get_Data_Edit  ------------------------------\\
        Get_Data_Edit(id) {
          axios
              .get("/departments/"+id+"/edit")
              .then(response => {
                  this.companies   = response.data.companies;
              })
              .catch(error => {
                  
              });
      },


    //--------------------------Get ALL department ---------------------------\\

    Get_Department(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "departments?page=" +
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
          this.departments = response.data.departments;

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

    //------------------------------- Create department ------------------------\\
    Create_Department() {
      
      this.SubmitProcessing = true;
      axios
        .post("departments", {
          department: this.department.department,
          company_id: this.department.company_id,
          department_head: this.department.department_head,
          
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Department");
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

    //------------------------------- Update department ------------------------\\
    Update_Department() {
      this.SubmitProcessing = true;
      axios
        .put("departments/" + this.department.id, {
          department: this.department.department,
          company_id: this.department.company_id,
          department_head: this.department.department_head,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Department");

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
     this.department = {
          id: "",
          department: "",
          company_id: "",
          department_head:"",
      };
    },

    //------------------------------- Delete department ------------------------\\
    Remove_Department(id) {
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
            .delete("departments/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Department");
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
            .post("departments/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Department");
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
    this.Get_Department(1);

    Fire.$on("Event_Department", () => {
      setTimeout(() => {
        this.Get_Department(this.serverParams.page);
        this.$bvModal.hide("New_Department");
      }, 500);
    });

    Fire.$on("Delete_Department", () => {
      setTimeout(() => {
        this.Get_Department(this.serverParams.page);
      }, 500);
    });
  }
};
</script>