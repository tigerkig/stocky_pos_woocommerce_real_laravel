<template>
  <div class="main-content">
    <breadcumb :page="$t('update_settings')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>


    <div class="col-md-12" v-if="!isLoading">

            <div class="card">
                <div class="card-header">
                    <span>{{$t('Update_Log')}}</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">{{$t('Note_update')}}</div>
                    <div class="alert alert-info" v-if="version !=''">
                        <strong>{{$t('Update_Available')}}
                            <span class="badge badge-pill badge-info">
                                {{version}}
                            </span>
                        </strong>
                       
                    </div>
                    <div class="alert alert-info" v-else>
                        <strong>{{$t('You_already_have_the_latest_version')}} <span class="badge badge-pill badge-info"></span></strong>
                    </div>
                      <div class="col-md-12 text-center mt-3">
                        <a href="https://github.com/uilibrary/Stocky-Issues-and-Feature-request" target="_blank"
                            class="btn btn-outline-info">{{$t('View_Change_Log')}}</a>
                    </div>

                     <div class="col-md-12 mt-3">
                       <h5>Please follow these steps, To Update your application</h5>
                       <div class="allert alert-danger">Note 1: If you have made any changes in the code manually then your changes will be lost.</div>
                        <div class="allert alert-danger">Note 2: only admin or user who has permission "system_setting" he can upgrade the system</div>
                       <ul>
                        <li>
                           <strong>Step 1 : </strong>Take back up of your database,  Go to <a href="/app/settings/Backup">Backup</a> Click on Generate Backup ,
                           You will find it in <strong>/storage/app/public/backup</strong>  and save it to your pc To restore it if there is an error , 
                           or Go to your PhpMyAdmin and export your database then and save it to your pc To restore it if there is an error
                        </li>

                         <li>
                           <strong>Step 2 : </strong> Take back up of your files before updating.
                         </li>

                        <li>
                           <strong>Step 3 : </strong>  Download the latest version from your codecanyon and Extract it .
                         </li>

                         <li>
                           <strong>Step 4 : </strong> Replace all the files and folders <strong>except</strong> the following :
                            <ul>
                                <li>file   : <strong>.env</strong></li>
                                <li>file   : <strong>.htaccess</strong></li>
                                <li>Folder : <strong>storage</strong></li>
                                <li>Folder : <strong>Modules</strong> (If you have any Module installed)</li>
                                <li>file   : <strong>modules_statuses.json</strong> (If you have any Module installed)</li>
                                <li>Folder : <strong>images folder in public : /public/images</strong></li>
                              </ul>
                          </li>

                         <li>
                           <strong>Step 5 : </strong>Visit  http://your_app/update to update your database
                         </li>

                          <li>
                           <strong>Step 6 : </strong> Hard Clear your cache browser
                         </li>

                           <li>
                           <strong>Step 7 : </strong> You are done! Enjoy the updated application
                         </li>

                       </ul>
                       <div class="allert alert-danger">Note: If any pages are not loading or blank, make sure you cleared your browser cache.</div>
                     </div>

                     <div class="col-md-12 mt-3">
                       <h4 class="mb-3">This video showing you the steps on how to upgrade stocky</h4>

                       <iframe width="853" height="480" src="https://www.youtube.com/embed/VwfRtMkxS9U"
                          title="how to update Stocky step by step" frameborder="0" 
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                          allowfullscreen>
                        </iframe>
                     </div>


                </div>
            </div>
        </div>

  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Update Settings"
  },
  data() {
    return {
      
      isLoading: true,
      SubmitProcessing:false,
      version:"",
     
    };
  },

  methods: {

        //------------------------ Update ---------------------------\\
        Update_system() {
            var self = this;
            self.SubmitProcessing = true;
            NProgress.start();
            NProgress.set(0.1);
            axios.get("/").then(response => {
                self.SubmitProcessing = false;
                NProgress.done();
                this.makeToast(
                  "success",
                  this.$t("Successfully_Updated"),
                  this.$t("Success")
                );
                Fire.$emit("Event_update");
            })
            .catch(error => {
                 self.SubmitProcessing = false;
                 NProgress.done();
                if(error.response.status == 400){
                     this.makeToast("danger", error.response.data.msg, this.$t("Failed"));
                }else{
                    this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
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

        //---------------------------------- get_version_info ----------------\\
        get_version_info() {
           var self = this;
          axios
            .get("get_version_info")
            .then(response => {
              self.version = response.data;
              self.isLoading = false;
            })
            .catch(error => {
              setTimeout(() => {
                self.isLoading = false;
              }, 500);
            });
        },   



   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.get_version_info();


    Fire.$on("Event_update", () => {
      this.get_version_info();
    });
  }
};
</script>