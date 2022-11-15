<template>
  <v-container fluid class="py-0 px-0">
      <v-form>
        <!--BOTONES CUANDO SE REALICE LA EDICIÓN-->
        <v-row justify="center" >
          <v-col cols="12" class="py-0 px-0">
            <v-container fluid class="py-0 px-0  ">
              <v-row class="py-0">
                <v-col cols="12" class="py-0">
                  <v-card flat tile class="py-0">
                    <v-card-text class="py-0">


                      <!-- M E N Ú    V E R T I C A L   D A T O S -->
                      <v-tabs vertical>

                        <v-tab class="py-0">
                          <v-icon left>
                            mdi-account
                          </v-icon>
                          EVALUACION DEL PRESTATARIO
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-lock
                          </v-icon>
                          GARANTIA
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-access-point
                          </v-icon>
                          INFORMACION DEL TRAMITE
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-account-arrow-left
                          </v-icon>
                          PERSONA DE REFERENCIA
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-account-check
                          </v-icon>
                          PERSONA CODEUDORA
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-book-open-page-variant
                          </v-icon>
                          DOCUMENTOS PRESENTADOS
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-alert
                          </v-icon>
                          OBSERVACIONES DEL TRAMITE
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-monitor-cellphone
                          </v-icon>
                          HISTORIAL DEL TRAMITE
                        </v-tab>

                        <v-tab>
                          <v-icon left>
                            mdi-monitor-cellphone
                          </v-icon>
                          HISTORIAL DE PAGOS
                        </v-tab>


                        <!-- E V A L U A C I Ó N   D E   P R E S T A T A R I O -->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>

                              <v-row>
                                <v-col cols="12" md="12" class="py-0">
                                  <p style="color:teal"><b>EVALUACION DEL PRESTATARIO PARA ACCEDER AL PRESTAMO</b></p>
                                </v-col>
                                
                                <v-progress-linear></v-progress-linear>
                                <br>

                                <v-col cols="12" md="4" class="py-0">
                                   <p><b>MONTO SOLICITADO: </b> {{loan.amount_approved | moneyString}} Bs.</p>
                                </v-col>

                                <v-col class="py-0">
                                  <p><b>PLAZO EN MESES:</b>{{' '+loan.loan_term}}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0">
                                   <p><b>PROMEDIO LIQUIDO PAGABLE: </b> {{loan.borrower[0].payable_liquid_calculated | moneyString }} Bs.</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0" >
                                   <p><b>LIQUIDO PARA CALIFICACION: </b> {{loan.borrower[0].liquid_qualification_calculated | moneyString}} Bs.</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0">
                                   <p><b>TOTAL BONOS:</b> {{loan.borrower[0].bonus_calculated | moneyString}}</p>
                                </v-col>
                                
                                <v-col cols="12" md="4" class="py-0">
                                   <p><b>INDICE DE ENDEUDAMIENTO:</b> {{loan.borrower[0].indebtedness_calculated|percentage }}% </p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0">
                                   <p><b>CALCULO DE CUOTA: </b> {{loan.estimated_quota | moneyString}} Bs.</p>
                                </v-col>
                              </v-row>

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   D E   G A R A N T Í A -->
                        <v-tab-item>
                          <v-card flat class=" py-0">
                            <v-card-text  class=" py-0">
                              <v-col cols="12" md="12" color="orange">
                                <v-card-text class="pa-0 mb-0">

                                  <v-col cols="12" md="12" class="py-0 px-0" >
                                    <p style="color:teal"><b>GARANTÍA</b></p>
                                  </v-col>

                                  <v-progress-linear></v-progress-linear>

                                  <div v-for="procedure_type in procedure_types" :key="procedure_type.id" class="pa-0 py-0" >
                                    <ul style="list-style: none" class="pa-0" v-if="procedure_type.name == 'Préstamo a Largo Plazo' || procedure_type.name == 'Préstamo a Corto Plazo'|| procedure_type.name == 'Refinanciamiento Préstamo a Corto Plazo' || procedure_type.name == 'Refinanciamiento Préstamo a Largo Plazo'">
                                    
                                      <li v-for="guarantor in loan.borrowerguarantors" :key="guarantor.id">
                                        <v-col cols="12" md="12" class="pa-0">

                                          <v-row class="pa-2">
                                            <v-col cols="12" md="12" class="py-0">
                                              <p style="color:black"><b>GARANTE </b></p>
                                            </v-col>

                                            <v-progress-linear></v-progress-linear><br>

                                            <v-col cols="12" md="3">
                                              <p><b>NOMBRE:</b> {{$options.filters.fullName(guarantor, true)}}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>CÉDULA DE IDENTIDAD:</b> {{guarantor.identity_card +" "+ identityCardExt(guarantor.city_identity_card_id) }}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>TELÉFONO:</b> {{guarantor.cell_phone_number}}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>PORCENTAJE DE PAGO:</b> {{guarantor.payment_percentage|percentage }}%</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>LIQUIDO PARA CALIFICACION:</b> {{guarantor.payable_liquid_calculated | moneyString}}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>PROMEDIO DE BONOS:</b> {{guarantor.bonus_calculated| moneyString }}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>LIQUIDO PARA CALIFICACION CALCULADO:</b> {{guarantor.liquid_qualification_calculated | moneyString}}</p>
                                            </v-col>

                                            <v-col cols="12" md="3">
                                              <p><b>INDICE DE ENDEUDAMIENTO CALCULADO:</b> {{guarantor.indebtedness_calculated|percentage }}%</p>
                                            </v-col>
                                          </v-row>

                                        </v-col>
                                      </li>
                                      <br>

                                      <p v-if="loan.guarantors.length==0" ><b> NO TIENE GARANTES </b></p>

                                    </ul>

                                    <v-col cols="12" md="12" v-if="procedure_type.name == 'Préstamo Hipotecario' || procedure_type.name == 'Refinanciamiento Préstamo Hipotecario'">
                                     <p style="color:teal"><b>GARANTIA HIPOTECARIA </b></p>
                                      <v-row>
                                        <v-progress-linear></v-progress-linear><br>

                                        <v-col cols="12" md="4">
                                          <p><b>CIUDAD:</b> {{loan_properties.city_properties}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>UBICACION:</b> {{loan_properties.location}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>NUMERO DE LOTE:</b> {{loan_properties.land_lot_number}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>SUPERFICIE:</b> {{loan_properties.surface}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>UNIDAD DE MEDIDA:</b> {{loan_properties.measurement}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>CODIGO CATASTRAL:</b> {{loan_properties.cadastral_code}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>NRO MATRICULA:</b> {{loan_properties.registration_number}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>NRO FOLIO REAL:</b> {{loan_properties.real_folio_number}}</p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>VNR: </b>{{ loan_properties.net_realizable_value}} </p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                           <p><b>VALOR COMERCIAL: </b>{{loan_properties.commercial_value}} </p>
                                        </v-col>

                                        <v-col cols="12" md="4">
                                          <p><b>VALOR DE RESCATE HIPOTECARIO: </b>{{loan_properties.rescue_value}} </p>
                                        </v-col>
                                      </v-row>
                                    </v-col>
                                    <ul style="list-style: none" class="pa-0 py-0" v-if="procedure_type.name == 'Préstamo Anticipo'">
                                      <v-col cols="12" md="12" class="py-0" >
                                        <p style="color:teal" ><b>GARANTE</b></p>
                                      </v-col>

                                      <v-progress-linear></v-progress-linear>

                                      <br>
                                      <p> <b>NO TIENE GARANTES</b></p>
                                    </ul>

                                  </div>
                                </v-card-text>
                              </v-col>
                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   I N F O R M A C I Ó N   D E  T R A M I T E -->
                        <v-tab-item>
                        <v-card flat>
                          <v-card-text>

                              <v-col cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>INFORMACION DEL TRAMITE</b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              <br>
                              
                              <v-col cols="12" md="6" class="pa-0" >
                                <p>DATOS DEL CONTRATO</p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              
                              <v-row>
                                <v-col cols="12" md="6">
                                  <p><b>FECHA ENTREGA DE CONTRATO:</b> {{loan.delivery_contract_date | date}}</p>
                                </v-col>
                                
                                <v-col cols="12" md="6">
                                  <p><b>FECHA RECEPCION DE CONTRATO:</b> {{loan.return_contract_date==null? loan.return_contract_date:' '}}</p>
                                </v-col>
                              </v-row>

                              <v-col cols="12" class="pa-0">
                                <v-progress-linear></v-progress-linear>
                                <br>
                                
                                <p>DATOS DEL DESEMBOLSO</p>
                                
                                <v-progress-linear></v-progress-linear>

                                <v-row>
                                  <v-col cols="12" md="6" class="py-0">
                                    <br>
                                    <p><b>TIPO DE DESEMBOLSO:</b> {{loan.payment_type.name}}</p>
                                  </v-col>

                                  <v-col cols="12" md="6" v-show="loan.payment_type.name=='Depósito Bancario'" class="py-0">
                                    <br>
                                    <p><b>ENTIDAD FINANCIERA:</b>{{' '+loan.payment_type.name}}</p>
                                  </v-col>
                                  <v-col cols="12" md="4" class="py-0">
                                    <p><b>Saldo de Prestamo a Refinanciar:</b> {{loan_refinancing.balance_parent_loan_refinancing | money}}</p>
                                  </v-col>

                                  <v-col cols="12" md="6" v-show="loan.payment_type.name=='Depósito Bancario'" class="py-0">
                                    <p><b>CUENTA SIGEP:</b> {{' '+loan.borrower[0].sigep_status}}</p>
                                  </v-col>

                                  <v-col cols="12" md="6" class="py-0">
                                    <p><b>CERTIFICACIÓN PRESUPUESTARIA CONTABLE:</b> {{loan.num_accounting_voucher}}</p>
                                  </v-col>

                                  <v-col cols="12" md="6" class="py-0">
                                     <p><b>FECHA DE DESEMBOLSO:</b> {{loan.disbursement_date | date}}</p>
                                  </v-col>
                                </v-row>
                              </v-col>

                              <!--                R E F I N A N C I A M I E N T O            -->
                              <v-col cols="12" md="6" class="pb-0" v-show="loan_refinancing.refinancing">
                               <b><p style="color:teal">DATOS DEL PRÉSTAMO A REFINANCIAR{{' => '+ loan_refinancing.description}}</p></b>
                              </v-col>

                              <v-progress-linear v-show="loan_refinancing.refinancing"></v-progress-linear  >

                              <v-row v-show="loan_refinancing.refinancing"> <v-col cols="12" md="4" class="py-2"> <p><b>Codigo de Prestamo a Padre:</b>{{' '+loan_refinancing.code}}</p> </v-col> 
                                <v-col cols="12" md="4" class="py-2" >
                                  <p><b>Monto de Préstamo Padre:</b> {{loan_refinancing.amount_approved_son | money}}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-2">
                                  <p><b>Plazo de Préstamo Padre:</b>{{' '+loan_refinancing.loan_term}}</p>
                                </v-col>
                                
                                <v-col cols="12" md="4" class="py-0">
                                  <p><b>Cuota de Préstamo Padre:</b> {{loan_refinancing.estimated_quota | money}}</p>
                                </v-col>
                                
                                <v-col cols="12" md="4" class="py-0" v-show="loan_refinancing.date_cut_refinancing==null">
                                  <p><b>Fecha de Corte :</b> Sin registar</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0" v-show="loan_refinancing.date_cut_refinancing !=null">
                                  <p><b>Fecha de Corte :</b> {{loan_refinancing.date_cut_refinancing |date }}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0">
                                  <p><b>Saldo de Prestamo a Refinanciar:</b> {{loan_refinancing.balance | money}}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0" >
                                  <p class="success--text"><b>Monto Solicitado del Prestamo Nuevo:</b> {{loan_refinancing.amount_approved | money}}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0" >
                                  <p class="success--text"><b>Saldo Anterior de la Deuda:</b> {{loan_refinancing.balance_parent_loan_refinancing | money}}</p>
                                </v-col>

                                <v-col cols="12" md="4" class="py-0" >
                                  <p class="success--text"><b>Monto del Refinanciamiento:</b> {{loan_refinancing.refinancing_balance | money}}</p>
                                </v-col>
                              </v-row>
                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   P E R S O N A  R E F E R E N C I A -->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>
                            
                              <v-col cols="12" md="4" class="py-0" >
                                <p style="color:teal"><b>PERSONA DE REFERENCIA </b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear><br>

                              <v-data-table
                                dense
                                v-if="loan.personal_references.length>0"
                                :headers="headers"
                                :items="loan.personal_references"
                                hide-default-footer
                                >
                              </v-data-table>

                              <p v-if="loan.personal_references.length==0"> <b>NO TIENE PERSONA DE REFERENCIA</b></p>

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   C O D E U D O R -->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>

                              <v-col v-if="loan.cosigners.length==0" cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>CODEUDOR</b></p>
                              </v-col>

                             <v-progress-linear v-if="loan.cosigners.length==0"></v-progress-linear>

                             <br v-if="loan.cosigners.length==0">

                              <p v-if="loan.cosigners.length==0" > <b>NO TIENE CODEUDORES</b></p>
                              
                              <v-col cols="12" md="12" class="py-0">
                                <div v-for="procedure_type in procedure_types" :key="procedure_type.id">
                                  <div v-if="procedure_type.name === 'Préstamo Hipotecario'">
                                    <p style="color:teal"><b>EVALUACION DEL CODEUDOR AFILIADO</b></p>
                                        <v-progress-linear></v-progress-linear><br>
                                      <div v-for="(lenders,i) in loan.lenders" :key="i">
                                        <div  v-if="(lenders,i)>0">
                                          <v-row>
                                             <br>
                                            <v-col cols="12" md="4" class="py-0">
                                              <p><b>NOMBRE: </b> {{$options.filters.fullName(loan.borrower[i], true)}} </p>
                                            </v-col>

                                            <v-col cols="12" md="4" class="py-0">
                                              <p><b>PROMEDIO LIQUIDO PAGABLE: </b> {{loan.borrower[i].pivot.payable_liquid_calculated}} Bs.</p>
                                            </v-col>

                                            <v-col cols="12" md="4" class="py-0" >
                                              <p><b>LIQUIDO PARA CALIFICACION: </b> {{loan.borrower[i].pivot.liquid_qualification_calculated | moneyString}} Bs.</p>
                                            </v-col>

                                            <v-col cols="12" md="4" class="py-0">
                                              <p><b>TOTAL BONOS:</b> {{loan.borrower[i].pivot.bonus_calculated | moneyString}}</p>
                                            </v-col>

                                            <v-col cols="12" md="4" class="py-0">
                                              <p><b>INDICE DE ENDEUDAMIENTO:</b> {{loan.borrower[i].pivot.indebtedness_calculated|percentage }}% </p>
                                            </v-col>

                                            <v-col cols="12" md="4" class="py-0">
                                              <p><b>CALCULO DE CUOTA: </b> {{loan.borrower[i].pivot.quota_treat | moneyString}} Bs.</p>
                                            </v-col>

                                          </v-row>
                                        </div>
                                      </div>

                                      <v-progress-linear></v-progress-linear>
                                      <br>
                                  </div>
                                </div>
                                <p style="color:teal" v-if="loan.cosigners.length>0"><b>CODEUDOR NO AFILIADO </b></p>
                                <v-progress-linear v-if="loan.cosigners.length>0"></v-progress-linear>
                                <v-card flat tile>
                                  <v-card-text>
                                   <v-data-table
                                    v-if="loan.cosigners.length>0"
                                    :headers="headers"
                                    :items="loan.cosigners"
                                    dense
                                  >
                                    </v-data-table>
                                  </v-card-text>
                                </v-card>
                              </v-col>
                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   D O C U M E N T O S   P R E S E N T A D O S-->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>
                              <v-col cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>DOCUMENTOS PRESENTADOS</b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              <br>

                              <!-- C O M P O N E N T E-->
                              <DocumentsFlow>
                              </DocumentsFlow>

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   O B S E R V A C I Ó N   T R A M I T E-->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>
                              <v-col cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>OBSERVACIONES DEL TRAMITE</b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              <br>

                              <!-- C O M P O N E N T E -->
                              <ObserverFlow
                              :loan.sync="loan"
                               :observations.sync="observations"
                               :observation_type.sync="observation_type">
                              </ObserverFlow>

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                        <!-- S E C C I Ó N   H I S T O R I A L   T R Á M I T E -->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>
                              <v-col cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>HISTORIAL DEL TRAMITE</b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              <br>

                              <!-- C O M P O N E N T E -->
                              <HistoryFlow
                              :loan.sync="loan">
                              </HistoryFlow>

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                      <!-- S E C C I Ó N   H I S T O R I A L   D E  P A G O S -->
                        <v-tab-item>
                          <v-card flat>
                            <v-card-text>
                              <v-col cols="12" md="12" class="py-0" >
                                <p style="color:teal"><b>HISTORIAL DE PAGOS</b></p>
                              </v-col>

                              <v-progress-linear></v-progress-linear>
                              <br>

                              <!-- C O M P O N E N T E -->
                              <HistoryFlowPayments 
                                :loan="loan"
                              />

                            </v-card-text>
                          </v-card>
                        </v-tab-item>

                      </v-tabs>
                    </v-card-text>
                  </v-card>
                 </v-col>
                </v-row>
              </v-container>
            </v-col>
          </v-row>
      </v-form>
  </v-container>
</template>
<script>

import DocumentsFlow from "@/components/tracing/DocumentsFlow"
import ObserverFlow from "@/components/tracing/ObserverFlow"
import HistoryFlow from "@/components/tracing/HistoryFlow"
import HistoryFlowPayments from "@/components/tracing/HistoryFlowPayments"

export default {
  components: {
   DocumentsFlow,
   ObserverFlow,
   HistoryFlow,
   HistoryFlowPayments
  },
  name: "specific-data-loan",
  props: {
    loan_refinancing: {
      type: Object,
      required: true
    },
    loan: {
      type: Object,
      required: true
    },
    observations: {
      type: Array,
      required: true
    },
    observation_type: {
      type: Array,
      required: true
    },
    loan_properties: {
      type: Object,
      required: true
    },
    procedure_types: {
      type: Object,
      required: true
    },
  },
   data: () => ({
    items_measurement: [
      { name: "Metros cuadrados", value: "METROS CUADRADOS" },
      { name: "Hectáreas", value: "HECTÁREAS" }
    ],
       headers: [
        {
          text: 'PRIMER NOMBRE',
          align: 'start',
          sortable: false,
          value: 'first_name',
          class: ['normal', 'white--text','text-md-center']
        },
        { text: 'SEGUNDO NOMBRE',  value: 'second_name', class: ['normal', 'white--text','text-md-center'] },
        { text: 'PRIMER APELLIDO ', value: 'last_name', class: ['normal', 'white--text','text-md-center'] },
        { text: 'SEGUNDO APELLIDO ', value: 'mothers_last_name', class: ['normal', 'white--text','text-md-center'] },
        { text: 'TELÉFONO', value: 'phone_number', class: ['normal', 'white--text','text-md-center'] },
        { text: 'CELULAR', value: 'cell_phone_number', class: ['normal', 'white--text','text-md-center'] },
        { text: 'DIRECCION ', value: 'address', class: ['normal', 'white--text','text-md-center'] },
      ],
    city: [],
    entity: [],
    entities:null,
  }),
  beforeMount(){
    this.getCity()
    this.getEntity()
  },
  computed: {
      cuenta() {
       for (this.i = 0; this.i< this.entity.length; this.i++) {
        if(this.loan.lenders[0].financial_entity_id==this.entity[this.i].id)
        {
          this.entities= this.entity[this.i].name
        }
      }
      return this.entities
    }
  },
  methods:{
  //Metodo para obtener la entidad financiera
    async getEntity() {
      try {
        this.loading = true
        let res = await axios.get(`financial_entity`)
        this.entity = res.data
       } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    //Metodo para obtener la ciudad
    async getCity() {
      try {
        this.loading = true
        let res = await axios.get(`city`)
        this.city = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    //Metodo para obtener la extencion del ci
    identityCardExt(id){
      let ext
      if(id != null){
        for(let i=0; i<this.city.length;i++){
          if(this.city[i].id == id){
            ext = this.city[i].first_shortened
          }
        }
      return ext
      }else{
        return ''
      }
    },
  }
}
</script>
