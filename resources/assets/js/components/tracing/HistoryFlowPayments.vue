<template>
  <v-container fluid>

    <template v-if="loan.state.name == 'Vigente'">

      <v-card class="ma-0 pa-0 pb-2">
        <v-row class="ma-0 pa-0">
          <v-col md="4" class="ma-0 pa-0">
            <strong>Monto desembolsado: </strong>{{ loan.amount_approved | moneyString }}<br />
            <strong>Desembolso: </strong>{{ loan.disbursement_date | date }}<br />
            <strong>Nro de comprobante contable: </strong>{{ loan.num_accounting_voucher }}<br />
          </v-col>
          <v-col md="4" class="ma-0 pa-0">
            <strong>Tasa anual: </strong> {{ loan.intereses.annual_interest | percentage }}%<br />
            <strong>Cuota fija mensual: </strong> {{ loan.estimated_quota | money}}<br />
          </v-col>

        </v-row>
      </v-card>

      <v-data-table
        dense
        :headers="headers"
        :items="payments"
        :loading="loading"
        :options.sync="options"
        :footer-props="{ itemsPerPageOptions: [10,50,100] }"
        :search="search"
        :key="refreshKardexTable"
      >

        <template v-slot:[`header.code`]="{ header }">
            {{ header.text }}<br>
            <v-menu offset-y :close-on-content-click="false">
              <template v-slot:activator="{ on, attrs }">
                <v-btn icon v-bind="attrs" v-on="on">
                  <v-icon small :color="search !='' ? 'red' : 'black'">
                    mdi-filter
                  </v-icon>
                </v-btn>
              </template>
              <div>
                <v-text-field
                  dense
                  v-model="search"
                  type="text"
                  :label="'Buscar ' + header.text"
                  hide-details
                  single-line
                ></v-text-field>
              </div>
            </v-menu>
          </template>

        <template v-slot:[`item.role_display_name`]="{ item }">
          {{ item.role.display_name }}
        </template>
        <template v-slot:[`item.user_username`]="{ item }">
          {{ item.user ? item.user.username : '' }}
        </template>
        <template v-slot:[`item.estimated_date`]="{ item }">
          {{ item.estimated_date | date }}
        </template>
        <template v-slot:[`item.created_at`]="{ item }">
          {{ item.created_at | date }}
        </template>
        <template v-slot:[`item.capital_payment`]="{ item }">
          {{ item.capital_payment | moneyString }}
        </template>
        <template v-slot:[`item.interest_payment`]="{ item }">
          {{ item.interest_payment | moneyString }}
        </template>
        <template v-slot:[`item.interest_remaining`]="{ item }">
          {{ item.interest_remaining | moneyString }}
        </template>
        <template v-slot:[`item.penal_payment`]="{ item }">
          {{ item.penal_payment | moneyString }}
        </template>
        <template v-slot:[`item.penal_remaining`]="{ item }">
          {{ item.penal_remaining | moneyString }}
        </template>
        <template v-slot:[`item.estimated_quota`]="{ item }">
          {{ item.estimated_quota | moneyString }}
        </template>
        <template v-slot:[`item.voucher`]="{ item }">
          {{item.voucher}}
        </template>
        <template v-slot:[`item.modality_shortened`]="{ item }">
          <v-tooltip top>
            <template v-slot:activator="{ on }">
              <span v-on="on"> {{item.modality.shortened}}</span>
            </template>
            <span>{{item.modality.name}}</span>
          </v-tooltip>
        </template>

        <template v-slot:[`item.state.name`]="{ item }">
          {{ item.state.name }}
        </template>

        <!-- <template v-slot:[`item.actions`]="{ item }">
          <v-tooltip bottom>
            <template v-slot:activator="{ on }">
              <v-btn
                icon
                small
                v-on="on"
                color="warning"
                :to="{
                  name: 'paymentAdd',
                  params: { hash: 'view' },
                  query: { loan_payment: item.id } }">
                <v-icon>mdi-eye</v-icon>
              </v-btn>
            </template>
            <span>Ver registro de cobro</span>
          </v-tooltip>

          <v-menu offset-y close-on-content-click>
            <template v-slot:activator="{ on }">
              <v-btn icon color="primary" dark v-on="on">
                <v-icon>mdi-printer</v-icon>
              </v-btn>
            </template>
            <v-list dense class="py-0">
              <v-list-item
                v-for="doc in printDocs"
                :key="doc.id"
                @click="imprimir(doc.id, item.id)"
              >
                <v-list-item-icon class="ma-0 py-0 pt-2">
                  <v-icon
                    class="ma-0 py-0"
                    small
                    v-text="doc.icon"
                    color="light-blue accent-4"
                  ></v-icon>
                </v-list-item-icon>
                <v-list-item-title class="ma-0 py-0 mt-n2">{{doc.title}}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
          <template></template>
        </template> -->
      </v-data-table>

    </template>
    <template v-else>
      <h3>NO SE CUENTA AÚN CON EL KARDEX</h3>
    </template>
  </v-container>
</template>
<script>

export default {
  name: "Kardex-list",
  components: {

  },
  props: {
    loan: {
      type: Object,
      required: true,
    },
  },

  data: () => ({
    bus: new Vue(),
    loading: true,
    options: {
      page: 1,
      itemsPerPage: 10,
      sortBy: ["quota_number"],
      sortDesc: [false],
    },
    payments: [],
    totalPayments: 0,
    paymentState: 0,
    printDocs: [],
    amortization_type: [],
    procedure_modality: [],
    search: '',

    headers: [
      {
        text: "Nro Cuota",
        value: "quota_number",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      {
        text: "Ubicación área",
        value: "role_display_name",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
      },
            {
        text: "Usuario",
        value: "user_username",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
      },
      {
        text: "Código",
        value: "code",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
      },
      {
        text: "Fecha de cálculo",
        value: "estimated_date",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      {
        text: "Fecha de cobro",
        value: "created_at",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      {
        text: "Amortización capital",
        value: "capital_payment",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Interes corriente",
        value: "interest_payment",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Interes penal",
        value: "penal_payment",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Interes corriente pendiente",
        value: "interest_remaining",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Interes penal pendiente",
        value: "penal_remaining",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Total pagado",
        value: "estimated_quota",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Comprobante",
        value: "voucher",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      {
        text: "Tipo de Amortización.",
        value: "modality_shortened",
        class: ["normal", "white--text"],
        align: "center",
        sortable: false,
        width: "5%",
        filterable: false,
      },
      {
        text: "Pagado por",
        value: "paid_by",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      {
        text: "Estado",
        value: "state.name",
        class: ["normal", "white--text"],
        align: "center",
        sortable: true,
        width: "5%",
        filterable: false,
      },
      // {
        // text: "Acciones",
        // value: "actions",
        // class: ["normal", "white--text"],
        // align: "center",
        // sortable: false,
        // width: "15%",
        // filterable: false,
      // },
    ],
    refreshKardexTable: 0,
  }),
  computed: {
    //Metodo para obtener Permisos por rol
    permissionSimpleSelected() {
      return this.$store.getters.permissionSimpleSelected;
    },
  },
  watch: {
    options: function (newVal, oldVal) {
      if (
        newVal.page != oldVal.page ||
        newVal.itemsPerPage != oldVal.itemsPerPage ||
        newVal.sortBy != oldVal.sortBy ||
        newVal.sortDesc != oldVal.sortDesc
      ) {
        this.getPayments()
      }
    },

  },
  mounted() {
    this.getPayments()
  },
  methods: {
    async getPayments() {
      try {
        this.loading = true;
        let res = await axios.get(`history_loan_payment`, {
          params: {
            loan_id: this.$route.params.id,
          },
        })
        this.payments = res.data.payments
        if(this.payments.length > 0){
          this.docsLoans()
        }
        this.refreshKardexTable++
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    createPayment(){
      if(this.loan.state.name != 'Liquidado'){
        if(this.loan.balance > 0){
          this.$router.push({ name: 'paymentAdd', params: { hash: 'new' }, query: { loan_id: this.$route.params.id } })
        }else{
          this.toastr.error("El saldo es 0, no puede realizar mas pagos.")
        }
      }
      else {
        this.toastr.error("El trámite tiene estado LIQUIDADO, no puede realizar mas pagos.")
      }
    },

    async imprimir(id, item) {
      try {
        let res;
        if (id == 5) {
          res = await axios.get(`loan_payment/${item}/print/loan_payment`);
        }
        printJS({
          printable: res.data.content,
          type: res.data.type,
          documentTitle: res.data.file_name,
          base64: true,
        });
      } catch (e) {
        this.toastr.error("Ocurrió un error en la impresión.");
        console.log(e)
      }
    },

    docsLoans() {
      let docs = [];
      if (this.permissionSimpleSelected.includes("print-payment-loan")) {
        docs.push({
          id: 5,
          title: "Registro de cobro",
          icon: "mdi-file-check-outline",
        });
      } else {
        console.log("Se ha producido un error durante la generación de la impresión");
      }
      this.printDocs = docs;
    },

    async imprimirK(item, folded) {
      try {
        let res = await axios.get(`loan/${item}/print/kardex`, {
          params: {
            folded: folded,
          },
        });
        console.log("kardex " + item);
        printJS({
          printable: res.data.content,
          type: res.data.type,
          file_name: res.data.file_name,
          base64: true,
        });
      } catch (e) {
        this.toastr.error("Ocurrió un error en la impresión.");
        console.log(e);
      }
    },
    last_payment(item){
      if(item.id == this.payments[this.payments.length -1].id ){
        return true
      }else{
        return false
      }
    },

  },
};
</script>
<style scoped>
.theme--light.v-datatable.v-datatable-tr.v-datatable-td {
  position: fixed;
  bottom: 0;
  padding: 0px;
}
.v-text-field{
  background-color: white;
  width: 200px;
  padding:5px;
  margin: 0px;
  font-size: 0.8em;
  border-color: palegreen;
}
</style>