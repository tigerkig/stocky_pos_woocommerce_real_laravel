<template>
  <div class="main-content">
    <breadcumb :page="$t('EditSaleReturn')" :folder="$t('ListReturns')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="edit_sale_return" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Sale_return">
        <b-row>
          <b-col lg="12" md="12" sm="12">
            <b-card>
              <b-row>
                <!-- date  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="date"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('date') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="date-feedback"
                        type="date"
                        v-model="sale_return.date"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Sale -->
               <b-col lg="4" md="4" sm="12" class="mb-3">
                  <b-form-group :label="$t('Sale')">
                    <b-form-input
                      label="Sale"
                      v-model="sale_return.sale_ref"
                      disabled="disabled"
                    ></b-form-input>
                  </b-form-group>
                </b-col>

                 <!-- Status  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider name="Status" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Status') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="sale_return.statut"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Status')"
                        :options="
                                [
                                  {label: 'Received', value: 'received'},
                                  {label: 'Pending', value: 'pending'}
                                ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- details product  -->
                <b-col md="12">
                  <h5>{{$t('list_product_returns')}} *</h5>
                  <div class="alert alert-danger">{{$t('products_refunded_alert')}}</div>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="bg-gray-300">
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">{{$t('ProductName')}}</th>
                          <th scope="col">{{$t('Net_Unit_Price')}}</th>
                          <th scope="col">{{$t('Quantity_sold')}}</th>
                          <th scope="col">{{$t('Qty_return')}}</th>
                          <th scope="col">{{$t('Discount')}}</th>
                          <th scope="col">{{$t('Tax')}}</th>
                          <th scope="col">{{$t('SubTotal')}}</th>
                       
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="details.length <=0">
                          <td colspan="9">{{$t('NodataAvailable')}}</td>
                        </tr>
                        <tr
                          v-for="detail in details"
                          :class="{'row_deleted': detail.del === 1 || detail.no_unit === 0}"
                          :key="detail.detail_id"
                        >
                          <td>{{detail.detail_id}}</td>
                          <td>
                            <span>{{detail.code}}</span>
                            <br>
                            <span class="badge badge-success">{{detail.name}}</span>
                            
                          </td>
                          <td>{{currentUser.currency}} {{formatNumber(detail.Net_price, 3)}}</td>
                          <td>
                            <span
                              class="badge badge-outline-warning"
                            >{{detail.sale_quantity}} {{detail.unitSale}}</span>
                          </td>
                          <td>
                            <div class="quantity">
                              <b-input-group>
                                <b-input-group-prepend>
                                  <span  v-show="detail.no_unit !== 0"
                                    class="btn btn-primary btn-sm"
                                    @click="decrement(detail ,detail.detail_id)"
                                  >-</span>
                                </b-input-group-prepend>
                                <input
                                  class="form-control"
                                  @keyup="Verified_Qty(detail,detail.detail_id)"
                                  :min="0.00"
                                  v-model.number="detail.quantity"
                                  :disabled="detail.del === 1 || detail.no_unit === 0"
                                >
                                <b-input-group-append>
                                  <span  v-show="detail.no_unit !== 0"
                                    class="btn btn-primary btn-sm"
                                    @click="increment(detail ,detail.detail_id)"
                                  >+</span>
                                </b-input-group-append>
                              </b-input-group>
                            </div>
                          </td>
                          <td>{{currentUser.currency}} {{formatNumber(detail.DiscountNet * detail.quantity, 2)}}</td>
                          <td>{{currentUser.currency}} {{formatNumber(detail.taxe * detail.quantity, 2)}}</td>
                          <td>{{currentUser.currency}} {{detail.subtotal.toFixed(2)}}</td>
                          <!-- <td v-show="detail.no_unit !== 0">
                            <i @click="delete_Product_Detail(detail.detail_id)" class="i-Close-Window text-25 text-danger"></i>
                          </td> -->
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <div class="offset-md-9 col-md-3 mt-4">
                  <table class="table table-striped table-sm">
                    <tbody>
                      <tr>
                        <td class="bold">{{$t('OrderTax')}}</td>
                        <td>
                          <span>{{currentUser.currency}} {{sale_return.TaxNet.toFixed(2)}} ({{formatNumber(sale_return.tax_rate ,2)}} %)</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="bold">{{$t('Discount')}}</td>
                        <td>{{currentUser.currency}} {{sale_return.discount.toFixed(2)}}</td>
                      </tr>
                      <tr>
                        <td class="bold">{{$t('Shipping')}}</td>
                        <td>{{currentUser.currency}} {{sale_return.shipping.toFixed(2)}}</td>
                      </tr>
                      <tr>
                        <td>
                          <span class="font-weight-bold">{{$t('Total')}}</span>
                        </td>
                        <td>
                          <span
                            class="font-weight-bold"
                          >{{currentUser.currency}} {{GrandTotal.toFixed(2)}}</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                 <!-- Order Tax  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="Order Tax"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('OrderTax')">
                      <b-input-group append="%">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          label="Order Tax"
                          v-model.number="sale_return.tax_rate"
                          @keyup="keyup_OrderTax()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Discount -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="Discount"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Discount')">
                      <b-input-group :append="currentUser.currency">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Discount-feedback"
                          label="Discount"
                          v-model.number="sale_return.discount"
                          @keyup="keyup_Discount()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="Discount-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Shipping  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="Shipping"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Shipping')">
                      <b-input-group :append="currentUser.currency">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Shipping-feedback"
                          label="Shipping"
                          v-model.number="sale_return.shipping"
                          @keyup="keyup_Shipping()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="Shipping-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <b-col md="12">
                  <b-form-group :label="$t('Please_provide_any_details')">
                    <textarea
                      v-model="sale_return.notes"
                      rows="4"
                      class="form-control"
                      :placeholder="$t('Afewwords')"
                    ></textarea>
                  </b-form-group>
                </b-col>
                <b-col md="12">
                  <b-form-group>
                    <b-button variant="primary" @click="Submit_Sale_return" :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
                    <div v-once class="typo__p" v-if="SubmitProcessing">
                      <div class="spinner sm spinner-primary mt-3"></div>
                    </div>
                  </b-form-group>
                </b-col>
              </b-row>
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
    title: "Edit Return"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing:false,
      details: [],
      detail: {},
      sale_return: {
        id: "",
        date: "",
        notes: "",
        statut: "",
        client_id: "",
        warehouse_id: "",
        sale_id:"",
        tax_rate: 0,
        TaxNet: 0,
        shipping: 0,
        discount: 0
      },
      total: 0,
      GrandTotal: 0,
      
    };
  },

  computed: {
    ...mapGetters(["currentUser"])
  },

  methods: {

    //--- Submit Validate Update Sale Return
    Submit_Sale_return() {
      this.$refs.edit_sale_return.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Update_Return();
        }
      });
    },
  
    //---Validate State Fields
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //-----------------------------------Verified QTY ------------------------------\\
     Verified_Qty(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (isNaN(detail.quantity)) {
            this.details[i].quantity = 1;
          }

          if (detail.quantity > detail.sale_quantity) {
            this.makeToast("warning", this.$t("qty_return_is_greater_than_qty_sold"), this.$t("Warning"));
            this.details[i].quantity = detail.sale_quantity;
          } else {
            this.details[i].quantity = detail.quantity;
          }

          this.Calcul_Total();
          this.$forceUpdate();
        }
      }
    },

    //-----------------------------------increment QTY ------------------------------\\

     increment(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity + 1 > detail.sale_quantity) {
            this.makeToast("warning", this.$t("qty_return_is_greater_than_qty_sold"), this.$t("Warning"));
          } else {
            this.formatNumber(this.details[i].quantity++, 2);
          }
        }
      }
      this.$forceUpdate();
      this.Calcul_Total();
    },

    //-----------------------------------decrement QTY ------------------------------\\

     decrement(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity - 1 > 0) {
            if (detail.quantity - 1 > detail.sale_quantity) {
              this.makeToast(
                "warning",
                this.$t("qty_return_is_greater_than_qty_sold"),
                this.$t("Warning")
              );
            } else {
              this.formatNumber(this.details[i].quantity--, 2);
            }
          }
        }
      }
      this.$forceUpdate();
      this.Calcul_Total();
    },

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

    //-----------------------------------------Calcul Total ------------------------------\\
    Calcul_Total() {
      this.total = 0;
      for (var i = 0; i < this.details.length; i++) {
        var tax = this.details[i].taxe * this.details[i].quantity;
        this.details[i].subtotal = parseFloat(
          this.details[i].quantity * this.details[i].Net_price + tax
        );
        this.total = parseFloat(this.total + this.details[i].subtotal);
      }

      const total_without_discount = parseFloat(
        this.total - this.sale_return.discount
      );
      this.sale_return.TaxNet = parseFloat(
        (total_without_discount * this.sale_return.tax_rate) / 100
      );
      this.GrandTotal = parseFloat(
        total_without_discount +
          this.sale_return.TaxNet +
          this.sale_return.shipping
      );

      var grand_total =  this.GrandTotal.toFixed(2);
      this.GrandTotal = parseFloat(grand_total);
    },

    //---------- keyup OrderTax
    keyup_OrderTax() {
      if (isNaN(this.sale_return.tax_rate)) {
        this.sale_return.tax_rate = 0;
      } else if(this.sale_return.tax_rate == ''){
         this.sale_return.tax_rate = 0;
        this.Calcul_Total();
      }else {
        this.Calcul_Total();
      }
    },

    //---------- keyup Discount

    keyup_Discount() {
      if (isNaN(this.sale_return.discount)) {
        this.sale_return.discount = 0;
       } else if(this.sale_return.discount == ''){
         this.sale_return.discount = 0;
        this.Calcul_Total();
      }else {
        this.Calcul_Total();
      }
    },

    //---------- keyup Shipping

    keyup_Shipping() {
      if (isNaN(this.sale_return.shipping)) {
        this.sale_return.shipping = 0;
       } else if(this.sale_return.shipping == ''){
         this.sale_return.shipping = 0;
        this.Calcul_Total();
      }else {
        this.Calcul_Total();
      }
    },

    //-----------------------------------Delete Detail Product ------------------------------\\
    // delete_Product_Detail(id) {
    //   for (var i = 0; i < this.details.length; i++) {
    //     if (id === this.details[i].detail_id) {
    //       this.details.splice(i, 1);
    //       this.Calcul_Total();
    //     }
    //   }
    // },

    //-----------------------------------verified Qty If Null || 0 ------------------------------\\

    verifiedForm() {
      if (this.details.length <= 0) {
        this.makeToast(
          "warning",
          this.$t("AddProductToList"),
          this.$t("Warning")
        );
        return false;
      } else {
        var count = 0;
        for (var i = 0; i < this.details.length; i++) {
          if (
            this.details[i].quantity != "" ||
            this.details[i].quantity !== 0
          ) {
            count += 1;
          }
         
        }

        if (count === 0) {
          this.makeToast("warning", this.$t("Please_add_return_quantity"), this.$t("Warning"));

          return false;
        } else {
          return true;
        }
      }
    },

    //--------------------------------- Update Return -------------------------\\
    Update_Return() {
      if (this.verifiedForm()) {
        this.SubmitProcessing = true;
        NProgress.start();
        NProgress.set(0.1);
        let id = this.$route.params.id;
        axios
          .put(`returns/sale/${id}`, {
            date: this.sale_return.date,
            client_id: this.sale_return.client_id,
            sale_id: this.sale_return.sale_id,
            warehouse_id: this.sale_return.warehouse_id,
            statut: this.sale_return.statut,
            notes: this.sale_return.notes,
            tax_rate: this.sale_return.tax_rate?this.sale_return.tax_rate:0,
            TaxNet: this.sale_return.TaxNet?this.sale_return.TaxNet:0,
            discount: this.sale_return.discount?this.sale_return.discount:0,
            shipping: this.sale_return.shipping?this.sale_return.shipping:0,
            GrandTotal: this.GrandTotal,
            details: this.details
          })
          .then(response => {
            NProgress.done();
            this.makeToast(
              "success",
              this.$t("Update.TitleReturn"),
              this.$t("Success")
            );

            this.SubmitProcessing = false;
            this.$router.push({ name: "index_sale_return" });
          })
          .catch(error => {
            NProgress.done();
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
            this.SubmitProcessing = false;
          });
      }
    },

    //---------------------------------------Get Elements ------------------------------\\
    GetElements() {
      let id = this.$route.params.id;
      let sale_id = this.$route.params.sale_id;
      axios
        .get(`returns/sale/edit_sell_return/${id}/${sale_id}`)
        .then(response => {
          this.sale_return = response.data.sale_return;
          this.details = response.data.details;
          this.Calcul_Total();
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    }
  },

  //----------------------------- Created function-------------------
  created() {
    this.GetElements();
  }
};
</script>