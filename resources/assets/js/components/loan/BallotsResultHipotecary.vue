<template>
  <v-container fluid class="py-0">
    <ValidationObserver ref="observer">
      <v-form>
        <!--v-card-->
          <v-row justify="center">
            <v-col cols="3" class="py-2" v-show="edit_parent_sismu || (($route.params.hash == 'remake' && data_loan_parent_aux.parent_reason != null))">
               <v-text-field
                class="py-0"
                dense
                :outlined="enable_sismu_field"
                :readonly="!enable_sismu_field"
                label="Código de Préstamo Padre"
                v-model="data_loan_parent_aux.code"
              ></v-text-field>
          </v-col>
          <v-col cols="3" class="py-2" v-show="edit_parent_sismu || (($route.params.hash == 'remake' && data_loan_parent_aux.parent_reason != null))">
            <ValidationProvider v-slot="{ errors }" name="monto" :rules="'required|numeric|min_value:'+loan_detail.minimun_amoun+'|max_value:'+loan_detail.maximun_amoun"  mode="aggressive">
              <v-text-field
                :error-messages="errors"
                class="py-0"
                dense
                :outlined="enable_sismu_field"
                :readonly="!enable_sismu_field"
                label="Monto"
                v-model="data_loan_parent_aux.amount_approved"
              ></v-text-field>
            </ValidationProvider>
          </v-col>
          <v-col cols="2" class="py-2" v-show="edit_parent_sismu || (($route.params.hash == 'remake' && data_loan_parent_aux.parent_reason != null))">
            <ValidationProvider v-slot="{ errors }" name="plazo" :rules="'required|numeric|min_value:'+loan_detail.minimum_term+'|max_value:'+loan_detail.maximum_term" mode="aggressive">
              <v-text-field
                :error-messages="errors"
                class="py-0"
                dense
                :outlined="enable_sismu_field"
                :readonly="!enable_sismu_field"
                label="Plazo"
                v-model="data_loan_parent_aux.loan_term"
              ></v-text-field>
            </ValidationProvider>
          </v-col>
          <v-col cols="2" class="py-2" v-show="edit_parent_sismu || (($route.params.hash == 'remake' && data_loan_parent_aux.parent_reason != null))">
            <ValidationProvider v-slot="{ errors }" name="saldo" :rules="'required|min_value:0|max_value:'+calculator_result.amount_requested" mode="aggressive">
              <v-text-field
                :error-messages="errors"
                class="py-0"
                dense
                :outlined="enable_sismu_field"
                :readonly="!enable_sismu_field"
                label="Saldo"
                v-model="data_loan_parent_aux.balance"
              ></v-text-field>
            </ValidationProvider>
          </v-col>
          <v-col cols="2" class="py-2" v-show="edit_parent_sismu || (($route.params.hash == 'remake' && data_loan_parent_aux.parent_reason != null))">
              <v-text-field
                class="py-0"
                dense
                :outlined="enable_sismu_field"
                :readonly="!enable_sismu_field"
                label="Cuota"
                v-model="data_loan_parent_aux.estimated_quota"
              ></v-text-field>
          </v-col>
            <v-col cols="12" class="py-2">
              <v-container class="py-0">
                <v-row>
                  <slot name="title"></slot>
                  <br />
                  <v-col cols="12" md="3"  class="py-0">
                    <v-layout row wrap>
                      <v-flex xs12 class="px-1">
                        <fieldset class="pa-3">
                      <ValidationProvider v-slot="{ errors }" name="plazo" :rules="'numeric|min_value:'+loan_detail.minimum_term+'|max_value:'+loan_detail.maximum_term" mode="aggressive">
                      <v-text-field
                        :error-messages="errors"
                        label="Plazo en Meses"
                        v-model="calculator_result.months_term"
                        v-on:keyup.enter="simulator_hipotecary()"
                      ></v-text-field>
                      </ValidationProvider>
                      <ValidationProvider v-slot="{ errors }" name="monto solicitado" :rules="'numeric|min_value:'+loan_detail.minimun_amoun+'|max_value:'+loan_detail.net_realizable_value" mode="aggressive">
                      <v-text-field
                        dense
                        :error-messages="errors"
                        label="Monto Solicitado"
                        v-model ="calculator_result.amount_requested"
                        v-on:keyup.enter="simulator_hipotecary()"
                      ></v-text-field>
                      </ValidationProvider>
                         <center>
                          <v-btn
                            class="py-0 text-right"
                            color="info"
                            rounded
                            x-small
                            @click="simulator_hipotecary()">Calcular
                          </v-btn>
                        </center>
                        </fieldset>
                      </v-flex>
                    </v-layout>
                  </v-col>
                  <v-col cols="12" md="9" class="py-0">
                    <v-card-text class="py-0">
                      <v-layout row wrap>
                        <v-flex xs5 class="px-1">
                          <fieldset class="pa-2">
                              <v-toolbar-title>Datos del Préstamo</v-toolbar-title>
                                <v-progress-linear></v-progress-linear>
                                 <p class="py-0 mb-0 caption">Monto del Inmueble: {{loan_detail.net_realizable_value | money}} <br>
                                 Monto Solicitado: {{calculator_result.amount_requested | money}}<br>
                                 Interes Calculado Total: {{calculator_result.indebtedness_calculated_total|percentage }}%  <br> Liquido Calculado Total: {{calculator_result.liquid_qualification_calculated_total | money}}<br> Cuota Total del Préstamo: {{calculator_result.quota_calculated_estimated_total | money}}</p>
                                <p class="py-0 mb-0 caption" v-show="type_sismu">Cuota de refinanciamiento Sismu: {{ data_sismu.quota_sismu | money}}</p>
                          </fieldset>
                        </v-flex>
                         <v-flex xs7 class="px-1">
                          <fieldset class="pa-2">
                              <v-toolbar-title>Datos del Afiliado</v-toolbar-title>
                              <ul style="list-style: none" class="pa-0">
                              <li v-for="(liquido,i) in liquid_calificated" :key="i">
                                <v-progress-linear></v-progress-linear>
                                <h1 class="py-0 mb-0 caption">Nombre del Afiliado: {{lenders_aux[i]}}</h1>
                                <p class="py-0 mb-0 caption">Liquido Pagable:  {{liquido.payable_liquid_calculated | money}}<br>
                                Total de Bonos: {{liquido.bonus_calculated | money}} <b> {{liquido.livelihood_amount?'Cubre la Cuota ':'No Cubre la Cuota'}}</b></p>
                              </li>
                            </ul>
                          </fieldset>
                        </v-flex>
                      </v-layout>
                    </v-card-text>
                  </v-col>
                  <v-col cols="12" md="12" class="pl-1" >
                    <v-card-text class="py-0">
                      <v-layout row wrap>
                        <v-flex xs12 class="px-0">
                          <fieldset class="pa-3">
                            <v-toolbar-title>Calculo del porcentaje de pago del Préstamo</v-toolbar-title>
                              <ul style="list-style: none" class="pa-0">
                                <li v-for="(calculado,i) in calculator_result.affiliates" :key="i" >
                                  <v-progress-linear></v-progress-linear>
                                   <h5 class="py-0 mb-0 caption ">Nombre del Afiliado: {{lenders_aux[i]}}</h5>
                                  <p class="py-0 mb-0 caption">Liquido para Callificacion: {{calculado.liquid_qualification_calculated | money}} <b> | </b> Cuota Estimada: {{calculado.quota_calculated_estimated | money}} <b> | </b> Porcentaje de Pago: {{calculado.payment_percentage|percentage }}% </p>
                                </li>
                              </ul>
                          </fieldset>
                        </v-flex>
                      </v-layout>
                    </v-card-text>
                  </v-col>
                 </v-row>
              </v-container>
            </v-col>
          </v-row>
        <!--/v-card-->
      </v-form>
    </ValidationObserver>
  </v-container>
</template>
<script>
export default {
  name: "ballots-result-hipotecary",
  data: () => ({
     simulator:{},
  }),
  props: {
    data_sismu: {
      type: Object,
      required: true
    },
    calculator_result: {
      type: Object,
      required: true
    },
    modalidad: {
      type: Object,
      required: true
    },
    data_loan_parent_aux: {
      type: Object,
      required: true
    },
    loan_detail: {
      type: Object,
      required: true
    },
    lenders: {
      type: Array,
      required: true
    },
    lenders_aux: {
      type: Array,
      required: true
    },
    liquid_calificated: {
      type: Array,
      required: true
    }
  },
  computed: {
    type_sismu() {
      if(this.$route.query.type_sismu)
      {
        return true
      }
    },
    edit_parent_sismu() { //Visualiza los campos para editar los datos del refinanciamiento
      if(this.refinancing || this.reprogramming)
      {
        return true
      }
      else{
        return false
      }

    },
    enable_sismu_field() { //Habilita los campos para editar los datos del refinanciamiento
      if(this.$route.query.type_sismu)
      {
        return true
      }
      else{
        return false
      }
    },

    refinancing() {
      return this.$route.params.hash == 'refinancing'
    },
    reprogramming() {
      return this.$route.params.hash == 'reprogramming'
    }
  },
  methods: {
    //Metodo del simulador para el monto maximo de prestamo
    async simulator_hipotecary() {
      try {
       if( parseFloat(this.calculator_result.amount_requested)  <= parseFloat(this.loan_detail.net_realizable_value))
        {
          let res = await axios.post(`simulator`, {
          procedure_modality_id: this.modalidad.id,
          amount_requested: this.calculator_result.amount_requested,
          months_term: this.calculator_result.months_term,
          guarantor: false,
          liquid_qualification_calculated_lender: 0,
          liquid_calculated:this.liquid_calificated
        })
        this.simulator = res.data
        this.lenders=this.liquid_calificated
        if( this.simulator.amount_requested<this.calculator_result.amount_requested){
          this.calculator_result.amount_requested=this.simulator.amount_requested
          this.loan_detail.amount_requested=this.calculator_result.amount_requested
        }else{
          this.calculator_result.amount_requested=this.simulator.amount_requested
          this.loan_detail.amount_requested=this.simulator.amount_requested
        }
        this.calculator_result.indebtedness_calculated_total=this.simulator.indebtedness_calculated_total
        this.calculator_result.quota_calculated_estimated_total=this.simulator.quota_calculated_estimated_total
        this.loan_detail.months_term=this.calculator_result.months_term
        this.loan_detail.liquid_qualification_calculated=this.simulator.liquid_qualification_calculated_total
        this.loan_detail.indebtedness_calculated=this.simulator.indebtedness_calculated_total
        this.loan_detail.maximum_suggested_valid=this.simulator.maximum_suggested_valid
        this.loan_detail.is_valid=this.simulator.is_valid
        for (this.i = 0; this.i< this.lenders.length; this.i++){

          this.lenders[this.i].payment_percentage=this.simulator.affiliates[this.i].payment_percentage
          this.lenders[this.i].indebtedness_calculated=this.simulator.affiliates[this.i].indebtnes_calculated
          this.lenders[this.i].quota_treat=this.simulator.affiliates[this.i].quota_calculated_estimated
          this.calculator_result.affiliates[this.i].quota_calculated_estimated=this.simulator.affiliates[this.i].quota_calculated_estimated
        }

        }
        else{
          this.toastr.error("El Monto Solicitado no puede ser mayor al Monto del Inmueble")
        }
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
  }
}
</script>