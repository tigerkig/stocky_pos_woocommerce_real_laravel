<template>
  <div class="main-content">
    <breadcumb :page="$t('pos_settings')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

     <!-- Reciept Pos Settings -->
    <validation-observer ref="Submit_Pos_Settings" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Pos_Settings">
        <b-row class="mt-5">
          <b-col lg="12" md="12" sm="12">
            <b-card no-body :header="$t('Pos_Settings')">
              <b-card-body>
                <b-row>

                   <!-- Note to customer  -->
                  <b-col lg="12" md="12" sm="12">
                    <validation-provider
                      name="note"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group :label="$t('Note_to_customer') + ' ' + '*'">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="note-feedback"
                          label="Note to customer"
                          :placeholder="$t('Note_to_customer')"
                          v-model="pos_settings.note_customer"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="note-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                 

                   <!-- Show Phone-->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Phone')}}
                          <input  type="checkbox" v-model="pos_settings.show_phone">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                     <!-- Show Address -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Address')}}
                          <input  type="checkbox" v-model="pos_settings.show_address">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                      <!-- Show Email  -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Email')}}
                          <input  type="checkbox" v-model="pos_settings.show_email">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                    <!-- Show Customer  -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Customer')}}
                          <input  type="checkbox" v-model="pos_settings.show_customer">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                     <!-- Show Tax & Discount  -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Tax_and_Discount')}}
                          <input  type="checkbox" v-model="pos_settings.show_discount">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                     <!-- Show barcode  -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_barcode')}}
                          <input  type="checkbox" v-model="pos_settings.show_barcode">
                          <span class="slider"></span>
                        </label>
                    </b-col>

                      <!-- Show Note_to_customer  -->
                    <b-col md="4" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Show_Note_to_customer')}}
                          <input  type="checkbox" v-model="pos_settings.show_note">
                          <span class="slider"></span>
                        </label>
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

  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "POS Settings"
  },
  data() {
    return {
      
      isLoading: true,
     
      pos_settings:{
        note_customer:"",
        show_note:"",
        show_barcode:"",
        show_discount:"",
        show_phone:"",
        show_email:"",
        show_address:"",
        show_customer:"",
      },

    };
  },

  methods: {
    ...mapActions(["refreshUserPermissions"]),

     //------------- Submit Validation Pos Setting
    Submit_Pos_Settings() {
      this.$refs.Submit_Pos_Settings.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Update_Pos_Settings();
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


      //---------------------------------- Update_Pos_Settings ----------------\\
    Update_Pos_Settings() {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .put("pos_settings/" + this.pos_settings.id, {
          note_customer: this.pos_settings.note_customer,
          show_note: this.pos_settings.show_note,
          show_barcode: this.pos_settings.show_barcode,
          show_discount: this.pos_settings.show_discount,
          show_phone: this.pos_settings.show_phone,
          show_email: this.pos_settings.show_email,
          show_address: this.pos_settings.show_address,
          show_customer: this.pos_settings.show_customer,      
        })
        .then(response => {
          Fire.$emit("Event_Pos_Settings");
          this.makeToast(
            "success",
            this.$t("Successfully_Updated"),
            this.$t("Success")
          );
          NProgress.done();
        })
        .catch(error => {
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },


 //---------------------------------- Get_pos_Settings ----------------\\ 
    get_pos_Settings() {
      axios
        .get("get_pos_Settings")
        .then(response => {
          this.pos_settings = response.data.pos_settings;
          this.isLoading = false;
        })
        .catch(error => {
          this.isLoading = false;
        });
    },

   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.get_pos_Settings();

     Fire.$on("Event_Pos_Settings", () => {
      this.get_pos_Settings();
    });

  }
};
</script>