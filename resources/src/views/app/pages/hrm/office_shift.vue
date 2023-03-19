<template>
  <div class="main-content">
    <breadcumb :page="$t('Office_Shift')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="office_shifts"
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
            @click="New_Office_Shift()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Office_Shift(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" class="cursor-pointer" v-b-tooltip.hover @click="Remove_Office_Shift(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Office_Shift">
      <b-modal hide-footer size="lg" id="New_Office_Shift" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Office_Shift">
          <b-row>
            <!-- Name -->
            <b-col md="6">
              <validation-provider
                name="Name"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Name') + ' ' + '*'">
                  <b-form-input
                    :placeholder="$t('Enter_Shift_name')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Name-feedback"
                    label="Name"
                    v-model="office_shift.name"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

             <!-- Company -->
              <b-col md="6">
                <validation-provider name="Company" :rules="{ required: true}">
                  <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                    <v-select
                      :class="{'is-invalid': !!errors.length}"
                      :state="errors[0] ? false : (valid ? true : null)"
                      v-model="office_shift.company_id"
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

              <!-- Monday_In  -->
               <b-col md="3">
                  <b-form-group :label="$t('Monday_In')">                       
                    <vue-clock-picker v-model="office_shift.monday_in" :placeholder="$t('Monday_In')" 
                       name="monday_in" id="monday_in"></vue-clock-picker>
                    </b-form-group>
                </b-col>

                <!-- Monday_Out  -->
               <b-col md="3">
                    <b-form-group :label="$t('Monday_Out')">
                      <vue-clock-picker v-model="office_shift.monday_out" :placeholder="$t('Monday_Out')"></vue-clock-picker>
                    </b-form-group>
                </b-col>

                 <!-- Tuesday_In  -->
               <b-col md="3">
                  <b-form-group :label="$t('Tuesday_In')">
                      <vue-clock-picker v-model="office_shift.tuesday_in" :placeholder="$t('Tuesday_In')"></vue-clock-picker>
                  </b-form-group>
                </b-col>

              <!-- tuesday_out  -->
               <b-col md="3">
                    <b-form-group :label="$t('tuesday_out')">
                      <vue-clock-picker v-model="office_shift.tuesday_out" :placeholder="$t('tuesday_out')"></vue-clock-picker>
                    </b-form-group>
                </b-col>

              <!-- wednesday_in  -->
               <b-col md="3">
                  <b-form-group :label="$t('wednesday_in')">
                    <vue-clock-picker v-model="office_shift.wednesday_in" :placeholder="$t('wednesday_in')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                <!-- wednesday_out  -->
               <b-col md="3">
                  <b-form-group :label="$t('wednesday_out')">
                    <vue-clock-picker v-model="office_shift.wednesday_out" :placeholder="$t('wednesday_out')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                <!-- thursday_in  -->
               <b-col md="3">
                  <b-form-group :label="$t('thursday_in')">
                    <vue-clock-picker v-model="office_shift.thursday_in" :placeholder="$t('thursday_in')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

               <!-- thursday_out  -->
               <b-col md="3">
                  <b-form-group :label="$t('thursday_out')">
                     <vue-clock-picker v-model="office_shift.thursday_out" :placeholder="$t('thursday_out')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                <!-- friday_in  -->
               <b-col md="3">
                  <b-form-group :label="$t('friday_in')">
                   <vue-clock-picker v-model="office_shift.friday_in" :placeholder="$t('friday_in')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                 <!-- friday_out  -->
               <b-col md="3">
                  <b-form-group :label="$t('friday_out')">
                   <vue-clock-picker v-model="office_shift.friday_out" :placeholder="$t('friday_out')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                 <!-- saturday_in  -->
               <b-col md="3">
                  <b-form-group :label="$t('saturday_in')">
                     <vue-clock-picker v-model="office_shift.saturday_in" :placeholder="$t('saturday_in')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                 <!-- saturday_out  -->
               <b-col md="3">
                  <b-form-group :label="$t('saturday_out')">
                   <vue-clock-picker v-model="office_shift.saturday_out" :placeholder="$t('saturday_out')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                <!-- sunday_in  -->
               <b-col md="3">
                  <b-form-group :label="$t('sunday_in')">
                     <vue-clock-picker v-model="office_shift.sunday_in" :placeholder="$t('sunday_in')"></vue-clock-picker>
                  </b-form-group>
              </b-col>

                <!-- sunday_out  -->
               <b-col md="3">
                  <b-form-group :label="$t('sunday_out')">
                    <vue-clock-picker v-model="office_shift.sunday_out" :placeholder="$t('sunday_out')"></vue-clock-picker>
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
import VueClockPicker from '@pencilpix/vue2-clock-picker';

import NProgress from "nprogress";
export default {
  metaInfo: {
    title: "Office Shift"
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
      office_shifts: [],
      companies: [],
      editmode: false,
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
          label: this.$t("Name"),
          field: "name",
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
  components: {
    VueClockPicker,
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
        this.Get_Office_Shift(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Office_Shift(1);
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
      this.Get_Office_Shift(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Office_Shift(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Office_Shift
    Submit_Office_Shift() {
      this.$refs.Create_Office_Shift.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Office_Shift();
          } else {
            this.Update_Office_Shift();
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

    //------------------------------ Modal (create Office_Shift) -------------------------------\\
    New_Office_Shift() {
      this.reset_Form();
       this.Get_Data_Create();
      this.editmode = false;
      this.$bvModal.show("New_Office_Shift");
    },

    //------------------------------ Modal (Update Office_Shift) -------------------------------\\
    Edit_Office_Shift(office_shift) {
      this.Get_Office_Shift(this.serverParams.page);
      this.reset_Form();
      this.editmode = true;
       this.Get_Data_Edit(office_shift.id);
      this.office_shift = office_shift;
      this.$bvModal.show("New_Office_Shift");
    },

     //---------------------- Get_Data_Create  ------------------------------\\
      Get_Data_Create() {
          axios
              .get("/office_shift/create")
              .then(response => {
                  this.companies   = response.data.companies;
              })
              .catch(error => {
                  
              });
      },

    //---------------------- Get_Data_Edit  ------------------------------\\
    Get_Data_Edit(id) {
        axios
            .get("/office_shift/"+id+"/edit")
            .then(response => {
                this.companies   = response.data.companies;
            })
            .catch(error => {
                
            });
    },

    Selected_Company(value) {
        if (value === null) {
            this.office_shift.company_id = "";
        }
    },

    //--------------------------Get ALL office_shift ---------------------------\\

    Get_Office_Shift(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "office_shift?page=" +
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
          this.office_shifts = response.data.office_shifts;
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

    //------------------------------- Create office_shift ------------------------\\
    Create_Office_Shift() {
      
      this.SubmitProcessing = true;
      axios
        .post("office_shift", {
          name: this.office_shift.name,
          company_id: this.office_shift.company_id,
          monday_in: this.office_shift.monday_in,
          monday_out: this.office_shift.monday_out,
          tuesday_in: this.office_shift.tuesday_in,
          tuesday_out: this.office_shift.tuesday_out,
          wednesday_in: this.office_shift.wednesday_in,
          wednesday_out: this.office_shift.wednesday_out,
          thursday_in: this.office_shift.thursday_in,
          thursday_out: this.office_shift.thursday_out,
          friday_in: this.office_shift.friday_in,
          friday_out: this.office_shift.friday_out,
          saturday_in: this.office_shift.saturday_in,
          saturday_out: this.office_shift.saturday_out,
          sunday_in: this.office_shift.sunday_in,
          sunday_out: this.office_shift.sunday_out,
          
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Office_Shift");
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

    //------------------------------- Update office_shift ------------------------\\
    Update_Office_Shift() {
      this.SubmitProcessing = true;
      axios
        .put("office_shift/" + this.office_shift.id, {
          name: this.office_shift.name,
          company_id: this.office_shift.company_id,
          monday_in: this.office_shift.monday_in,
          monday_out: this.office_shift.monday_out,
          tuesday_in: this.office_shift.tuesday_in,
          tuesday_out: this.office_shift.tuesday_out,
          wednesday_in: this.office_shift.wednesday_in,
          wednesday_out: this.office_shift.wednesday_out,
          thursday_in: this.office_shift.thursday_in,
          thursday_out: this.office_shift.thursday_out,
          friday_in: this.office_shift.friday_in,
          friday_out: this.office_shift.friday_out,
          saturday_in: this.office_shift.saturday_in,
          saturday_out: this.office_shift.saturday_out,
          sunday_in: this.office_shift.sunday_in,
          sunday_out: this.office_shift.sunday_out,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Office_Shift");

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
      this.office_shift = {
          id: "",
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
      };
    },

    //------------------------------- Delete office_shift ------------------------\\
    Remove_Office_Shift(id) {
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
            .delete("office_shift/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Office_Shift");
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

    //---- Delete company by selection

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
            .post("office_shift/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Office_Shift");
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
    this.Get_Office_Shift(1);

    Fire.$on("Event_Office_Shift", () => {
      setTimeout(() => {
        this.Get_Office_Shift(this.serverParams.page);
        this.$bvModal.hide("New_Office_Shift");
      }, 500);
    });

    Fire.$on("Delete_Office_Shift", () => {
      setTimeout(() => {
        this.Get_Office_Shift(this.serverParams.page);
      }, 500);
    });
  }
};
</script>