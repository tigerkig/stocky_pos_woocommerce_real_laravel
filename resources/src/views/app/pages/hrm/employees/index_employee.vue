<template>
  <div class="main-content">
    <breadcumb :page="$t('Employees')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="employees"
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
        styleClass="tableOne table-hover vgt-table"
      >
        <div slot="selected-row-actions">
          <button 
            v-if="currentUserPermissions && currentUserPermissions.includes('delete_employee')"
            class="btn btn-danger btn-sm" 
            @click="delete_by_selected()">{{$t('Del')}}
          </button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info ripple m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="Employee_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="employees"
              :columns="columns"
              :file-name="'employees'"
              :file-type="'xlsx'"
              :sheet-name="'employees'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
          <router-link
            class="btn-sm btn btn-primary ripple btn-icon m-1"
            to="/app/hrm/employees/store"
            v-if="currentUserPermissions && currentUserPermissions.includes('add_employee')"
          >
            <span class="ul-btn__icon">
              <i class="i-Add"></i>
            </span>
            <span class="ul-btn__text ml-1">{{$t('Add')}}</span>
          </router-link>
        </div>

        <template slot="table-row" slot-scope="props">
         
          <span v-if="props.column.field == 'actions'">

            <router-link 
            v-if="currentUserPermissions && currentUserPermissions.includes('view_employee')"
            title="detail" :to="'/app/hrm/employees/detail/'+props.row.id">
              <i class="i-Eye text-25 text-info"></i>
            </router-link>
            <router-link
              v-if="currentUserPermissions && currentUserPermissions.includes('edit_employee')"
              title="Edit"
              v-b-tooltip.hover
              :to="'/app/hrm/employees/edit/'+props.row.id"
            >
              <i class="i-Edit text-25 text-success"></i>
            </router-link>
            <a
              title="Delete"
              v-b-tooltip.hover
              v-if="currentUserPermissions && currentUserPermissions.includes('delete_employee')"
              @click="Remove_Employee(props.row.id)"
            >
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </div>

    <!-- Multiple Filters -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>

          <!-- Username  -->
          <b-col md="12">
            <b-form-group :label="$t('username')">
              <b-form-input label="Username" :placeholder="$t('username')" v-model="Filter_username"></b-form-input>
            </b-form-group>
          </b-col>

            <!-- Employment Type  -->
          <b-col md="12">
            <b-form-group :label="$t('Employment_type')">
              <v-select
                v-model="Filter_employment_type"
                :reduce="label => label.value"
                :placeholder="$t('Employment_type')"
                :options="
                      [
                        {label: 'Full-time', value: 'full_time'},
                        {label: 'Part-time', value: 'part_time'},
                        {label: 'Self-employed', value: 'self_employed'},

                        {label: 'Freelance', value: 'freelance'},
                        {label: 'Contract', value: 'contract'},
                        {label: 'Internship', value: 'internship'},
                        {label: 'Apprenticeship', value: 'apprenticeship'},
                        {label: 'Seasonal', value: 'seasonal'},
                      ]"
              ></v-select>
            </b-form-group>
          </b-col>

        
          <!-- Company  -->
          <b-col md="12">
            <b-form-group :label="$t('Company')">
              <v-select
                :reduce="label => label.value"
                :placeholder="$t('Company')"
                v-model="Filter_company"
                :options="companies.map(companies => ({label: companies.name, value: companies.id}))"
              />
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button
              @click="Get_Employees(serverParams.page)"
              variant="primary m-1"
              size="sm"
              block
            >
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger m-1" size="sm" block>
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
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Employee"
  },
  data() {
    return {
      isLoading: true,
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
      Filter_username: "",
      Filter_employment_type: "",
      Filter_company: "",
      employees: [],
      companies: [],
    };
  },

  computed: {
    ...mapGetters(["currentUserPermissions", "currentUser"]),
    columns() {
      return [
        {
          label: this.$t("FirstName"),
          field: "firstname",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("LastName"),
          field: "lastname",
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
          label: this.$t("Designation"),
          field: "designation_name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Office_Shift"),
          field: "office_shift_name",
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
    //---------------------- Employee PDF -------------------------------\\
    Employee_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "First Name", dataKey: "firstname" },
        { title: "Last Name", dataKey: "lastname" },
        { title: "Phone", dataKey: "phone" },
        { title: "Company", dataKey: "company_name" },
        { title: "Department", dataKey: "department_name" },
        { title: "Designation", dataKey: "designation_name" },
        { title: "Office shift", dataKey: "office_shift_name" }
      ];
      pdf.autoTable(columns, self.employees);
      pdf.text("Employee List", 40, 25);
      pdf.save("Employee_List.pdf");
    },


    //------ update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Employees(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Employees(1);
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
      let field = "";
      if (params[0].field == "company_name") {
        field = "company_id";
      } else if (params[0].field == "department_name") {
        field = "department_id";
      }else if (params[0].field == "designation_name") {
        field = "designation_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: field
        }
      });
      this.Get_Employees(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Employees(this.serverParams.page);
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_username = "";
      this.Filter_employment_type = "";
      this.Filter_company = "";
      this.Get_Employees(this.serverParams.page);
    },

    // Simply replaces null values with strings=''
    setToStrings() {
      if (this.Filter_employment_type === null) {
        this.Filter_employment_type = "";
      } else if (this.Filter_company === null) {
        this.Filter_company = "";
      }
    },

    //------------------------------------------------ Get All Expense -------------------------------\\
    Get_Employees(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();
      axios
        .get(
          "employees?page=" +
            page +
            "&username=" +
            this.Filter_username +
            "&employment_type=" +
            this.Filter_employment_type +
            "&company_id=" +
            this.Filter_company +
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
          this.employees = response.data.employees;
          this.companies = response.data.companies;
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

    //------------------------------- Remove Employee -------------------------\\

    Remove_Employee(id) {
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
            .delete("employees/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );
              Fire.$emit("Delete_Employee");
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
    },

    //---- Delete Expense by selection

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
            .post("employees/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Employee");
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

  //----------------------------- Created function-------------------
  created: function() {
    this.Get_Employees(1);

    Fire.$on("Delete_Employee", () => {
      setTimeout(() => {
        // Complete the animation of theprogress bar.
        NProgress.done();
        this.Get_Employees(this.serverParams.page);
      }, 500);
    });
  }
};
</script>