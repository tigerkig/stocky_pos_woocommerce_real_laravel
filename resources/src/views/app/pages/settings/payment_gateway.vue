<template>
  <div class="main-content">
    <breadcumb :page="$t('payment_gateway')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <!-- Payment Gateway -->
    <validation-observer ref="form_payment" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Payment">
        <b-row class="mt-5">
          <b-col lg="12" md="12" sm="12">
            <b-card no-body :header="$t('Payment_Gateway')">
              <b-card-body>
                <b-row>
                   <!-- Strip key  -->
                  <b-col lg="6" md="6" sm="12">
                      <b-form-group label="STRIPE_KEY">
                        <b-form-input
                           type="password"
                           v-model="gateway.stripe_key"
                            :placeholder="$t('LeaveBlank')"
                        ></b-form-input>
                      </b-form-group>
                  </b-col>

                   <!-- Strip Secret  -->
                  <b-col lg="6" md="6" sm="12">
                      <b-form-group label="STRIPE_SECRET">
                        <b-form-input
                          type="password"
                          v-model="gateway.stripe_secret"
                           :placeholder="$t('LeaveBlank')"
                        ></b-form-input>
                      </b-form-group>
                  </b-col>

                   <!-- Remove Stripe Key & Secret-->
                    <b-col md="6" class="mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                         {{$t('Remove_Stripe_Key_Secret')}}
                          <input  type="checkbox" v-model="gateway.deleted">
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
    title: "Payment Gateway"
  },
  data() {
    return {
      isLoading: true,
      gateway:{
        stripe_key:"",
        stripe_secret:"",
        deleted:false,
      },
    
    };
  },

  methods: {
    ...mapActions(["refreshUserPermissions"]),

     //------------- Submit Validation Payment
    Submit_Payment() {
      this.$refs.form_payment.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
            this.Update_Payment();
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

    //---------------------------------- Update Payment Gateway ----------------\\
    Update_Payment() {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("payment_gateway",{
          stripe_key: this.gateway.stripe_key,
          stripe_secret: this.gateway.stripe_secret,
          deleted: this.gateway.deleted,
        })
        .then(response => {
          Fire.$emit("Event_payment");
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

     //---------------------------------- GET Payment_Gateway ----------------\\
    Get_Payment_Gateway() {
      axios
        .get("get_payment_gateway")
        .then(response => {
          this.gateway = response.data.gateway;
          this.isLoading = false;
        })
        .catch(error => {
          this.isLoading = false;
        });
    },


   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Payment_Gateway();

    Fire.$on("Event_payment", () => {
      this.Get_Payment_Gateway();
    });


  }
};
</script>