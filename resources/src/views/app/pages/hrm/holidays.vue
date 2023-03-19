<template>
  <div class="main-content">
    <breadcumb :page="$t('Holidays')" :folder="$t('hrm')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="holidays"
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
            @click="New_Holiday()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Holiday(props.row)" class="cursor-pointer" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover class="cursor-pointer" @click="Remove_Holiday(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Holiday">
      <b-modal hide-footer size="md" id="New_Modal_Holiday" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Holiday">
          <b-row>
               <!-- Company -->
                 <b-col md="12">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="holiday.company_id"
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


              <!-- title -->
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
                      v-model="holiday.title"
                    ></b-form-input>
                    <b-form-invalid-feedback id="title-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- start date -->
              <b-col md="12">
                <validation-provider
                  name="start_date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                    <b-form-group :label="$t('start_date') + ' ' + '*'">
                        <Datepicker id="start_date" name="start_date" :placeholder="$t('Enter_Start_date')" v-model="holiday.start_date" 
                            input-class="form-control back_important" format="yyyy-MM-dd"  @closed="holiday.start_date=formatDate(holiday.start_date)">
                        </Datepicker>
                        <b-form-invalid-feedback id="start_date-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                     </b-form-group>
                </validation-provider>
              </b-col>

               <!-- end date -->
              <b-col md="12">
                <validation-provider
                  name="Finish_Date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                    <b-form-group :label="$t('Finish_Date') + ' ' + '*'">
                        <Datepicker id="end_date" name="end_date" :placeholder="$t('Enter_Finish_date')" v-model="holiday.end_date" 
                            input-class="form-control back_important" format="yyyy-MM-dd"  @closed="holiday.end_date=formatDate(holiday.end_date)">
                        </Datepicker>
                        <b-form-invalid-feedback id="end_date-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                     </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Please_provide_any_details -->
            <b-col md="12">
                <b-form-group :label="$t('Please_provide_any_details')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Please_provide_any_details')"
                    label="description"
                    v-model="holiday.description"
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
    title: "Holiday"
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
      companies:[],
      holidays: {}, 
      holiday: {
          title: "",
          company_id:"",
          start_date:"",
          end_date:"",
          description:"",
      }, 
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Holiday"),
          field: "title",
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
        this.Get_Holidays(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Holidays(1);
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
      this.Get_Holidays(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Holidays(this.serverParams.page);
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
  

    //------------- Submit Validation Create & Edit Holiday
    Submit_Holiday() {
      this.$refs.Create_Holiday.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Holiday();
          } else {
            this.Update_Holiday();
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

   //------------------------------ Show Modal (Create Holiday) -------------------------------\\
    New_Holiday() {
        this.reset_Form();
        this.editmode = false;
        this.Get_Data_Create();
        this.$bvModal.show("New_Modal_Holiday");
    },

    //------------------------------ Show Modal (Update Holiday) -------------------------------\\
    Edit_Holiday(holiday) {
        this.editmode = true;
        this.reset_Form();
        this.Get_Data_Edit(holiday.id);
        this.holiday = holiday;
        this.$bvModal.show("New_Modal_Holiday");
    },

     //---------------------- Get_Data_Create  ------------------------------\\
      Get_Data_Create() {
          axios
              .get("/holiday/create")
              .then(response => {
                  this.companies   = response.data.companies;
              })
              .catch(error => {
                  
              });
      },

      //---------------------- Get_Data_Edit  ------------------------------\\
      Get_Data_Edit(id) {
        axios
             .get(`holiday/${id}/edit`)
            .then(response => {
                this.companies   = response.data.companies;
            })
            .catch(error => {
                
            });
    },

       Selected_Company(value) {
          if (value === null) {
              this.policy.company_id = "";
          }
      },



    //--------------------------Get ALL holidays ---------------------------\\

    Get_Holidays(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "holiday?page=" +
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
          this.holidays = response.data.holidays;

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

    //------------------------------- Create holiday ------------------------\\
    Create_Holiday() {
      
        var self = this;
        self.SubmitProcessing = true;
        axios.post("/holiday", {
            company_id: self.holiday.company_id,
            title: self.holiday.title,
            start_date: self.holiday.start_date,
            end_date: self.holiday.end_date,
            description: self.holiday.description,
        }).then(response => {
            this.SubmitProcessing = false;
            Fire.$emit("Event_Holiday");
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

    //------------------------------- Update holiday ------------------------\\
    Update_Holiday() {

      var self = this;
      self.SubmitProcessing = true;
      axios.put("/holiday/" + self.holiday.id, {
          title: self.holiday.title,
          company_id: self.holiday.company_id,
          start_date: self.holiday.start_date,
          end_date: self.holiday.end_date,
          description: self.holiday.description,
      }).then(response => {
            this.SubmitProcessing = false;
            Fire.$emit("Event_Holiday");

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
     this.holiday = {
        id: "",
        title: "",
        company_id:"",
        start_date:"",
        end_date:"",
        description:"",
    };
    },

    //------------------------------- Delete holiday ------------------------\\
    Remove_Holiday(id) {
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
            .delete("holiday/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Holiday");
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
            .post("holiday/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_Holiday");
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
    this.Get_Holidays(1);

    Fire.$on("Event_Holiday", () => {
      setTimeout(() => {
        this.Get_Holidays(this.serverParams.page);
        this.$bvModal.hide("New_Modal_Holiday");
      }, 500);
    });

    Fire.$on("Delete_Holiday", () => {
      setTimeout(() => {
        this.Get_Holidays(this.serverParams.page);
      }, 500);
    });
  }
};
</script>