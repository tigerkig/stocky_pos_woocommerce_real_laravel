<template>
  <div class="main-content">
    <breadcumb :page="$t('Add_Employee')" :folder="$t('Employees')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="Create_Employee" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Employee" enctype="multipart/form-data">
        <b-row>
          <b-col md="8">
            <b-card>
              <b-row>
                <!-- FirstName -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider
                    name="FirstName"
                    :rules="{required:true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('FirstName') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="FirstName-feedback"
                        label="FirstName"
                        :placeholder="$t('Enter_FirstName')"
                        v-model="employee.firstname"
                      ></b-form-input>
                      <b-form-invalid-feedback id="FirstName-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!-- LastName -->
               <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider
                    name="LastName"
                    :rules="{required:true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('LastName') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="LastName-feedback"
                        label="LastName"
                        :placeholder="$t('Enter_LastName')"
                        v-model="employee.lastname"
                      ></b-form-input>
                      <b-form-invalid-feedback id="LastName-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!-- Gender -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider name="Gender" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Gender') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="employee.gender"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Gender')"
                        :options="
                           [
                            {label: 'Male', value: 'male'},
                            {label: 'Female', value: 'female'}
                           ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

               <!-- Birth_date  -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <b-form-group :label="$t('Birth_date')">
                      <Datepicker id="birth_date" name="birth_date" :placeholder="$t('Enter_Birth_date')" v-model="employee.birth_date" 
                          input-class="form-control back_important" format="yyyy-MM-dd"  @closed="employee.birth_date=formatDate(employee.birth_date)">
                      </Datepicker>
                    </b-form-group>
                </b-col>

              <!-- Email_Address -->
               <b-col lg="6" md="6" sm="12" class="mb-2">
                  <b-form-group :label="$t('Email_Address')">
                    <b-form-input
                      label="Email_Address"
                      :placeholder="$t('Enter_email_address')"
                      v-model="employee.email"
                    ></b-form-input>
                    </b-form-group>
                </b-col>

                 <!-- country -->
               <b-col lg="6" md="6" sm="12" class="mb-2">
                  <b-form-group :label="$t('Country')">
                    <b-form-input
                      label="Country"
                      :placeholder="$t('Enter_Country')"
                      v-model="employee.country"
                    ></b-form-input>
                    </b-form-group>
                </b-col>

                   <!-- phone -->
               <b-col lg="6" md="6" sm="12" class="mb-2">
                  <b-form-group :label="$t('Phone')">
                    <b-form-input
                      label="phone"
                      :placeholder="$t('Enter_Phone_Number')"
                      v-model="employee.phone"
                    ></b-form-input>
                    </b-form-group>
                </b-col>

                 <!-- joining_date  -->
                 <b-col lg="6" md="6" sm="12" class="mb-2">
                    <b-form-group :label="$t('joining_date')">
                        <Datepicker id="joining_date" name="joining_date" :placeholder="$t('Enter_joining_date')" v-model="employee.joining_date" 
                            input-class="form-control back_important" format="yyyy-MM-dd"  @closed="employee.joining_date=formatDate(employee.joining_date)">
                        </Datepicker>
                    </b-form-group>
                </b-col>
             
                <!-- Company -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Company" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Company') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="employee.company_id"
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
                <b-col md="6" class="mb-2">
                  <validation-provider name="Department" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Department') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="employee.department_id"
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
                <b-col md="6" class="mb-2">
                  <validation-provider name="Designation" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Designation') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="employee.designation_id"
                        class="required"
                        required
                        @input="Selected_Designation"
                        :placeholder="$t('Choose_Designation')"
                        :reduce="label => label.value"
                        :options="designations.map(designations => ({label: designations.designation, value: designations.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!-- Office_Shift -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Office_Shift" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Office_Shift') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="employee.office_shift_id"
                        class="required"
                        required
                        @input="Selected_Office_shift"
                        :placeholder="$t('Choose_Office_Shift')"
                        :reduce="label => label.value"
                        :options="office_shifts.map(office_shifts => ({label: office_shifts.name, value: office_shifts.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
              </b-row>
            </b-card>
          </b-col>
          <b-col md="12" class="mt-3">
             <b-button variant="primary" type="submit" :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>
  </div>
</template>


<script>
import NProgress from "nprogress";
import Datepicker from 'vuejs-datepicker';

export default {
  metaInfo: {
    title: "Create Employee"
  },
   components: {
    Datepicker
  },
  data() {
    return {
      
      isLoading: true,
      SubmitProcessing:false,
      data: new FormData(),
      companies: [],
      departments: [],
      designations: [],
      office_shifts: [],
      roles: {},
      employee: {
          firstname: "",
          lastname:"",
          country:"",
          email:"",
          gender:"",
          phone:"",
          birth_date:"",
          department_id:"",
          designation_id:"",
          office_shift_id:"",
          joining_date:"",
          company_id:"",
      }, 
    };
  },


  methods: {
    //------------- Submit Validation Create Employee
    Submit_Employee() {
      this.$refs.Create_Employee.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Create_Employee();
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

    formatDate(d){
      var m1 = d.getMonth()+1;
      var m2 = m1 < 10 ? '0' + m1 : m1;
      var d1 = d.getDate();
      var d2 = d1 < 10 ? '0' + d1 : d1;
      return [d.getFullYear(), m2, d2].join('-');
    },

    //------ Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //-------------- Employee Get Elements
    GetElements() {
      axios
        .get("employees/create")
        .then(response => {
          this.companies = response.data.companies;
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

        Selected_Company(value) {
            if (value === null) {
                this.employee.company_id = "";
                this.employee.department_id = "";
                this.employee.designation_id = "";
                this.employee.office_shift_id = "";
            }
            this.departments = [];
            this.designations = [];
            this.employee.department_id = "";
            this.employee.designation_id = "";
            this.employee.office_shift_id = "";
            this.Get_departments_by_company(value);
            this.Get_office_shift_by_company(value);

        },

        Selected_Department(value) {
            if (value === null) {
                this.employee.department_id = "";
                this.employee.designation_id = "";
            }
            this.designations = [];
            this.employee.designation_id = "";
            this.Get_designations_by_department(value);
        },

        Selected_Designation(value) {
            if (value === null) {
                this.employee.designation_id = "";
            }
        },

         Selected_Office_shift(value) {
            if (value === null) {
                this.employee.office_shift_id = "";
            }
        },

        //---------------------- Get_departments_by_company ------------------------------\\
        Get_departments_by_company(value) {
        axios
            .get("/core/get_departments_by_company?id=" + value)
            .then(({ data }) => (this.departments = data));
        },

        //---------------------- Get designations by department ------------------------------\\
        Get_designations_by_department(value) {
        axios
            .get("/core/get_designations_by_department?id=" + value)
            .then(({ data }) => (this.designations = data));
        },

        //---------------------- Get_office_shift_by_company ------------------------------\\
        Get_office_shift_by_company(value) {
        axios
            .get("/core/get_office_shift_by_company?id=" + value)
            .then(({ data }) => (this.office_shifts = data));
        },

    //------------------------------ Create new Employee ------------------------------\\
    Create_Employee() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.SubmitProcessing = true;

        // Send Data with axios
       axios.post("/employees", {
          firstname: self.employee.firstname,
          lastname: self.employee.lastname,
          country: self.employee.country,
          email: self.employee.email,
          gender: self.employee.gender,
          phone: self.employee.phone,
          birth_date: self.employee.birth_date,
          company_id: self.employee.company_id,
          department_id: self.employee.department_id,
          designation_id: self.employee.designation_id,
          office_shift_id: self.employee.office_shift_id,
          joining_date: self.employee.joining_date,

        }).then(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          self.SubmitProcessing = false;
          this.$router.push({ name: "employees_list" });
          this.makeToast(
            "success",
            this.$t("Successfully_Created"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.SubmitProcessing = false;
        });
    }
  }, //end Methods

  //-----------------------------Created function-------------------

  created: function() {
    this.GetElements();
  }
};
</script>
