<template>
  <div class="main-content">
    <breadcumb :page="$t('Employee_Details')" :folder="$t('Employee')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-row v-if="!isLoading">
      <b-col md="12">
        <b-card class="card mb-30" header-bg-variant="transparent ">
          <b-tabs active-nav-item-class="nav nav-tabs" content-class="mt-3">
            <b-tab :title="$t('Basic_Information')">
              <validation-observer ref="Edit_Employee" v-if="!isLoading">
                <b-form @submit.prevent="Submit_Employee" enctype="multipart/form-data">
                  <b-row>
                    <b-col md="12">
                      <b-card>
                        <b-row>
                          <!-- FirstName -->
                          <b-col md="4" class="mb-2">
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
                                <b-form-invalid-feedback
                                  id="FirstName-feedback"
                                >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                              </b-form-group>
                            </validation-provider>
                          </b-col>

                          <!-- LastName -->
                          <b-col md="4" class="mb-2">
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
                                <b-form-invalid-feedback
                                  id="LastName-feedback"
                                >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                              </b-form-group>
                            </validation-provider>
                          </b-col>

                          <!-- Gender -->
                          <b-col md="4" class="mb-2">
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

                          <!-- Family_status -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Family_status')">
                              <v-select
                                v-model="employee.marital_status"
                                :reduce="label => label.value"
                                :placeholder="$t('Choose_Family_status')"
                                @input="Selected_Family_status"
                                :options="
                                [
                                   {label: 'Married', value: 'married'},
                                  {label: 'Single', value: 'single'},
                                  {label: 'Divorced', value: 'divorced'},
                                ]"
                              ></v-select>
                            </b-form-group>
                          </b-col>

                          <!-- Employment_type -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Employment_type')">
                              <v-select
                                v-model="employee.employment_type"
                                :reduce="label => label.value"
                                :placeholder="$t('Select_Employment_type')"
                                @input="Selected_Employment_type_Employee"
                                :options="
                                [
                                  {label: 'Full-time', value: 'full_time'},
                                  {label: 'Part-time', value: 'part_time'},
                                  {label: 'Self-employed', value: 'self_employed'},
                                  {label: 'Freelance', value: 'freelance'},
                                  {label: 'Contract', value: 'contract'},
                                  {label: 'Internship', value: 'internship'},
                                  {label: 'Apprenticeship', value: 'apprenticeship'},
                                  {label: 'Seasonal', value: 'seasonal'},
                                ]"
                              ></v-select>
                            </b-form-group>
                          </b-col>

                          <!-- Birth_date  -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Birth_date')">
                              <Datepicker
                                id="birth_date"
                                name="birth_date"
                                :placeholder="$t('Enter_Birth_date')"
                                v-model="employee.birth_date"
                                input-class="form-control back_important"
                                format="yyyy-MM-dd"
                                @closed="employee.birth_date=formatDate(employee.birth_date)"
                              ></Datepicker>
                            </b-form-group>
                          </b-col>

                          <!-- Email_Address -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Email_Address')">
                              <b-form-input
                                label="Email_Address"
                                :placeholder="$t('Enter_email_address')"
                                v-model="employee.email"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- country -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Country')">
                              <b-form-input
                                label="country"
                                :placeholder="$t('Enter_Country')"
                                v-model="employee.country"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- City -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('City')">
                              <b-form-input
                                label="City"
                                :placeholder="$t('Enter_City')"
                                v-model="employee.city"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- Province -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Province')">
                              <b-form-input
                                label="Province"
                                :placeholder="$t('Enter_Province')"
                                v-model="employee.province"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- Address -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Adress')">
                              <b-form-input
                                label="Address"
                                :placeholder="$t('Enter_Address')"
                                v-model="employee.address"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- Zip_code -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Zip_code')">
                              <b-form-input
                                label="zipcode"
                                :placeholder="$t('Enter_Zip_code')"
                                v-model="employee.zipcode"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- phone -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Phone')">
                              <b-form-input
                                label="phone"
                                :placeholder="$t('Enter_Phone_Number')"
                                v-model="employee.phone"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- joining_date  -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('joining_date')">
                              <Datepicker
                                id="joining_date"
                                name="joining_date"
                                :placeholder="$t('Enter_joining_date')"
                                v-model="employee.joining_date"
                                input-class="form-control back_important"
                                format="yyyy-MM-dd"
                                @closed="employee.joining_date=formatDate(employee.joining_date)"
                              ></Datepicker>
                            </b-form-group>
                          </b-col>

                          <!-- Leaving_Date  -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Leaving_Date')">
                              <Datepicker
                                id="leaving_date"
                                name="leaving_date"
                                :placeholder="$t('Enter_Leaving_Date')"
                                v-model="employee.leaving_date"
                                input-class="form-control back_important"
                                format="yyyy-MM-dd"
                                @closed="employee.leaving_date=formatDate(employee.leaving_date)"
                              ></Datepicker>
                            </b-form-group>
                          </b-col>

                          <!-- Annual_Leave -->
                          <b-col md="4" class="mb-2">
                            <validation-provider
                              name="total_leave"
                              :rules="{required:true}"
                              v-slot="validationContext"
                            >
                              <b-form-group :label="$t('Annual_Leave') + ' ' + '*'">
                                <b-form-input
                                  :state="getValidationState(validationContext)"
                                  aria-describedby="total_leave-feedback"
                                  label="total_leave"
                                  :placeholder="$t('Enter_Annual_Leave')"
                                  v-model="employee.total_leave"
                                ></b-form-input>
                                <b-form-invalid-feedback
                                  id="total_leave-feedback"
                                >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                              </b-form-group>
                            </validation-provider>
                          </b-col>

                          <!-- Remaining_leave -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Remaining_leave')">
                              <b-form-input
                                disabled="disabled"
                                label="remaining_leave"
                                v-model="employee.remaining_leave"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- Company -->
                          <b-col md="4" class="mb-2">
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
                          <b-col md="4" class="mb-2">
                            <validation-provider name="Department" :rules="{ required: true}">
                              <b-form-group
                                slot-scope="{ valid, errors }"
                                :label="$t('Department') + ' ' + '*'"
                              >
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
                          <b-col md="4" class="mb-2">
                            <validation-provider name="Designation" :rules="{ required: true}">
                              <b-form-group
                                slot-scope="{ valid, errors }"
                                :label="$t('Designation') + ' ' + '*'"
                              >
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
                          <b-col md="4" class="mb-2">
                            <validation-provider name="Office_Shift" :rules="{ required: true}">
                              <b-form-group
                                slot-scope="{ valid, errors }"
                                :label="$t('Office_Shift') + ' ' + '*'"
                              >
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

                          <!-- Hourly_rate -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Hourly_rate')">
                              <b-form-input
                                label="Hourly_rate"
                                :placeholder="$t('Enter_Hourly_rate')"
                                v-model="employee.hourly_rate"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>

                          <!-- Basic_salary -->
                          <b-col md="4" class="mb-2">
                            <b-form-group :label="$t('Basic_salary')">
                              <b-form-input
                                label="Basic_salary"
                                :placeholder="$t('Enter_Basic_salary')"
                                v-model="employee.basic_salary"
                              ></b-form-input>
                            </b-form-group>
                          </b-col>
                        </b-row>
                      </b-card>
                    </b-col>
                    <b-col md="12" class="mt-3">
                      <b-button
                        variant="primary"
                        type="submit"
                        :disabled="SubmitProcessing"
                      >{{$t('submit')}}</b-button>
                      <div v-once class="typo__p" v-if="SubmitProcessing">
                        <div class="spinner sm spinner-primary mt-3"></div>
                      </div>
                    </b-col>
                  </b-row>
                </b-form>
              </validation-observer>
            </b-tab>

            <b-tab :title="$t('Social_Media')">
              <b-form @submit.prevent="Update_Employee_social">
                <b-row>
                  <b-col md="12">
                    <b-card>
                      <b-row>
                        <!-- Skype -->
                        <b-col md="4" class="mb-2">
                          <b-form-group :label="$t('Skype')">
                            <b-form-input
                              label="Skype"
                              :placeholder="$t('Enter_Skype')"
                              v-model="employee.skype"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>

                        <!-- Facebook -->
                        <b-col md="4" class="mb-2">
                          <b-form-group :label="$t('Facebook')">
                            <b-form-input
                              label="Facebook"
                              :placeholder="$t('Enter_Facebook')"
                              v-model="employee.facebook"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>

                        <!-- WhatsApp -->
                        <b-col md="4" class="mb-2">
                          <b-form-group :label="$t('WhatsApp')">
                            <b-form-input
                              label="WhatsApp"
                              :placeholder="$t('Enter_WhatsApp')"
                              v-model="employee.whatsapp"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>

                        <!-- LinkedIn -->
                        <b-col md="4" class="mb-2">
                          <b-form-group :label="$t('LinkedIn')">
                            <b-form-input
                              label="LinkedIn"
                              :placeholder="$t('Enter_LinkedIn')"
                              v-model="employee.linkedin"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>

                        <!-- Twitter -->
                        <b-col md="4" class="mb-2">
                          <b-form-group :label="$t('Twitter')">
                            <b-form-input
                              label="Twitter"
                              :placeholder="$t('Enter_Twitter')"
                              v-model="employee.twitter"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>
                      </b-row>
                    </b-card>
                  </b-col>
                  <b-col md="12" class="mt-3">
                    <b-button
                      variant="primary"
                      type="submit"
                      :disabled="Submit_Processing_social"
                    >{{$t('submit')}}</b-button>
                    <div v-once class="typo__p" v-if="Submit_Processing_social">
                      <div class="spinner sm spinner-primary mt-3"></div>
                    </div>
                  </b-col>
                </b-row>
              </b-form>
            </b-tab>

            <!-- Experiences Table -->
            <b-tab :title="$t('Experiences')">
              <vue-good-table
                mode="remote"
                :columns="columns_experiences"
                :totalRows="totalRows_experiences"
                :rows="experiences"
                @on-page-change="PageChange_experiences"
                @on-per-page-change="onPerPageChange_experiences"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
                <div slot="table-actions" class="mt-2 mb-3">
                  <b-button
                    @click="New_Experience()"
                    class="btn-rounded"
                    variant="btn btn-primary btn-icon m-1"
                  >
                    <i class="i-Add"></i>
                    {{$t('Add')}}
                  </b-button>
                </div>
                <template slot="table-row" slot-scope="props">
                  <span v-if="props.column.field == 'actions'">
                    <a @click="Edit_Experience(props.row)" title="Edit" v-b-tooltip.hover>
                      <i class="i-Edit text-25 text-success"></i>
                    </a>
                    <a title="Delete" v-b-tooltip.hover @click="Remove_Experience(props.row.id)">
                      <i class="i-Close-Window text-25 text-danger"></i>
                    </a>
                  </span>
                </template>
              </vue-good-table>
            </b-tab>

            <!-- accounts_bank Table -->
            <b-tab :title="$t('bank_account')">
              <vue-good-table
                mode="remote"
                :columns="columns_accounts"
                :totalRows="totalRows_accounts"
                :rows="accounts_bank"
                @on-page-change="PageChange_accounts"
                @on-per-page-change="onPerPageChange_accounts"
                :pagination-options="{
                  enabled: true,
                  mode: 'records',
                  nextLabel: 'next',
                  prevLabel: 'prev',
                }"
                styleClass="tableOne table-hover vgt-table"
              >
                <div slot="table-actions" class="mt-2 mb-3">
                  <b-button
                    @click="New_Account()"
                    class="btn-rounded"
                    variant="btn btn-primary btn-icon m-1"
                  >
                    <i class="i-Add"></i>
                    {{$t('Add')}}
                  </b-button>
                </div>
                <template slot="table-row" slot-scope="props">
                  <span v-if="props.column.field == 'actions'">
                    <a @click="Edit_Account(props.row)" title="Edit" v-b-tooltip.hover>
                      <i class="i-Edit text-25 text-success"></i>
                    </a>
                    <a title="Delete" v-b-tooltip.hover @click="Remove_Account(props.row.id)">
                      <i class="i-Close-Window text-25 text-danger"></i>
                    </a>
                  </span>
                </template>
              </vue-good-table>
            </b-tab>
          </b-tabs>
        </b-card>
      </b-col>

      <!-- Modal_Experience -->
      <validation-observer ref="Create_Experience">
        <b-modal
          hide-footer
          size="lg"
          id="Experience_Modal"
          :title="edit_mode_experience?$t('Edit'):$t('Add')"
        >
          <b-form @submit.prevent="Submit_Experience">
            <b-row>
              <!-- Title -->
              <b-col md="6" class="mb-2">
                <validation-provider
                  name="Title"
                  :rules="{required:true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('title') + ' ' + '*'">
                    <b-form-input
                      :state="getValidationState(validationContext)"
                      aria-describedby="Title-feedback"
                      label="Title"
                      :placeholder="$t('Enter_title')"
                      v-model="experience.title"
                    ></b-form-input>
                    <b-form-invalid-feedback id="Title-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Company_Name -->
              <b-col md="6" class="mb-2">
                <validation-provider
                  name="Company_Name"
                  :rules="{required:true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Company_Name') + ' ' + '*'">
                    <b-form-input
                      :state="getValidationState(validationContext)"
                      aria-describedby="Company_Name-feedback"
                      label="Company_Name"
                      :placeholder="$t('Enter_Company_Name')"
                      v-model="experience.company_name"
                    ></b-form-input>
                    <b-form-invalid-feedback
                      id="Company_Name-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Location -->
              <b-col md="6" class="mb-2">
                <b-form-group :label="$t('Location')">
                  <b-form-input
                    label="Location"
                    :placeholder="$t('Enter_location')"
                    v-model="experience.location"
                  ></b-form-input>
                </b-form-group>
              </b-col>

              <!-- start date -->
              <b-col md="6">
                <validation-provider
                  name="start_date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('start_date') + ' ' + '*'">
                    <Datepicker
                      id="start_date"
                      name="start_date"
                      :placeholder="$t('Enter_Start_date')"
                      v-model="experience.start_date"
                      input-class="form-control back_important"
                      format="yyyy-MM-dd"
                      @closed="experience.start_date=formatDate(experience.start_date)"
                    ></Datepicker>
                    <b-form-invalid-feedback
                      id="start_date-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- end date -->
              <b-col md="6">
                <validation-provider
                  name="Finish_Date"
                  :rules="{ required: true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Finish_Date') + ' ' + '*'">
                    <Datepicker
                      id="end_date"
                      name="end_date"
                      :placeholder="$t('Enter_Finish_date')"
                      v-model="experience.end_date"
                      input-class="form-control back_important"
                      format="yyyy-MM-dd"
                      @closed="experience.end_date=formatDate(experience.end_date)"
                    ></Datepicker>
                    <b-form-invalid-feedback
                      id="end_date-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Employment_type -->
              <b-col lg="6" md="6" sm="12" class="mb-2">
                <validation-provider name="Status" :rules="{ required: true}">
                  <b-form-group slot-scope="{ valid, errors }" :label="$t('Employment_type') + ' ' + '*'">
                    <v-select
                      :class="{'is-invalid': !!errors.length}"
                      :state="errors[0] ? false : (valid ? true : null)"
                      v-model="experience.employment_type"
                      :reduce="label => label.value"
                      :placeholder="$t('Select_Employment_type')"
                      @input="Selected_Employment_type"
                      :options="
                            [
                              {label: 'Full-time', value: 'full_time'},
                              {label: 'Part-time', value: 'part_time'},
                              {label: 'Self-employed', value: 'self_employed'},
                              {label: 'Freelance', value: 'freelance'},
                              {label: 'Contract', value: 'contract'},
                              {label: 'Internship', value: 'internship'},
                              {label: 'Apprenticeship', value: 'apprenticeship'},
                              {label: 'Seasonal', value: 'seasonal'},
                            ]"
                    ></v-select>
                    <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Description -->
              <b-col md="12">
                <b-form-group :label="$t('Description')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Description')"
                    label="Description"
                    v-model="experience.description"
                  ></b-form-textarea>
                </b-form-group>
              </b-col>

              <b-col md="12" class="mt-3">
                <b-button
                  variant="primary"
                  type="submit"
                  :disabled="Submit_Processing_Experience"
                >{{$t('submit')}}</b-button>
                <div v-once class="typo__p" v-if="Submit_Processing_Experience">
                  <div class="spinner sm spinner-primary mt-3"></div>
                </div>
              </b-col>
            </b-row>
          </b-form>
        </b-modal>
      </validation-observer>

      <!-- Modal_Account -->
      <validation-observer ref="Create_Account">
        <b-modal
          hide-footer
          size="lg"
          id="Account_Modal"
          :title="edit_mode_account?$t('Edit'):$t('Add')"
        >
          <b-form @submit.prevent="Submit_Account">
            <b-row>
              <!-- Title -->
              <b-col md="6" class="mb-2">
                <validation-provider
                  name="Bank_Name"
                  :rules="{required:true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Bank_Name') + ' ' + '*'">
                    <b-form-input
                      :state="getValidationState(validationContext)"
                      aria-describedby="Bank_Name-feedback"
                      label="Bank_Name"
                      :placeholder="$t('Enter_Bank_Name')"
                      v-model="account_bank.bank_name"
                    ></b-form-input>
                    <b-form-invalid-feedback
                      id="Bank_Name-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Bank_Branch -->
              <b-col md="6" class="mb-2">
                <validation-provider
                  name="Bank_Branch"
                  :rules="{required:true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Bank_Branch') + ' ' + '*'">
                    <b-form-input
                      :state="getValidationState(validationContext)"
                      aria-describedby="Bank_Branch-feedback"
                      label="Bank_Branch"
                      :placeholder="$t('Enter_Bank_Branch')"
                      v-model="account_bank.bank_branch"
                    ></b-form-input>
                    <b-form-invalid-feedback
                      id="Bank_Branch-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Bank_Number -->
              <b-col md="6" class="mb-2">
                <validation-provider
                  name="Bank_Number"
                  :rules="{required:true}"
                  v-slot="validationContext"
                >
                  <b-form-group :label="$t('Bank_Number') + ' ' + '*'">
                    <b-form-input
                      :state="getValidationState(validationContext)"
                      aria-describedby="Bank_Number-feedback"
                      label="Bank_Number"
                      :placeholder="$t('Enter_Bank_Number')"
                      v-model="account_bank.account_no"
                    ></b-form-input>
                    <b-form-invalid-feedback
                      id="Bank_Number-feedback"
                    >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  </b-form-group>
                </validation-provider>
              </b-col>

              <!-- Description -->
              <b-col md="12">
                <b-form-group :label="$t('Please_provide_any_details')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Description')"
                    label="Description"
                    v-model="account_bank.note"
                  ></b-form-textarea>
                </b-form-group>
              </b-col>

              <b-col md="12" class="mt-3">
                <b-button
                  variant="primary"
                  type="submit"
                  :disabled="Submit_Processing_Bank"
                >{{$t('submit')}}</b-button>
                <div v-once class="typo__p" v-if="Submit_Processing_Bank">
                  <div class="spinner sm spinner-primary mt-3"></div>
                </div>
              </b-col>
            </b-row>
          </b-form>
        </b-modal>
      </validation-observer>
    </b-row>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import Datepicker from "vuejs-datepicker";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Details Employee"
  },
  components: {
    Datepicker
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing: false,
      Submit_Processing_social: false,

      Submit_Processing_Experience: false,
      edit_mode_experience: false,
      totalRows_experiences: "",
      limit_experiences: "10",
      experience_page: 1,

      Submit_Processing_Bank: false,
      edit_mode_account: false,
      totalRows_accounts: "",
      limit_accounts: "10",
      account_page: 1,

      data: new FormData(),
      experiences: [],
      companies: [],
      departments: [],
      designations: [],
      office_shifts: [],
      roles: {},
      employee: {},
      experience: {
        title: "",
        company_name: "",
        employment_type: "",
        location: "",
        start_date: "",
        end_date: "",
        description: ""
      },

      account_bank: {
        bank_name: "",
        bank_branch: "",
        account_no: "",
        note: ""
      }
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    columns_experiences() {
      return [
        {
          label: this.$t("title"),
          field: "title",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Company"),
          field: "company_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("start_date"),
          field: "start_date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Finish_Date"),
          field: "end_date",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
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
    },
    columns_accounts() {
      return [
        {
          label: this.$t("Bank_Name"),
          field: "bank_name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Bank_Branch"),
          field: "bank_branch",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Bank_Number"),
          field: "account_no",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
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
    //------------- Submit Validation Edit Employee
    Submit_Employee() {
      this.$refs.Edit_Employee.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Edit_Employee();
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

    //------ Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    formatDate(d) {
      var m1 = d.getMonth() + 1;
      var m2 = m1 < 10 ? "0" + m1 : m1;
      var d1 = d.getDate();
      var d2 = d1 < 10 ? "0" + d1 : d1;
      return [d.getFullYear(), m2, d2].join("-");
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

    Selected_Gender(value) {
      if (value === null) {
        this.employee.gender = "";
      }
    },

    Selected_Family_status(value) {
      if (value === null) {
        this.employee.marital_status = "";
      }
    },

    Selected_Employment_type_Employee(value) {
      if (value === null) {
        this.employee.employment_type = "";
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

    //------------------------------ Show Details -------------------------\\
    Get_Details() {
      let id = this.$route.params.id;
      axios
        .get(`employees/${id}`)
        .then(response => {
          this.employee = response.data.employee;
          this.companies = response.data.companies;
          this.departments = response.data.departments;
          this.designations = response.data.designations;
          this.office_shifts = response.data.office_shifts;

          this.isLoading = false;
        })
        .catch(response => {
          this.isLoading = false;
        });
    },

    //------------------------------ Create new Employee ------------------------------\\
    Edit_Employee() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.SubmitProcessing = true;

      // Send Data with axios
      axios
        .put("employees/" + this.employee.id, {
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
          leaving_date: self.employee.leaving_date,
          marital_status: self.employee.marital_status,
          employment_type: self.employee.employment_type,
          city: self.employee.city,
          province: self.employee.province,
          address: self.employee.address,
          zipcode: self.employee.zipcode,
          hourly_rate: self.employee.hourly_rate,
          basic_salary: self.employee.basic_salary,
          total_leave: self.employee.total_leave
        })
        .then(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          self.SubmitProcessing = false;
          this.$router.push({ name: "employees_list" });
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.SubmitProcessing = false;
        });
    },

    //------------------------ Update Social Profile ---------------------------\\
    Update_Employee_social() {
      var self = this;
      self.Submit_Processing_social = true;
      axios
        .put("/update_social_profile/" + self.employee.id, {
          facebook: self.employee.facebook,
          skype: self.employee.skype,
          whatsapp: self.employee.whatsapp,
          twitter: self.employee.twitter,
          linkedin: self.employee.linkedin
        })
        .then(response => {
          self.Submit_Processing_social = false;
          this.$router.push({ name: "employees_list" });
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          self.Submit_Processing_social = false;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //--------------------------- Event Page Change -------------\\
    PageChange_experiences({ currentPage }) {
      if (this.experience_page !== currentPage) {
        this.Get_experiences(currentPage);
      }
    },

    //--------------------------- Limit Page experiences -------------\\
    onPerPageChange_experiences({ currentPerPage }) {
      if (this.limit_experiences !== currentPerPage) {
        this.limit_experiences = currentPerPage;
        this.Get_experiences(1);
      }
    },

    //--------------------------- Get_experiences by employee -------------\\
    Get_experiences(page) {
      axios
        .get(
          "get_experiences_by_employee?page=" +
            page +
            "&limit=" +
            this.limit_experiences +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.experiences = response.data.experiences;
          this.totalRows = response.data.totalRows;
        })
        .catch(response => {});
    },

    //------------------------------ Show Modal (Create Experience) -------------------------------\\
    New_Experience() {
      this.reset_Form_experience();
      this.edit_mode_experience = false;
      this.$bvModal.show("Experience_Modal");
    },

    //------------------------------ Show Modal (Edit Experience) -------------------------------\\
    Edit_Experience(experience) {
      this.edit_mode_experience = true;
      this.reset_Form_experience();
      this.experience = experience;
      this.$bvModal.show("Experience_Modal");
    },

    Selected_Employment_type(value) {
      if (value === null) {
        this.experience.employment_type = "";
      }
    },

    //----------------------------- Reset_Form_experience---------------------------\\
    reset_Form_experience() {
      this.experience = {
        id: "",
        title: "",
        company_name: "",
        employment_type: "",
        location: "",
        start_date: "",
        end_date: "",
        description: ""
      };
    },

    //------------- Submit Validation Create & Edit Experience
    Submit_Experience() {
      this.$refs.Create_Experience.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.edit_mode_experience) {
            this.Create_Experience();
          } else {
            this.Update_Experience();
          }
        }
      });
    },

    //------------------------------- Create_Experience ------------------------\\
    Create_Experience() {
      var self = this;
      self.Submit_Processing_Experience = true;
      axios
        .post("work_experience", {
          title: self.experience.title,
          company_name: self.experience.company_name,
          employee_id: self.employee.id,
          location: self.experience.location,
          employment_type: self.experience.employment_type,
          start_date: self.experience.start_date,
          end_date: self.experience.end_date,
          description: self.experience.description
        })
        .then(response => {
          this.Submit_Processing_Experience = false;
          Fire.$emit("Event_experience");
          this.makeToast(
            "success",
            this.$t("Created_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.Submit_Processing_Experience = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Update_Experience ------------------------\\
    Update_Experience() {
      var self = this;
      self.Submit_Processing_Experience = true;
      axios
        .put("/work_experience/" + self.experience.id, {
          title: self.experience.title,
          company_name: self.experience.company_name,
          employee_id: self.employee.id,
          location: self.experience.location,
          employment_type: self.experience.employment_type,
          start_date: self.experience.start_date,
          end_date: self.experience.end_date,
          description: self.experience.description
        })
        .then(response => {
          this.Submit_Processing_Experience = false;
          Fire.$emit("Event_experience");
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.Submit_Processing_Experience = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Remove_Experience ------------------------\\
    Remove_Experience(id) {
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
            .delete("work_experience/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_experience");
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

    //--------------------------------------------- Bank Account -----------------------------------------------------------\\

    //--------------------------- Get_accounts by employee -------------\\
    Get_accounts(page) {
      axios
        .get(
          "get_accounts_by_employee?page=" +
            page +
            "&limit=" +
            this.limit_accounts +
            "&id=" +
            this.$route.params.id
        )
        .then(response => {
          this.accounts_bank = response.data.accounts_bank;
          this.totalRows_accounts = response.data.totalRows;
        })
        .catch(response => {});
    },

    //--------------------------- Event Page Change -------------\\
    PageChange_accounts({ currentPage }) {
      if (this.account_page !== currentPage) {
        this.Get_accounts(currentPage);
      }
    },

    //--------------------------- Limit Page accounts -------------\\
    onPerPageChange_accounts({ currentPerPage }) {
      if (this.limit_accounts !== currentPerPage) {
        this.limit_accounts = currentPerPage;
        this.Get_accounts(1);
      }
    },

    //------------- Submit Validation Create & Edit Account
    Submit_Account() {
      this.$refs.Create_Account.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.edit_mode_account) {
            this.Create_Account();
          } else {
            this.Update_Account();
          }
        }
      });
    },

    //------------------------------ Show Modal (Create Bank Account) -------------------------------\\

    New_Account() {
      this.reset_Form_bank_account();
      this.edit_mode_account = false;
      this.$bvModal.show("Account_Modal");
    },

    //------------------------------ Show Modal (Edit Bank Account) -------------------------------\\

    Edit_Account(account_bank) {
      this.edit_mode_account = true;
      this.reset_Form_bank_account();
      this.account_bank = account_bank;
      this.$bvModal.show("Account_Modal");
    },

    //----------------------------- Reset_Form_Bank Account---------------------------\\

    reset_Form_bank_account() {
      this.account_bank = {
        id: "",
        bank_name: "",
        bank_branch: "",
        account_no: "",
        note: ""
      };
    },

    //------------------------------- Create Bank Account ------------------------\\
    Create_Account() {
      var self = this;
      self.Submit_Processing_Bank = true;
      axios
        .post("/employee_account", {
          employee_id: self.employee.id,
          bank_name: self.account_bank.bank_name,
          bank_branch: self.account_bank.bank_branch,
          account_no: self.account_bank.account_no,
          note: self.account_bank.note
        })
        .then(response => {
          this.Submit_Processing_Bank = false;
          Fire.$emit("Event_account");
          this.makeToast(
            "success",
            this.$t("Created_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.Submit_Processing_Bank = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Update Bank Account ------------------------\\
    Update_Account() {
      var self = this;
      self.Submit_Processing_Bank = true;
      axios
        .put("/employee_account/" + self.account_bank.id, {
          employee_id: self.employee.id,
          bank_name: self.account_bank.bank_name,
          bank_branch: self.account_bank.bank_branch,
          account_no: self.account_bank.account_no,
          note: self.account_bank.note
        })
        .then(response => {
          this.Submit_Processing_Bank = false;
          Fire.$emit("Event_account");
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.Submit_Processing_Bank = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------- Remove_Account ------------------------\\
    Remove_Account(id) {
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
            .delete("employee_account/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("Delete_account");
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
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_Details();
    this.Get_experiences(1);
    this.Get_accounts(1);

    Fire.$on("Event_experience", () => {
      setTimeout(() => {
        this.Get_experiences(1);
        this.$bvModal.hide("Experience_Modal");
      }, 500);
    });

    Fire.$on("Delete_experience", () => {
      setTimeout(() => {
        this.Get_experiences(1);
      }, 500);
    });

    Fire.$on("Event_account", () => {
      setTimeout(() => {
        this.Get_accounts(1);
        this.$bvModal.hide("Account_Modal");
      }, 500);
    });

    Fire.$on("Delete_account", () => {
      setTimeout(() => {
        this.Get_accounts(1);
      }, 500);
    });
  }
};
</script>