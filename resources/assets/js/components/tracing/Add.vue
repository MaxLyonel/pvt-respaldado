<template>
  <v-card flat>
    <v-card-title class="pb-0">
      <v-toolbar dense color='tertiary'>

        <v-toolbar-title>
          <Breadcrumbs />
        </v-toolbar-title>

        <v-spacer></v-spacer>

          <template>

              <!-- B O T Ó N  1   I M P.  S O L I C I T U D -->
              <v-tooltip bottom >
                <template v-slot:activator="{ on }">
                  <v-btn
                    :loading="loading_print_solicitude"
                    fab
                    x-small
                    outlined
                    v-on="on"
                    color="primary"
                    class="ml-1"
                    @click="imprimir(2, loan.id)"
                    ><v-icon>mdi-file-document</v-icon>
                  </v-btn>
                </template>
                <span>Imprimir Solicitud</span>
              </v-tooltip>

              <!-- B O T Ó N  2   I M P.  C O N T R A T O -->
              <v-tooltip bottom >
                <template v-slot:activator="{ on }">
                  <v-btn
                    :loading="loading_print_contract"
                    fab
                    x-small
                    outlined
                    v-on="on"
                    color="primary"
                    class="ml-1"
                    @click="imprimir(1, loan.id)"
                    ><v-icon>mdi-file</v-icon>
                  </v-btn>
                </template>
                <span>Imprimir Contrato</span>
              </v-tooltip>

              <!-- B O T Ó N  3   I M P.   P L A N   D E   P A G O S -->
              <v-tooltip bottom v-if="loan.state.name == 'Vigente' || loan.state.name == 'Liquidado'">
                <template v-slot:activator="{ on }">
                  <v-btn
                    :loading="loading_print_plan"
                    fab
                    x-small
                    outlined
                    v-on="on"
                    color="primary"
                    class="ml-1"
                    @click="imprimir(3, loan.id)"
                    ><v-icon>mdi-cash</v-icon>
                  </v-btn>
                </template>
                <span>Imprimir Plan de pagos</span>
              </v-tooltip>

              <!-- B O T Ó N  4    I M P.   K A R D E X -->
              <v-tooltip bottom v-if="loan.state.name == 'Vigente' || loan.state.name == 'Liquidado'">
                <template v-slot:activator="{ on }">
                  <v-btn
                    :loading="loading_print_kardex"
                    fab
                    x-small
                    outlined
                    v-on="on"
                    color="primary"
                    class="ml-1"
                    @click="imprimir(4, loan.id)"
                    ><v-icon>mdi-view-list</v-icon>
                  </v-btn>
                </template>
                <span>Imprimir Kardex</span>
              </v-tooltip>

              <!-- B O T Ó N  5   I M P.   F O R M.  C A L I F I C A C I Ó N -->
              <v-tooltip bottom >
                <template v-slot:activator="{ on }">
                  <v-btn
                    v-if="permissionSimpleSelected.includes('print-qualification-form')"
                    :loading="loading_print_form_calification"
                    fab
                    x-small
                    outlined
                    v-on="on"
                    color="primary"
                    class="ml-1"
                    @click="imprimir(5, loan.id)"
                    ><v-icon>mdi-playlist-check</v-icon>
                  </v-btn>
                </template>
                <span>Imprimir Formulario de Calificación</span>
              </v-tooltip>

          <v-divider vertical class="mx-4"></v-divider>

          <h6 class="caption">

          <strong>Ubicación trámite:</strong> <br />

          <v-icon x-small color="orange">mdi-folder-information</v-icon>{{role_name}} <br>
          <v-icon x-small color="blue" v-if="user_name != null">mdi-file-account</v-icon> {{user_name}}</h6>

          </template>

      </v-toolbar>
    </v-card-title>

    <!-- S E C C I Ó N   D A T O S   E S P E C I F I C O S -->
    <v-card-text>

      <Dashboard :affiliate.sync="affiliate" :loan.sync="loan"/>

      <FormTracing
          :loan.sync="loan"
          :loan_refinancing.sync="loan_refinancing"
          :loan_properties.sync="loan_properties"
          :procedure_types.sync="procedure_types"
          :observations.sync="observations"
          :observation_type.sync="observation_type"
      >
      </FormTracing>

    </v-card-text>

  </v-card>
</template>
<script>
import Breadcrumbs from "@/components/shared/Breadcrumbs"
import FormTracing from "@/components/tracing/FormTracing"
import Dashboard from "@/components/tracing/Dashboard"

export default {
  name: "flow-index",
  components: {
    Breadcrumbs,
    FormTracing,
    Dashboard,
  },
  data: () => ({
    affiliate: {
      first_name: null,
      second_name: null,
      last_name: null,
      mothers_last_name: null,
      identity_card: null,
      birth_date: null,
      date_death: null,
      reason_death: null,
      phone_number: null,
      cell_phone_number: null,
      city_identity_card_id: null,
      date_entry: null,
      service_years: null,
      service_months: null,
      date_derelict: null,
      unit_name: null,
      registration: null
    },
    loan: {
      state: {},
      borrower:[
        {
          city_identity_card: {}
        }
      ],
      modality:{},
      payment_type: {},
      personal_references:[],
      cosigners:[],
      guarantors:[]
    },
    city:[],
    loan_refinancing:{},
    observations: [],
    observation_type: [],
    loan_properties: {},
    procedure_types: {},
    vertical: true,
    tab: "tab-1",
    role_name: null,
    user_name: null,
    loading_print_solicitude:false,
    loading_print_contract:false,
    loading_print_plan:false,
    loading_print_kardex:false,
    loading_print_form_calification:false
  }),
   mounted() {
    this.getloan(this.$route.params.id)
    this.getObservation(this.$route.params.id)
    this.getObservationType()
    this.getCity()
  },
  computed:{
    //permisos del selector global por rol
    permissionSimpleSelected () {
      return this.$store.getters.permissionSimpleSelected
    },
  },
  methods: { setBreadcrumbs() { let breadcrumbs = [
        {
          text: "Seguimiento",
          to: { name: "ListTracingLoans" }
        }
      ]
      breadcrumbs.push({
        text: this.loan.code,
        to: { name: "tracingAdd", params: { id: this.loan.id } }
      })
      this.$store.commit("setBreadcrumbs", breadcrumbs)
    },
    //Metodo para sacar el detalle del loan
    async getloan(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan/${id}`)
        this.loan = res.data
        this.loan.state.name = res.data.state.name

        if(this.loan.parent_reason=='REFINANCIAMIENTO')
        {
          this.loan_refinancing.refinancing = true
          if(this.loan.parent_loan_id){

            this.loan_refinancing.code = this.loan.parent_loan.code
            this.loan_refinancing.amount_approved_son  = this.loan.parent_loan.amount_approved
            this.loan_refinancing.loan_term  = this.loan.parent_loan.loan_term
            this.loan_refinancing.balance  = this.loan.parent_loan.balance
            this.loan_refinancing.estimated_quota = this.loan.parent_loan.estimated_quota
            this.loan_refinancing.disbursement_date = this.loan.parent_loan.disbursement_date
            this.loan_refinancing.type_sismu = false
            this.loan_refinancing.description= 'PRESTAMO DEL PVT'
          }else{
            this.loan_refinancing.code = this.loan.data_loan.code
            this.loan_refinancing.amount_approved_son  = this.loan.data_loan.amount_approved
            this.loan_refinancing.loan_term  = this.loan.data_loan.loan_term
            this.loan_refinancing.balance  = this.loan.data_loan.balance
            this.loan_refinancing.type_sismu = true
            this.loan_refinancing.estimated_quota = this.loan.data_loan.estimated_quota
            this.loan_refinancing.disbursement_date = this.loan.data_loan.disbursement_date
            this.loan_refinancing.description= 'PRESTAMO DEL SISMU'
           }
        }else{
           this.loan_refinancing.refinancing = false
        }
            this.loan_refinancing.date_cut_refinancing = this.loan.date_cut_refinancing
            this.loan_refinancing.balance_parent_loan_refinancing = this.loan.balance_parent_loan_refinancing
            this.loan_refinancing.amount_approved = this.loan.amount_approved
            this.loan_refinancing.refinancing_balance = this.loan.refinancing_balance

        let res1 = await axios.get(`affiliate/${this.loan.affiliate_id}`)
        this.affiliate = res1.data
        if (this.loan.property_id != null) {
          this.getLoanproperty(this.loan.property_id)
        }
        this.getProceduretype(this.loan.procedure_modality_id)
        this.borrower = this.loan.borrower[0]
        this.setBreadcrumbs()
        //this.getAddress(this.affiliate.id)

        this.role(this.loan.role_id)
        if(this.loan.user_id != null){
          this.user(this.loan.user_id)
        }
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
    //Metodo para sacar detalle de la propiedad
    async getLoanproperty(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan_property/${id}`)
        this.loan_properties = res.data
        for(let i=0; i<= this.city.length ; i++ )
        {
          if(this.city[i].id == this.loan_properties.real_city_id)
          {
           this.loan_properties.city_properties = this.city[i].name
          }
        }
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    //Metodo para sacar el procedure
    async getProceduretype(id) {
      try {
        this.loading = true
        let res = await axios.get(`procedure_modality/${id}`)
        this.procedure_types = res.data
        /*console.log(this.procedure_types)*/ } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    //Metodo para sacar el tipo de observacion
    async getObservationType() {
      try {
        this.loading = true
        let res = await axios.get(
          `module/${6}/observation_type`
        )
        this.observation_type = res.data
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    //Metodo para sacar las observaciones del tramite
    async getObservation(id) {
      try {
        this.loading = true
        let res = await axios.get(`loan/${id}/observation`)
        this.observations = res.data

        for (this.i = 0; this.i < this.observations.length; this.i++) {
           //console.log(this.observations[this.i].user_id)
          let res1 = await axios.get(`user/${this.observations[this.i].user_id}`
          )
          this.observations[this.i].user_name = res1.data.username
        }
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async role(role_id){
      try {
        let res = await axios.get(`role/${role_id}`)
        this.role_name = res.data.display_name
      } catch (e) {
        console.log(e)
      }
    },
    //Metodo para sacar los usuarios
    async user(user_id){
      try {
        let res = await axios.get(`user/${user_id}`)
        this.user_name = res.data.username
      } catch (e) {
        console.log(e)
      }
    },
    async imprimir(id, item) {
      try {
        let res;
        if (id == 1) {
          this.loading_print_contract=true
          res = await axios.get(`loan/${item}/print/contract`);
        } else if (id == 2) {
          this.loading_print_solicitude=true
          res = await axios.get(`loan/${item}/print/form`);
        } else if (id == 3) {
          this.loading_print_plan=true
          res = await axios.get(`loan/${item}/print/plan`);
        } else if (id == 4) {
          this.loading_print_kardex=true
          res = await axios.get(`loan/${item}/print/kardex`);
        } else {
          this.loading_print_form_calification=true
          res = await axios.get(`loan/${item}/print/qualification`);
        }
        printJS({
          printable: res.data.content,
          type: res.data.type,
          documentTitle: res.data.file_name,
          base64: true,
        });
      } catch (e) {
        this.toastr.error("Ocurrió un error en la impresión.");
        console.log(e);
      }
      finally{
        this.clean()
      }
    },
    //Metodo para dejar los valores por defecto
    clean(){
      this.loading_print_solicitude=false
      this.loading_print_contract=false
      this.loading_print_plan=false
      this.loading_print_kardex=false
      this.loading_print_form_calification=false
    }
  },
}

</script>
