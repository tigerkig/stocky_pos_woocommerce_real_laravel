<template>
  <div class="main-content">
    <breadcumb :page="$t('ProfitandLoss')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-row v-if="!isLoading">
      <b-col md="12" class="text-center">
        <date-range-picker 
          v-model="dateRange" 
          :startDate="startDate" 
          :endDate="endDate" 
           @update="Submit_filter_dateRange"
          :locale-data="locale" > 

          <template v-slot:input="picker" style="min-width: 350px;">
              {{ picker.startDate.toJSON().slice(0, 10)}} - {{ picker.endDate.toJSON().slice(0, 10)}}
          </template>        
        </date-range-picker>
      </b-col>

        <b-col md="12" class="mt-4">
          <b-row>
            <!-- /.Total Sales -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">
                    <span class="bold">({{infos.sales.nmbr?infos.sales.nmbr:0}})</span>
                    {{$t('Sales')}}
                  </p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.sales.sum?infos.sales.sum:0),2)}}</p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->
            <!-- /.Total Purchases -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">
                    <span class="bold">({{infos.purchases.nmbr?infos.purchases.nmbr:0}})</span>
                    {{$t('Purchases')}}
                  </p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.purchases.sum?infos.purchases.sum:0),2)}}</p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->
            <!-- /.Total Returns Sales -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">
                    <span class="bold">({{infos.returns_sales.nmbr?infos.returns_sales.nmbr:0}})</span>
                    {{$t('SalesReturn')}}
                  </p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.returns_sales.sum?infos.returns_sales.sum:0),2)}}</p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->
            <!-- /.Total Returns Purchases -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">
                    <span
                      class="bold"
                    >({{infos.returns_purchases.nmbr?infos.returns_purchases.nmbr:0}})</span>
                    {{$t('PurchasesReturn')}}
                  </p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.returns_purchases.sum?infos.returns_purchases.sum:0),2)}}</p>
                </div>
              </div>
            </b-col>

            <!-- /.col -->
            <!-- /.Expense -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">
                    <span class="bold">{{$t('Expenses')}}</span>
                  </p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.expenses.sum?infos.expenses.sum:0),2)}}</p>
                </div>
              </div>
            </b-col>

             <!-- /.Revenue -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">{{$t('Revenue')}}</p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.total_revenue?infos.total_revenue:0),2)}}</p>
                </div>

                <div class="card-footer">
                  <p>
                    (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.sales.sum?infos.sales.sum:0),2)}}</span>
                    {{$t('Sales')}})
                    - (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.returns_sales.sum?infos.returns_sales.sum:0),2)}}</span>
                    {{$t('SalesReturn')}})
              
                  </p>
                </div>
              </div>
            </b-col>

            <!-- /.Gross profit -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">{{$t('Gross_Profit')}}</p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.profit?infos.profit:0),2)}}</p>
                </div>

                <div class="card-footer">
                  <p>
                    (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.sales.sum?infos.sales.sum:0),2)}}</span>
                    {{$t('Sales')}})
                    - (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.product_cost?infos.product_cost:0),2)}}</span>
                    {{$t('Product_Cost')}})
              
                  </p>
                </div>
              </div>
            </b-col>

            <!-- /.Paiements Received -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">{{$t('PaiementsReceived')}}</p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.payment_received?infos.payment_received:0),2)}}</p>
                </div>

                <div class="card-footer">
                  <p>
                    (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.paiement_sales.sum?infos.paiement_sales.sum:0),2)}}</span>
                    {{$t('PaymentsSales')}}
                    +
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.PaymentPurchaseReturns.sum?infos.PaymentPurchaseReturns.sum:0),2)}}</span>
                    {{$t('PurchasesReturn')}})
                  </p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->

            <!-- /.Paiements Sent -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">{{$t('PaiementsSent')}}</p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.payment_sent?infos.payment_sent:0),2)}}</p>
                </div>
                <div class="card-footer">
                  <p>
                    (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.paiement_purchases.sum?infos.paiement_purchases.sum:0),2)}}</span>
                    {{$t('PaymentsPurchases')}}
                    +
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.PaymentSaleReturns.sum?infos.PaymentSaleReturns.sum:0),2)}}</span>
                    {{$t('SalesReturn')}})
                    +
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.expenses.sum?infos.expenses.sum:0),2)}}</span>
                    {{$t('Expenses')}})
                  </p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->

            <!-- /.Paiements Net -->
            <b-col md="4" sm="12">
              <div class="card card-icon text-center mb-30">
                <div class="card-body">
                  <i class="i-Data-Upload"></i>
                  <p class="text-muted mt-2 mb-2">{{$t('PaiementsNet')}}</p>
                  <p
                    class="text-primary text-24 line-height-1 m-0"
                  >{{currentUser.currency}} {{formatNumber((infos.paiement_net?infos.paiement_net:0),2)}}</p>
                </div>
                <div class="card-footer">
                  <p>
                    (
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.payment_received?infos.payment_received:0),2)}}</span>
                    {{$t('Recieved')}}
                    -
                    <span
                      class="bold"
                    >{{currentUser.currency}} {{formatNumber((infos.payment_sent?infos.payment_sent:0),2)}}</span>
                    {{$t('Sent')}})
                  </p>
                </div>
              </div>
            </b-col>
            <!-- /.col -->
          </b-row>
        </b-col>
    </b-row>
  </div>
</template>


<script>
import NProgress from "nprogress";
import { mapActions, mapGetters } from "vuex";
import DateRangePicker from 'vue2-daterange-picker'
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'
import moment from 'moment'

export default {
  metaInfo: {
    title: "Profit & Loss"
  },
  components: { DateRangePicker },
  data() {
    return {
      isLoading: true,
      infos: [],
      today_mode: true,
      startDate: "", 
      endDate: "", 
      dateRange: { 
       startDate: "", 
       endDate: "" 
      }, 
      locale:{ 
          //separator between the two ranges apply
          Label: "Apply", 
          cancelLabel: "Cancel", 
          weekLabel: "W", 
          customRangeLabel: "Custom Range", 
          daysOfWeek: moment.weekdaysMin(), 
          //array of days - see moment documenations for details 
          monthNames: moment.monthsShort(), //array of month names - see moment documenations for details 
          firstDay: 1 //ISO first day of week - see moment documenations for details
        },
    };
  },

  computed: {
    ...mapGetters(["currentUser"])
  },

  methods: {
    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

  //----------------------------- Submit Date Picker -------------------\\
    Submit_filter_dateRange() {
      var self = this;
      self.startDate =  self.dateRange.startDate.toJSON().slice(0, 10);
      self.endDate = self.dateRange.endDate.toJSON().slice(0, 10);
      self.ProfitAndLoss();
    },


    get_data_loaded() {
      var self = this;
      if (self.today_mode) {
        let today = new Date()

        self.startDate = today.getFullYear();
        self.endDate = new Date().toJSON().slice(0, 10);

        self.dateRange.startDate = today.getFullYear();
        self.dateRange.endDate = new Date().toJSON().slice(0, 10);
        
      }
    },

    //----------------------------- Profit And Loss-------------------\\
    ProfitAndLoss() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.get_data_loaded();

      axios
        .get(
          "report/profit_and_loss?to=" +
            this.endDate +
            "&from=" +
            this.startDate
        )
        .then(response => {
          this.infos = response.data.data;
          // Complete the animation of theprogress bar.
          NProgress.done();
           this.isLoading = false;
          this.today_mode = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
           setTimeout(() => {
            this.isLoading = false;
            this.today_mode = false;
          }, 500);
        });
    },

  
  }, //end Methods

  //-----------------------------Autoload function-------------------\\

  created: function() {
    this.ProfitAndLoss();
  }
};
</script>