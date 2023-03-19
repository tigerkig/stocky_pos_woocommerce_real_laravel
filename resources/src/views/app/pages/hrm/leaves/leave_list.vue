<template>
  <div class="main-content">
    <breadcumb :page="$t('Leave_request')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="leaves"
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
            @click="New_Leave()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Leave(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" class="cursor-pointer" v-b-tooltip.hover @click="Remove_Leave(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Leave">
      <b-modal hide-footer size="lg" id="New_Modal_Leave" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Leave">
          <b-row>
               <!-- Company -->
                 <b-col md="6">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="leave.company_id"
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
                <b-col md="6">
                  <validation-provider name="Department" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Department') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="leave.department_id"
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

                  <!-- Employee -->
                <b-col md="6">
                  <validation-provider name="Employee" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Employee') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="leave.employee_id"
                        class="required"
                        required
                        @input="Selected_Employee"
                        :placeholder="$t('Choose_Employee')"
                        :reduce="label => label.value"
                        :options="employees.map(employees => ({label: employees.username, value: employees.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                   <!-- leave_type -->
                <b-col md="6">
                  <validation-provider name="leave_type" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Leave_Type') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="leave.leave_type_id"
                        class="required"
                        required
                        @input="Selected_Leave_Type"
                        :placeholder="$t('Choose_leave_type')"
                        :reduce="label => label.value"
                        :options="leave_types.map(leave_types => ({label: leave_types.title, value: leave_types.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

              <!-- start date -->
              <b-col md="6">
                <validation-provider
                  name="start_date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                    <b-form-group :label="$t('start_date') + ' ' + '*'">
                        <Datepicker id="start_date" name="start_date" :placeholder="$t('Enter_Start_date')" v-model="leave.start_date" 
                            input-class="form-control back_important" format="yyyy-MM-dd"  @closed="leave.start_date=formatDate(leave.start_date)">
                        </Datepicker>
                        <b-form-invalid-feedback id="start_date-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                     </b-form-group>
                </validation-provider>
              </b-col>

               <!-- end date -->
              <b-col md="6">
                <validation-provider
                  name="Finish_Date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                    <b-form-group :label="$t('Finish_Date')">
                        <Datepicker id="end_date" name="end_date" :placeholder="$t('Enter_Finish_date')" v-model="leave.end_date" 
                            input-class="form-control back_important" format="yyyy-MM-dd"  @closed="leave.end_date=formatDate(leave.end_date)">
                        </Datepicker>
                        <b-form-invalid-feedback id="end_date-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                     </b-form-group>
                </validation-provider>
              </b-col>

                <!-- Status -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider name="Status" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Status') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="leave.status"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_status')"
                        :options="
                          [
                            {label: 'Approved', value: 'approved'},
                            {label: 'Pending', value: 'pending'},
                            {label: 'Rejected', value: 'rejected'},
                          ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

            <!-- -Attachment -->
            <b-col md="6">
              <validation-provider name="Attachment" ref="Attachment" rules="mimes:image/*|size:2048">
                <b-form-group slot-scope="{validate, valid, errors }" :label="$t('Attachment')">
                  <input
                    :state="errors[0] ? false : (valid ? true : null)"
                    :class="{'is-invalid': !!errors.length}"
                    @change="changeAttachement"
                    label="Choose Attachment"
                    type="file"
                  >
                  <b-form-invalid-feedback id="Attachment-feedback">{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>


              <!-- Leave_Reason -->
            <b-col md="12">
                <b-form-group :label="$t('Leave_Reason')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Reason_Leave')"
                    label="Leave_Reason"
                    v-model="leave.reason"
                  ></b-form-textarea>
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
import Datepicker from 'vuejs-datepicker';

export default {
  metaInfo: {
    title: "Leave"
  },
   components: {
    Datepicker
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
      data: new FormData(),
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      editmode: false,
      employees:[],
      companies:[],
      departments:[],
      leave_types:[],
      leaves: [], 
      leave: {
          company_id: "",
          department_id: "",
          employee_id: "",
          leave_type_id :"",
          start_date:"",
          end_date:"",
          days:"",
          reason:"",
          attachment:"",
          half_day:"",
          status:"",
      }, 
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Employee"),
          field: "employee_name",
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
          label: this.$t("Leave_Type"),
          field: "leave_type_title",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("start_date"),
          field: "start_date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Finish_Date"),
          field: "end_date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Days"),
          field: "days",
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
    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Leaves(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Leaves(1);
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
      } else if (params[0].field == "employee_name") {
        field = "employee_id";
      }else if (params[0].field == "leave_type_title") {
        field = "leave_type_id";
      }else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Leaves(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Leaves(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    formatDate(d){
        var m1 = d.getMonth()+1;
        var m2 = m1 < 10 ? '0' + m1 : m1;
        var d1 = d.getDate();
        var d2 = d1 < 10 ? '0' + d1 : d1;
        return [d.getFullYear(), m2, d2].join('-');
    },

       //------------------------------ Event Upload attachment -------------------------------\\
    async changeAttachement(e) {
      const { valid } = await this.$refs.Attachment.validate(e);

      if (valid) {
        this.leave.attachment = e.target.files[0];
      } else {
        this.leave.attachment = "";
      }
    },

    //------------- Submit Validation Create & Edit Leave
    Submit_Leave() {
      this.$refs.Create_Leave.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Leave();
          } else {
            this.Update_Leave();
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

    //------------------------------ Modal (create Leave) -------------------------------\\
    New_Leave() {
      this.reset_Form();
      this.editmode = false;
      this.Get_Data_Create();
      
    },

   //------------------------------ Show Modal (Update Leave) -------------------------------\\
    Edit_Leave(leave) {
        this.editmode = true;
        this.reset_Form();
        this.Get_Data_Edit(leave.id);
    },

     
     Selected_Employee(value) {
                if (value === null) {
                    this.leave.employee_id = "";
                }
            },

            Selected_Leave_Type(value) {
                if (value === null) {
                    this.leave.leave_type_id = "";
                }
            },

            Selected_Status(value) {
                if (value === null) {
                    this.leave.status = "";
                }
            },

            Selected_Company(value) {
                if (value === null) {
                    this.leave.company_id = "";
                }
                this.employees = [];
                this.departments = [];
                this.leave.employee_id = "";
                this.leave.department_id = "";
                this.Get_departments_by_company(value);
            },

            Selected_Department(value) {
                if (value === null) {
                    this.leave.department_id = "";
                    this.leave.employee_id = "";
                }
                this.employee_id = [];
                this.leave.employee_id = "";
                this.Get_employees_by_department(value);
            },

       
              //---------------------- Get_departments_by_company ------------------------------\\
            Get_departments_by_company(value) {
            axios
                .get("/core/get_departments_by_company?id=" + value)
                .then(({ data }) => (this.departments = data));
            },

            //---------------------- Get_employees_by_department ------------------------------\\
            
            Get_employees_by_department(value) {
                axios
                .get("/get_employees_by_department?id=" + value)
                .then(({ data }) => (this.employees = data));
            },


            
             //---------------------- Get_Data_Create  ------------------------------\\
             Get_Data_Create() {
                axios
                    .get("/leave/create")
                    .then(response => {
                        this.companies   = response.data.companies;
                        this.leave_types = response.data.leave_types;
                        this.$bvModal.show("New_Modal_Leave");
                    })
                    .catch(error => {
                       
                    });
            },

              //---------------------- Get_Data_Edit  ------------------------------\\
              Get_Data_Edit(id) {
                axios
                    .get(`leave/${id}/edit`)
                    .then(response => {
                        this.leave    = response.data.leave;
                        this.companies   = response.data.companies;
                        this.leave_types = response.data.leave_types;
                        this.Get_departments_by_company(this.leave.company_id);
                        this.Get_employees_by_department(this.leave.department_id);
                        this.leave.attachment = "";
                        this.$bvModal.show("New_Modal_Leave");
                    })
                    .catch(error => {
                       
                    });
            },


    //--------------------------Get ALL Leaves ---------------------------\\

    Get_Leaves(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "leave?page=" +
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
          this.leaves = response.data.leaves;

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

    //------------------------------- Create leave ------------------------\\
    Create_Leave() {
      
      var self = this;
        self.SubmitProcessing = true;
        self.data.append("company_id", self.leave.company_id);
        self.data.append("department_id", self.leave.department_id);
        self.data.append("employee_id", self.leave.employee_id);
        self.data.append("leave_type_id", self.leave.leave_type_id);
        self.data.append("start_date", self.leave.start_date);
        self.data.append("end_date", self.leave.end_date);
        self.data.append("reason", self.leave.reason);
        self.data.append("attachment", self.leave.attachment);
        self.data.append("half_day", self.leave.half_day?1:0);
        self.data.append("status", self.leave.status);

      axios.post("/leave", self.data)
        .then(response => {
          if(response.data.isvalid == false){
              self.SubmitProcessing = false;
              self.errors = {};
              this.makeToast("danger", this.$t("remaining_leaves_are_insufficient"), this.$t("Failed"));
          }
          else{
            this.SubmitProcessing = false;
            Fire.$emit("Event_Leave");
            this.makeToast(
              "success",
              this.$t("Created_in_successfully"),
              this.$t("Success")
            );
          }
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Update Leave ------------------------\\
    Update_Leave() {

      var self = this;
      self.SubmitProcessing = true;
      self.data.append("company_id", self.leave.company_id);
      self.data.append("department_id", self.leave.department_id);
      self.data.append("employee_id", self.leave.employee_id);
      self.data.append("leave_type_id", self.leave.leave_type_id);
      self.data.append("start_date", self.leave.start_date);
      self.data.append("end_date", self.leave.end_date);
      self.data.append("reason", self.leave.reason);
      self.data.append("attachment", self.leave.attachment);
      self.data.append("half_day", self.leave.half_day?1:0);
      self.data.append("status", self.leave.status);
      self.data.append("_method", "put");

       axios.post("/leave/" + self.leave.id, self.data)
        .then(response => {
           if(response.data.isvalid == false){
              self.SubmitProcessing = false;
              self.errors = {};
              this.makeToast("danger", this.$t("remaining_leaves_are_insufficient"), this.$t("Failed"));
          }
          else{
            this.SubmitProcessing = false;
            Fire.$emit("Event_Leave");

            this.makeToast(
              "success",
              this.$t("Updated_in_successfully"),
              this.$t("Success")
            );
          }
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- reset Form ------------------------\\
    reset_Form() {
     this.leave = {
        id: "",
        company_id: "",
        department_id: "",
        employee_id: "",
        leave_type_id :"",
        start_date:"",
        end_date:"",
        days:"",
        reason:"",
        attachment:"",
        half_day:"",
        status:"",
      };
    },

    //------------------------------- Delete Leave ------------------------\\
    Remove_Leave(id) {
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
            .delete("leave/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Leave");
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
            .post("leave/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Leave");
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
    this.Get_Leaves(1);

    Fire.$on("Event_Leave", () => {
      setTimeout(() => {
        this.Get_Leaves(this.serverParams.page);
        this.$bvModal.hide("New_Modal_Leave");
      }, 500);
    });

    Fire.$on("Delete_Leave", () => {
      setTimeout(() => {
        this.Get_Leaves(this.serverParams.page);
      }, 500);
    });
  }
};
</script>