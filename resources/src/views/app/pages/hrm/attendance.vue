<template>
  <div class="main-content">
    <breadcumb :page="$t('Attendances')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="attendances"
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
            @click="New_attendance()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Attendance(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover class="cursor-pointer" @click="Remove_Attendance(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Attendance">
      <b-modal hide-footer size="md" id="Modal_attendance" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Attendance">
          <b-row>
           <!-- Company -->
                 <b-col md="12">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="attendance.company_id"
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

                  <!-- Employee -->
                 <b-col md="12">
                  <validation-provider name="Employee" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Employee') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="attendance.employee_id"
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

                  <!-- Date  -->
                 <b-col md="12">
                   <validation-provider name="Date" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('date') + ' ' + '*'">
                      <Datepicker  id="date" name="date" :placeholder="$t('Enter_Attendance_date')" v-model="attendance.date" 
                          input-class="form-control back_important" format="yyyy-MM-dd"  @closed="attendance.date=formatDate(attendance.date)">
                      </Datepicker>
                     <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!-- Time_In  -->
               <b-col md="12">
                 <validation-provider name="Time_In" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Time_In') + ' ' + '*'">
                    <vue-clock-picker v-model="attendance.clock_in" :placeholder="$t('Time_In')" 
                       name="clock_in" id="clock_in"></vue-clock-picker>
                     <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                   <!-- Time_In  -->
               <b-col md="12">
                 <validation-provider name="Time_Out" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Time_Out') + ' ' + '*'">
                    <vue-clock-picker v-model="attendance.clock_out" :placeholder="$t('Time_Out')" 
                       name="clock_out" id="clock_out"></vue-clock-picker>
                     <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
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
import VueClockPicker from '@pencilpix/vue2-clock-picker';

import NProgress from "nprogress";
import Datepicker from 'vuejs-datepicker';

export default {
  metaInfo: {
    title: "Attendance"
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
      employees:[],
      companies:[],
      attendances: [], 
      attendance: {
          company_id: "",
          employee_id: "",
          date:"",
          clock_in:"",
          clock_out:"",
      }, 

      office_shift: {
          name: "",
          company_id:"",
          monday_in:"",
          monday_out:"",
          tuesday_in:"",
          tuesday_out:"",
          wednesday_in:"",
          wednesday_out:"",
          thursday_in:"",
          thursday_out:"",
          friday_in:"",
          friday_out:"",
          saturday_in:"",
          saturday_out:"",
          sunday_in:"",
          sunday_out:"",
      }, 
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Employee"),
          field: "employee_username",
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
          label: this.$t("date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Time_In"),
          field: "clock_in",
          tdClass: "text-left",
          thClass: "text-left"
        },
         {
          label: this.$t("Time_Out"),
          field: "clock_out",
          tdClass: "text-left",
          thClass: "text-left"
        },
         {
          label: this.$t("Work_Duration"),
          field: "total_work",
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
 components: {
    VueClockPicker,
    Datepicker,
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
        this.Get_Attendances(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Attendances(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

     formatDate(d){
      var m1 = d.getMonth()+1;
      var m2 = m1 < 10 ? '0' + m1 : m1;
      var d1 = d.getDate();
      var d2 = d1 < 10 ? '0' + d1 : d1;
      return [d.getFullYear(), m2, d2].join('-');
    },

    //---- Event Sort Change

    onSortChange(params) {
      let field = "";
      if (params[0].field == "company_name") {
        field = "company_id";
      }else if (params[0].field == "employee_username") {
        field = "employee_id";
      }else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Attendances(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Attendances(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit attendance
    Submit_Attendance() {
      this.$refs.Create_Attendance.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Attendance();
          } else {
            this.Update_Attendance();
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


     //------------------------------ Show Modal (Create attendance) -------------------------------\\
      New_attendance() {
        this.reset_Form();
        this.editmode = false;
        this.Get_all_companies();
        this.$bvModal.show("Modal_attendance");
    },

    //------------------------------ Show Modal (Update attendance) -------------------------------\\
    Edit_Attendance(attendance) {
        this.editmode = true;
        this.reset_Form();
        this.Get_all_companies();
        this.Get_employees_by_company(attendance.company_id);
        this.attendance = attendance;
        this.$bvModal.show("Modal_attendance");
    },

    Selected_Company(value) {
        if (value === null) {
            this.attendance.company_id = "";
        }
        this.employees = [];
        this.attendance.employee_id = "";
        this.Get_employees_by_company(value);
    },

    Selected_Employee(value) {
        if (value === null) {
            this.attendance.employee_id = "";
        }
    },

      //---------------------- Get_employees_by_company ------------------------------\\
      
      Get_employees_by_company(value) {
          axios
          .get("/core/get_employees_by_company?id=" + value)
          .then(({ data }) => (this.employees = data));
      },

       //---------------------- Get all companies  ------------------------------\\
        Get_all_companies() {
          axios
              .get("/attendances/create")
              .then(response => {
                  this.companies   = response.data.companies;
              })
              .catch(error => {
                  
              });
      },


    //--------------------------Get ALL attendances ---------------------------\\

    Get_Attendances(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "attendances?page=" +
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
          this.attendances = response.data.attendances;

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

    //------------------------------- Create attendance ------------------------\\
    Create_Attendance() {
      
      this.SubmitProcessing = true;
      axios
        .post("attendances", {
          company_id: this.attendance.company_id,
          employee_id: this.attendance.employee_id,
          date: this.attendance.date,
          clock_in: this.attendance.clock_in,
          clock_out: this.attendance.clock_out,
          
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Attendance");
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

    //------------------------------- Update attendance ------------------------\\
    Update_Attendance() {
      this.SubmitProcessing = true;
      axios
        .put("attendances/" + this.attendance.id, {
          company_id: this.attendance.company_id,
          employee_id: this.attendance.employee_id,
          date: this.attendance.date,
          clock_in: this.attendance.clock_in,
          clock_out: this.attendance.clock_out,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Attendance");

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
      this.attendance = {
          company_id: "",
          employee_id: "",
          date:"",
          clock_in:"",
          clock_out:"",
      };
    },

    //------------------------------- Delete attendance ------------------------\\
    Remove_Attendance(id) {
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
            .delete("attendances/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Attendance");
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

    //---- Delete attendance by selection

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
            .post("attendances/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Attendance");
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
    this.Get_Attendances(1);

    Fire.$on("Event_Attendance", () => {
      setTimeout(() => {
        this.Get_Attendances(this.serverParams.page);
        this.$bvModal.hide("Modal_attendance");
      }, 500);
    });

    Fire.$on("Delete_Attendance", () => {
      setTimeout(() => {
        this.Get_Attendances(this.serverParams.page);
      }, 500);
    });
  }
};
</script>