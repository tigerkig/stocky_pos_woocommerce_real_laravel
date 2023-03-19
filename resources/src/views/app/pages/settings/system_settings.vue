<template>
  <div class="main-content">
    <breadcumb :page="$t('SystemSettings')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <!-- System Settings -->
    <validation-observer ref="form_setting" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Setting">
        <b-row>
          <b-col lg="12" md="12" sm="12">
            <b-card no-body :header="$t('SystemSettings')">
              <b-card-body>
                <b-row>
                  <!-- Default Currency -->
                  <b-col lg="4" md="4" sm="12">
                    <b-form-group :label="$t('DefaultCurrency')">
                      <v-select
                        v-model="setting.currency_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Currency')"
                        :options="currencies.map(currencies => ({label: currencies.name, value: currencies.id}))"
                      />
                    </b-form-group>
                  </b-col>

                  <!-- Email  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="Email"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('DefaultEmail') + ' ' + '*'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Email-feedback"
                          label="Email"
                          :placeholder="$t('DefaultEmail')"
                          v-model="setting.email"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="Email-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- - Logo -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider name="Logo" ref="Logo" rules="mimes:image/*|size:200">
                      <b-form-group
                        slot-scope="{validate, valid, errors }"
                        :label="$t('ChangeLogo')"
                      >
                        <input
                          :state="errors[0] ? false : (valid ? true : null)"
                          :class="{'is-invalid': !!errors.length}"
                          @change="onFileSelected"
                          label="Choose Logo"
                          type="file"
                        >
                        <b-form-invalid-feedback id="Logo-feedback">{{ errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Company Name  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="Company Name"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('CompanyName') + ' ' + '*'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Company-feedback"
                          label="Company Name"
                          :placeholder="$t('CompanyName')"
                          v-model="setting.CompanyName"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="Company-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Company Phone  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="Company Phone"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('CompanyPhone') + ' ' + '*'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Phone-feedback"
                          label="Company Phone"
                          :placeholder="$t('CompanyPhone')"
                          v-model="setting.CompanyPhone"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="Phone-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- developed by -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="developed by"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('developed_by') + ' ' + '*'">
                         <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="developed_by-feedback"
                          v-model="setting.developed_by"
                          class="form-control"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="developed_by-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                   <!-- Footer -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="footer"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('footer') + ' ' + '*'">
                         <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="footer-feedback"
                          v-model="setting.footer"
                          class="form-control"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="footer-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                   <!-- Default Language -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider name="DefaultLanguage" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('DefaultLanguage') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="setting.default_language"
                        :reduce="label => label.value"
                        :placeholder="$t('DefaultLanguage')"
                              :options="
                                  [
                                  {label: 'English', value: 'en'},
                                  {label: 'French', value: 'fr'},
                                  {label: 'Arabic', value: 'ar'},
                                  {label: 'Turkish', value: 'tur'},
                                  {label: 'Simplified Chinese', value: 'sm_ch'},
                                  {label: 'Thaï', value: 'thai'},
                                  {label: 'Hindi', value: 'hn'},
                                  {label: 'German', value: 'de'},
                                  {label: 'Spanish', value: 'es'},
                                  {label: 'Italien', value: 'it'},
                                  {label: 'Indonesian', value: 'Ind'},
                                  {label: 'Traditional Chinese', value: 'tr_ch'},
                                  {label: 'Russian', value: 'ru'},
                                  {label: 'Vietnamese', value: 'vn'},
                                  {label: 'Korean', value: 'kr'},
                                  {label: 'Bangla', value: 'ba'},
                                  {label: 'Portuguese', value: 'br'},
                              ]"                     
                      ></v-select>
                        <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                         </b-form-group>
                    </validation-provider>
                  </b-col>

                  
                  <!-- Default Customer -->
                  <b-col lg="4" md="4" sm="12">
                    <b-form-group :label="$t('DefaultCustomer')">
                      <v-select
                        v-model="setting.client_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Customer')"
                        :options="clients.map(clients => ({label: clients.name, value: clients.id}))"
                      />
                    </b-form-group>
                  </b-col>

                   <!-- Default Warehouse -->
                  <b-col lg="4" md="4" sm="12">
                    <b-form-group :label="$t('DefaultWarehouse')">
                      <v-select
                        v-model="setting.warehouse_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Warehouse')"
                        :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
                      />
                    </b-form-group>
                  </b-col>

                   <!-- Default SMS Gateway -->
                  <b-col lg="4" md="4" sm="12">
                    <b-form-group :label="$t('Default_SMS_Gateway')">
                      <v-select
                        v-model="setting.sms_gateway"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_SMS_Gateway')"
                        :options="sms_gateway.map(sms_gateway => ({label: sms_gateway.title, value: sms_gateway.id}))"
                      />
                    </b-form-group>
                  </b-col>

                   <!-- Time_Zone -->
                  <b-col lg="4" md="4" sm="12">
                    <b-form-group :label="$t('Time_Zone')">
                     <v-select @input="Selected_Time_Zone"
                          :placeholder="$t('Time_Zone')"
                          v-model="setting.timezone" :reduce="label => label.value"
                          :options="zones_array.map(zones_array => ({label: zones_array.label, value: zones_array.zone}))">
                      </v-select>
                    </b-form-group>
                  </b-col>

                   <!-- Company Adress -->
                  <b-col lg="12" md="12" sm="12">
                    <validation-provider
                      name="Adress"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Adress') + ' ' + '*'">
                         <textarea
                          :state="getValidationState(validationContext)"
                          aria-describedby="Adress-feedback"
                          v-model="setting.CompanyAdress"
                          class="form-control"
                          :placeholder="$t('Afewwords')"
                         ></textarea>
                        <b-form-invalid-feedback
                          id="Adress-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <b-col md="2" sm="2" class="mt-4 mb-4">
                    <label class="checkbox checkbox-primary mb-3"><input type="checkbox" v-model="setting.is_invoice_footer"><h5>{{$t('invoice_footer')}} </h5><span class="checkmark"></span></label>
                  </b-col>

                   <b-col md="6" sm="6" class="mt-4 mb-4" v-if="setting.is_invoice_footer">
                  <validation-provider
                      name="invoice_footer"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('invoice_footer') + ' ' + '*'">
                         <textarea
                          :state="getValidationState(validationContext)"
                          aria-describedby="invoice_footer-feedback"
                          v-model="setting.invoice_footer"
                          class="form-control"
                          :placeholder="$t('invoice_footer')"
                         ></textarea>
                        <b-form-invalid-feedback
                          id="invoice_footer-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                </b-col>


                  <b-col md="12">
                    <b-form-group>
                      <b-button variant="primary" type="submit">{{$t('submit')}}</b-button>
                    </b-form-group>
                  </b-col>
                </b-row>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>

    <!-- Clear Cache -->
      <b-form @submit.prevent="Clear_Cache" v-if="!isLoading">
        <b-row class="mt-5">
          <b-col lg="12" md="12" sm="12">
            <b-card no-body :header="$t('Clear_Cache')">
              <b-card-body>
                <b-row>

                  <b-col md="12">
                    <b-form-group>
                      <b-button variant="primary" @click="Clear_Cache()" >{{$t('Clear_Cache')}}</b-button>
                    </b-form-group>
                  </b-col>
                </b-row>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-form>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "System Settings"
  },
  data() {
    return {
      
      isLoading: true,
      data: new FormData(),
      settings: [],
      currencies: [],
      clients: [],
      warehouses: [],
      sms_gateway: [],
      zones_array:[],
      setting: {
        client_id: "",
        warehouse_id: "",
        currency_id: "",
        email: "",
        logo: "",
        CompanyName: "",
        CompanyPhone: "",
        CompanyAdress: "",
        footer:"",
        developed_by:"",
        default_language:"",
        sms_gateway:"",
        is_invoice_footer:'',
        invoice_footer:'',
      },

    };
  },

  methods: {
    ...mapActions(["refreshUserPermissions"]),


      SetLocal(locale) {
      this.$i18n.locale = locale;
      this.$store.dispatch("language/setLanguage", locale);
      Fire.$emit("ChangeLanguage");
    },

    //------------- Submit Validation Setting
    Submit_Setting() {
      this.$refs.form_setting.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Update_Settings();
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

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------------------------ Event Upload Logo -------------------------------\\
    async onFileSelected(e) {
      const { valid } = await this.$refs.Logo.validate(e);

      if (valid) {
        this.setting.logo = e.target.files[0];
      } else {
        this.setting.logo = "";
      }
    },

     Selected_Time_Zone(value) {
          if (value === null) {
              this.setting.timezone = "";
          }
      },


  
    //---------------------------------- Update Settings ----------------\\
    Update_Settings() {
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.data.append("client", self.setting.client_id);
      self.data.append("warehouse", self.setting.warehouse_id);
      self.data.append("currency", self.setting.currency_id);
      self.data.append("email", self.setting.email);
      self.data.append("logo", self.setting.logo);
      self.data.append("CompanyName", self.setting.CompanyName);
      self.data.append("CompanyPhone", self.setting.CompanyPhone);
      self.data.append("CompanyAdress", self.setting.CompanyAdress);
      self.data.append("footer", self.setting.footer);
      self.data.append("developed_by", self.setting.developed_by);
      self.data.append("default_language", self.setting.default_language);
      self.data.append("sms_gateway", self.setting.sms_gateway);
      self.data.append("is_invoice_footer", self.setting.is_invoice_footer);
      self.data.append("invoice_footer", self.setting.invoice_footer);
      self.data.append("timezone", self.setting.timezone);
      self.data.append("_method", "put");

      axios
        .post("settings/" + self.setting.id, self.data)
        .then(response => {
          Fire.$emit("Event_Setting");
          this.makeToast(
            "success",
            this.$t("Successfully_Updated"),
            this.$t("Success")
          );
          this.refreshUserPermissions();
          NProgress.done();
          this.SetLocal(self.setting.default_language);
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          NProgress.done();
        });
    }, 

    //---------------------------------- Clear_Cache ----------------\\
    Clear_Cache() {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("clear_cache")
        .then(response => {
          this.makeToast(
            "success",
            this.$t("Cache_cleared_successfully"),
            this.$t("Success")
          );
          NProgress.done();
        })
        .catch(error => {
          NProgress.done();
          this.makeToast("danger", this.$t("Failed_to_clear_cache"), this.$t("Failed"));
        });
    },   

    //---------------------------------- Get SETTINGS ----------------\\
    Get_Settings() {
      axios
        .get("get_Settings_data")
        .then(response => {
          this.setting    = response.data.settings;
          this.currencies = response.data.currencies;
          this.clients    = response.data.clients;
          this.warehouses = response.data.warehouses;
          this.sms_gateway    = response.data.sms_gateway;
           this.zones_array    = response.data.zones_array;
          this.isLoading = false;
        })
        .catch(error => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Settings();

    Fire.$on("Event_Setting", () => {
      this.Get_Settings();
    });

  }
};
</script>