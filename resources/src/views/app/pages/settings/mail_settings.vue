<template>
  <div class="main-content">
    <breadcumb :page="$t('mail_settings')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>


    <!-- mail_settings -->
    <validation-observer ref="form_config_mail" v-if="!isLoading">
      <b-form @submit.prevent="Submit_config_mail">
        <b-row class="mt-5">
          <b-col lg="12" md="12" sm="12">
            <b-card no-body :header="$t('mail_settings')">
              <b-card-body>
                <b-row>

                   <!-- MAIL_MAILER  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="MAIL_MAILER"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_MAILER *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="MAIL_MAILER-feedback"
                          label="MAIL_MAILER"
                          placeholder="MAIL_MAILER"
                          v-model="server.mail_mailer"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="MAIL_MAILER-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                      <p class="text-danger">Supported: "smtp", "sendmail", "mailgun", "ses","postmark", "log"</p>
                    </validation-provider>
                  </b-col>

                  <!-- HOST  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="HOST"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_HOST *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="HOST-feedback"
                          label="HOST"
                          placeholder="MAIL_HOST"
                          v-model="server.host"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="HOST-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- PORT  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="PORT"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_PORT *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="PORT-feedback"
                          label="PORT"
                          placeholder="MAIL_PORT"
                          v-model="server.port"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="PORT-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Sender Name  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="sender"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="Sender Name *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="sender-feedback"
                          label="Sender"
                          placeholder="Sender Name"
                          v-model="server.sender_name"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="sender-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Username  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="Username"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_USERNAME *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Username-feedback"
                          label="Username"
                          placeholder="MAIL_USERNAME"
                          v-model="server.username"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="Username-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- Password  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="Password"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_PASSWORD *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Password-feedback"
                          label="Password"
                          placeholder="MAIL_PASSWORD"
                          v-model="server.password"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="Password-feedback"
                        >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                      </b-form-group>
                    </validation-provider>
                  </b-col>

                  <!-- encryption  -->
                  <b-col lg="4" md="4" sm="12">
                    <validation-provider
                      name="encryption"
                      :rules="{ required: true}"
                      v-slot="validationContext"
                    >
                      <b-form-group label="MAIL_ENCRYPTION *">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="encryption-feedback"
                          label="encryption"
                          placeholder="MAIL_ENCRYPTION"
                          v-model="server.encryption"
                        ></b-form-input>
                        <b-form-invalid-feedback
                          id="encryption-feedback"
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

  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Mail Settings"
  },
  data() {
    return {
      
      isLoading: true,
      server: {
        host: "",
        port: "",
        username: "",
        password: "",
        encryption: "",
        sender_name:"",
        mail_mailer:"",
      }
    };
  },

  methods: {
    ...mapActions(["refreshUserPermissions"]),

    //------------- Submit Validation SMTP
    Submit_config_mail() {
      this.$refs.form_config_mail.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
            this.Update_config_mail();
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

    //---------------------------------- Update SMTP ----------------\\
    Update_config_mail() {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .put("update_config_mail/" + this.server.id, {
          mail_mailer: this.server.mail_mailer,
          host: this.server.host,
          port: this.server.port,
          sender_name: this.server.sender_name,
          username: this.server.username,
          password: this.server.password,
          encryption: this.server.encryption
        })
        .then(response => {
          Fire.$emit("Event_Smtp");
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

    //---------------------------------- GET SMTP ----------------\\ 
    get_config_mail() {
      axios
        .get("get_config_mail")
        .then(response => {
          this.server = response.data.server;
          this.isLoading = false;
        })
        .catch(error => {
          this.isLoading = false;
        });
    },

   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.get_config_mail();

    Fire.$on("Event_Smtp", () => {
      this.get_config_mail(); 
    });

  }
};
</script>