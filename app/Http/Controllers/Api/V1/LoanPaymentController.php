<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use App\LoanPayment;
use App\User;
use App\Voucher;
use App\LoanState;
use App\LoanPaymentState;
use App\Affiliate;
use App\Spouse;
use App\LoanGlobalParameter;
use App\Http\Requests\LoanPaymentsForm;
use App\Http\Requests\VoucherForm;
use App\Events\LoanFlowEvent;
use App\Events\LoanPaymentFlowEvent;
use App\RoleSequence;
use Carbon;
use DB;
use App\Helpers\Util;
use App\Http\Controllers\Api\V1\LoanController;
use App\Exports\ArchivoPrimarioExport;
use App\Exports\FileWithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use App\Loan;
use App\Role;
use App\ProcedureModality;
use App\PaymentType;
use App\LoanPaymentPeriod;
//use App\AmortizationType;
use App\ProcedureType;
use App\AffiliateStateType;
use App\AffiliateState;
use App\Imports\LoanPaymentImport;
use App\Tag;
use Carbon\CarbonImmutable;
use App\LoanPaymentCategorie;

/** @group Cobranzas
* Datos de los trámites de Cobranzas
*/
class LoanPaymentController extends Controller
{
    public static function append_data(LoanPayment $loanPayment, $with_state = false)
    {
        $loanPayment->loan = $loanPayment->loan;
        $loanPayment->affiliate = $loanPayment->affiliate;
        if($loanPayment->paid_by == 'T')
            $loanPayment->borrower = $loanPayment->loan->getBorrowers()->where('id_affiliate', $loanPayment->affiliate->id);
        else
            $loanPayment->borrower = $loanPayment->loan->getGuarantors()->where('id_affiliate', $loanPayment->affiliate->id);
        if ($with_state) $loanPayment->state = $loanPayment->state;
        $loanPayment->modality->procedure_type = $loanPayment->modality->procedure_type;
        return $loanPayment;
    }
    /**
    * Lista de Registro de pagos
    * Devuelve el listado con los datos paginados
    * @queryParam role_id integer Ver préstamos del rol, si es 0 se muestra la lista completa. Example: 73
    * @queryParam state_id integer ID del estado del registro de pago. Example 6
    * @queryParam loan_id integer ID del tramite de préstamo. Example 1
    * @queryParam user_id integer ID del usuario de préstamo. Example 1
    * @queryParam validated Booleano para filtrar trámites válidados. Example: 1
    * @queryParam procedure_type_id ID para filtrar trámites por tipo de trámite. Example: 30
    * @queryParam trashed Booleano para obtener solo eliminados. Example: 1
    * @queryParam search Parámetro de búsqueda. Example: 2000
    * @queryParam sortBy Vector de ordenamiento. Example: []
    * @queryParam sortDesc Vector de orden descendente(true) o ascendente(false). Example: [true]
    * @queryParam per_page Número de datos por página. Example: 8
    * @queryParam page Número de página. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/index.200.json
     */
    public function index(Request $request){
        $filters = [];
        $relations = [];
        if (!$request->has('role_id')) {
            if (Auth::user()->can('show-all-loan') || Auth::user()->can('show-all-payment-loan')) {
                $request->role_id = 0;
            } else {
                $role = Auth::user()->roles()->whereHas('module', function($query) {
                    return $query->whereName('prestamos');
                })->orderBy('name')->first();
                if ($role) {
                    $request->role_id = $role->id;
                } else {
                    abort(403);
                }
            }
        } else {
            $request->role_id = (integer)$request->role_id;
            if (($request->role_id == 0 && !Auth::user()->can('show-all-loan') && !Auth::user()->can('show-all-payment-loan')) || ($request->role_id != 0 && !Auth::user()->roles->pluck('id')->contains($request->role_id))) {
                abort(403);
            }
        }
        if ($request->role_id != 0) {
            $filters = [
                'role_id' => $request->role_id
            ];
        }
        if ($request->has('validated')) $filters['validated'] = $request->boolean('validated');
        if ($request->has('procedure_type_id')) {
            $relations['modality'] = [
                'procedure_type_id' => $request->procedure_type_id
            ];
        }
        if ($request->has('state_id')) {
            $relations['state'] = [
                'state_id' => $request->state_id
            ];
        }
        if ($request->has('loan_id')) {
            $relations['loan'] = [
                'loan_id' => $request->loan_id
            ];
        }
        if ($request->has('user_id')) {
            $relations['user'] = [
                'user_id' => $request->user_id
            ];
        }
        $data = Util::search_sort(new LoanPayment(), $request, $filters, $relations);
        $data->getCollection()->transform(function ($loanPayment) {
            return self::append_data($loanPayment, true);
        });
        return $data;
    }

    /**
    * Kardex de pagos
    * Devuelve el kardex de pagos con los datos paginados
    * @queryParam loan_id integer required ID del tramite de préstamo. Example 1
    * @queryParam search Parámetro de búsqueda. Example: PAY000016
    * @queryParam per_page Número de datos por página. Example: 8
    * @queryParam page Número de página. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/indexKardex.200.json
     */
    public function indexKardex(Request $request){
        $loan = Loan::whereId($request->loan_id)->first();
        $loan->balance = $loan->balance;
        $loan['estimated_quota'] = $loan->estimated_quota;
        $loan['interest'] = $loan->interest;
        $payments = collect();
            $loanPayments = LoanPayment::where('loan_id', $request->loan_id)->orderBy('id')->get();
            foreach($loanPayments as $loanPayment)
            {
                if($loanPayment->state->name == 'Pagado' || $loanPayment->state->name == 'Pendiente por confirmar')
                {
                    $loanPayment->state = LoanPaymentState::whereId($loanPayment->state_id)->first();
                    $loanPayment->role = Role::whereId($loanPayment->role_id)->first();
                    $loanPayment->user = User::whereId($loanPayment->user_id)->first();
                    $loanPayment->modality;
                    $loanPayment->voucher_treasury;
                    $loanPayment->is_last_payment_kardex = $loanPayment->is_last_payment_kardex();
                    $payments->push($loanPayment);
                }
            }
        $loan->payments = $payments;
        return $loan;
    }
     /**
    * Historial de pagos
    * Devuelve el historial de pagos con los datos paginados
    * @bodyParam loan_id integer required ID del tramite de préstamo. Example 1
    * @authenticated
    * @responseFile responses/loan_payment/payment_history.200.json
     */
    public function payment_history(Request $request){
        $request->validate([
            'loan_id' => 'required',
        ]);
        $loan = Loan::whereId($request->loan_id)->first();
        $loan->balance = $loan->balance;
        $loan['estimated_quota'] = $loan->estimated_quota;
        $loan['interest'] = $loan->interest;
        $payments = collect();
            $loanPayments = LoanPayment::where('loan_id', $request->loan_id)->orderBy('id')->withTrashed()->get();
            foreach($loanPayments as $loanPayment)
            {
                    $loanPayment->state = LoanPaymentState::whereId($loanPayment->state_id)->first();
                    $loanPayment->role = Role::whereId($loanPayment->role_id)->first();
                    $loanPayment->user = User::whereId($loanPayment->user_id)->first();
                    $loanPayment->modality;
                    $loanPayment->voucher_treasury;
                    $payments->push($loanPayment);
            }           
        $loan->payments = $payments;
        return $loan;
    }

    /**
    * Detalle de Registro de pago
    * Devuelve el detalle de un registro de pago mediante su ID
    * @urlParam loan_payment required ID de registro de pago. Example: 4
    * @authenticated
    * @responseFile responses/loan_payment/show.200.json
    */
    public function show(LoanPayment $loanPayment)
    {
        if (Auth::user()->can('show-all-loan') || Auth::user()->roles()->whereHas('module', function($query) {
            return $query->whereName('prestamos');
        })->pluck('id')->contains($loanPayment->role_id)) {
            return self::append_data($loanPayment, true);
        } else {
            abort(403);
        }
    }

    /**
    * Editar Registro de pago
    * Edita el Registro de Pago realizado.
    * @urlParam loan_payment required ID del pago realizado. Example: 1
	* @bodyParam description string Texto de descripción. Example: Penalizacion regularizada
    * @bodyParam validated boolean Estado validación del tramite de cobro. Example: true
    * @bodyParam voucher string Comprobante de pago GAR-ABV o D-10/20 o CONT-123. Example: CONT-123
    * @bodyParam affiliate_id integer ID del afiliado. Example: 57950
    * @bodyParam paid_by enum Pago realizado por Titular(T) o Garante(G). Example: T
    * @bodyParam procedure_modality_id integer ID de la modalidad de amortización. Example: 53
    * @authenticated
    * @responseFile responses/loan_payment/update.200.json
    */
    public function update(Request $request, LoanPayment $loanPayment)
    {
        $payment_procedure_type = $loanPayment->modality->procedure_type->name;
        $Pagado = LoanPaymentState::whereName('Pagado')->first()->id;
        $pendiente_pago = LoanPaymentState::whereName('Pendiente por confirmar')->first()->id;
        $request->validate([
            'description' => 'nullable|string|min:2',
            'validated' => 'boolean',
            'procedure_modality_id'=> 'exists:procedure_modalities,id',
           // 'amortization_type_id'=> 'exists:amortization_types,id',
            'affiliate_id'=> 'exists:affiliates,id',
            'voucher'=> 'nullable|string|min:3',
            'paid_by'=> 'string|in:T,G',
        ]);
        if (Auth::user()->can('update-payment-loan')) {
            $update = $request->only('description','loan_payment_date', 'validated','procedure_modality_id','affiliate_id','voucher','paid_by');
        }
        if($payment_procedure_type != 'Amortización Directa' && $request->validated) {
            $loanPayment->state_id=$Pagado;
        }
        if($payment_procedure_type != 'Amortización Directa' && !$request->validated){
            $loanPayment->state_id=$pendiente_pago;
        }
        $user_id = auth()->id();
        $loanPayment->fill($update);
        $loanPayment->save();
        $loanPayment->update(['user_id' => $user_id]);
       /* if($request->validated && $loanPayment->state_id == $Pagado || $request->validated && $request->state_id == $Pagado){
            $loanPayment->update(['loan_payment_date' => Carbon::now()]);
            $loanPayment->save();
        }*/
        $loan = Loan::find($loanPayment->loan_id);
        $loan->verify_state_loan();
        return  $loanPayment;
    }

    /**
    * Anular Registro de Pago
    * @urlParam loan_payment required ID del pago. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/destroy.200.json
    */
    public function destroy(LoanPayment $loanPayment)
    {
        $PendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;
        $PendienteAjuste = LoanPaymentState::whereName('Pendiente por confirmar')->first()->id;
        if ($loanPayment->state_id == $PendientePago || $loanPayment->state_id == $PendienteAjuste){
            $state = LoanPaymentState::whereName('Anulado')->first();
            $loanPayment->state()->associate($state);
            $loanPayment->save();
            $loanPayment->delete();
            $loan = Loan::find($loanPayment->loan_id);
            $loan->verify_state_loan();
            return $loanPayment;  
        }else{
            abort(403, 'El registro a eliminar no está en estado Pendiente');
        }
    }

    /**
    * Anular el Ultimo Registro de Pago
    * @urlParam loan_payment required ID del pago. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/delete_last_record_payment.200.json
    */
    public function delete_last_record_payment(LoanPayment $loanPayment)
    {       
        $message['message'] = false;
        if(isset($loanPayment)){   
            $loan_state_liquidated = LoanState::whereName("Liquidado")->first()->id;  
            $loan_state_current = LoanState::whereName("Vigente")->first()->id;
            $last_records = $loanPayment->loan->paymentsKardex->sortByDesc('id')->first();  
            $loan_state = $loanPayment->loan->state_id; 
            $loan = $loanPayment->loan;   
            if ($loanPayment->id == $last_records->id){
                $procedure_modality_id = ProcedureModality::whereName("Directo")->first()->id;
                if($loanPayment->procedure_modality_id == $procedure_modality_id){ 
                    $voucher=Voucher::wherePayableId($loanPayment->id)->wherePayableType('loan_payments')->whereDeletedAt(null)->first();
                    if($voucher == null){
                        $state = LoanPaymentState::whereName('Anulado')->first();
                        $loanPayment->state()->associate($state);
                        $loanPayment->validated = false;
                        $loanPayment->save();
                        if($loan_state_liquidated == $loan_state){
                            $loan->state_id = LoanState::whereName("Vigente")->first()->id;
                            $loan->update();
                          }
                        $loanPayment->delete();                       
                        return $loanPayment;                 
                    } else{
                        $message['message'] = "El registro no puede ser eliminado por tener un váucher asociado";
                        $message['deleted'] = false;
                    }
                } else{
                    $state = LoanPaymentState::whereName('Anulado')->first();
                    $loanPayment->state()->associate($state);
                    $loanPayment->validated = false;
                    $loanPayment->save();
                    if($loan_state_liquidated == $loan_state){
                        $loan->state_id = LoanState::whereName("Vigente")->first()->id;
                        $loan->update();
                      }
                    $loanPayment->delete();
                    return $loanPayment;
                }
            }else{
                $message['message'] = "El registro no puede ser eliminado por no se el ultimo";
                $message['deleted'] = false;
                }
        }
        else{       
            $message['message'] = "No se encontro el registro del Pago";  
            $message['deleted'] = false;        
        }
        return $message;
    }

    /** @group Tesoreria
    * Registro de cobro de Préstamo
    * Insertar registro de pago (loan_payment).
    * @urlParam loan_payment required ID del registro de pago. Example: 2
    * @bodyParam voucher_type_id integer required ID de tipo de voucher. Example: 1
    * @bodyParam bank_pay_number integer número de voucher bancario. Example: 12354121
    * @bodyParam voucher_amount_total number Monto del voucher. Example: 55.58
    * @bodyParam voucher_payment_date date Fecha de pago. Example: 2021-01-01
    * @bodyParam description string Texto de descripción. Example: Penalizacion regularizada
    * @authenticated
    * @responseFile responses/loan_payment/set_voucher.200.json
    */
    public function set_voucher(VoucherForm $request, LoanPayment $loanPayment)
    {
        $Liquidado = LoanState::whereName('Liquidado')->first()->id;
        $Pagado = LoanPaymentState::whereName('Pagado')->first()->id;
        $PendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;

        if ($loanPayment->state_id == $PendientePago){
            DB::beginTransaction();
            try {
                $payment = new Voucher;
                $payment->user_id = auth()->id();
                $payment->voucher_type_id = $request->input('voucher_type_id');
                $payment->total = $request->input('voucher_amount_total');
                if($request->has('voucher_payment_date') && $request->voucher_payment_date != null) {
                    $payment->payment_date = $request->input('voucher_payment_date');//fecha del comprobante bancario de voucher
                }else{
                    $payment->payment_date = Carbon::now()->format('Y-m-d');//fecha del comprobante bancario
                }
                $payment->description = $request->input('description', null);
                $payment->bank_pay_number = $request->input('bank_pay_number', null);
                $bank_pay_number=$request->input('bank_pay_number', null);
                //obtencion de codigo de pago
                $correlative = 0;
                $correlative = Util::correlative('voucher');
                $payment->code = implode(['TRANS', str_pad($correlative, 6, '0', STR_PAD_LEFT), '-', Carbon::now()->year]);
                //fin obtencion de codigo;
                $voucher = $loanPayment->voucher_treasury()->create($payment->toArray());
                $loanPayment->update(['state_id' => $Pagado,'user_id' => $payment->user_id,'validated'=>true,'loan_payment_date'=>Carbon::now(),'voucher'=>$bank_pay_number]);
                if($loanPayment->loan->payments->count() == 1 && $loanPayment->loan->payments->first()->state_id == $Pagado){
                    $user = User::whereUsername('admin')->first();
                }
                $loan=Loan::find($loanPayment->loan_id);
                $loan->verify_state_loan();
                 //generar PDF
                    $information_loan= $this->get_information_loan($loan);
                    $file_name = implode('_', ['voucher', $voucher->code]) . '.pdf';
                    $loanpayment = new VoucherController;
                    $payment->attachment = Util::pdf_to_base64([
                        $loanpayment->print_voucher(new Request([]), $voucher, false)
                    ], $file_name,$information_loan, 'legal', $request->copies ?? 1);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return $e;
            }
            return $payment;
        }
        abort(403, 'Registro de Pago no realizado porque no está pendiente de pago');
    }

    /**
    * Derivar en lote
    * Deriva o devuelve trámites en un lote mediante sus IDs
    * @bodyParam ids array required Lista de IDs de los trámites a derivar. Example: [28,29]
    * @bodyParam role_id integer required ID del rol al cual derivar o devolver. Example: 91
    * @authenticated
    * @responseFile responses/loan_payment/bullk_update_role.200.json
    */
    public function bulk_update_role( LoanPaymentsForm $request)
    {
        if(!$request->user_id) 
            $user_id = null;
        else
            $user_id = $request->user_id;
        $sequence = null;
        $from_role = null;
        $to_role = $request->role_id;

        $PendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;

        $to_role = $request->role_id;
        $loanPayment =  LoanPayment::whereIn('id',$request->ids)->where('role_id', '!=', $request->role_id)->where('state_id',$PendientePago)->orderBy('code');
        $derived = $loanPayment->get();
        $to_role = Role::find($to_role);
        
       
        //
        if (count(array_unique($loanPayment->pluck('role_id')->toArray()))) $from_role = $derived->first()->role_id;
        if ($from_role) {
            $from_role = Role::find($from_role);
            $flow_message = $this->flow_message($derived->first()->modality->procedure_type->id, $from_role, $to_role);
        }
        $derived->map(function ($item, $key) use ($from_role, $to_role, $flow_message) {
            if (!$from_role) {
                $item['from_role_id'] = $item['role_id'];
                $from_role = Role::find($item['role_id']);
                $flow_message = $this->flow_message($item->modality->procedure_type->id, $from_role, $to_role);
            }
            $item['role_id'] = $to_role->id;
            $item['validated'] = false;
            Util::save_record($item, $flow_message['type'], $flow_message['message']);
        });
        //

        $loanPayment->update(array_merge($request->only('role_id'), ['validated' => false], ['user_id' => $user_id]));
        
        $derived->transform(function ($loanPaymen) {
            return self::append_data($loanPaymen, true);
        });

        event(new LoanPaymentFlowEvent($derived));

        // PDF template
        $data = [
            'type' => 'loan_payment',
            'header' => [
                'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                'unity' => 'Área de ' . $from_role->display_name,
                'table' => [
                    ['Fecha', Carbon::now()->isoFormat('L')],
                    ['Hora', Carbon::now()->format('H:i')],
                    ['Usuario', Auth::user()->username]
                ]
            ],
            'title' => ($flow_message['type'] == 'derivacion' ? 'DERIVACIÓN' : 'DEVOLUCIÓN') . ' DE TRÁMITES - MODALIDAD ' . $derived->first()->modality->procedure_type->second_name,
            'procedures' => $derived,
            'roles' => [
                'from' => $from_role,
                'to' => $to_role
            ]
        ];
        $information_derivation='Fecha: '.Str::slug(Carbon::now()->isoFormat('LLL'), ' ').'  enviado a  '.$from_role->display_name;
        $file_name = implode('_', ['derivacion', 'Cobros', Str::slug(Carbon::now()->isoFormat('LLL'), '_')]) . '.pdf';
        $view = view()->make('flow.bulk_flow_procedures')->with($data)->render();
        return response()->json([
            'attachment' => Util::pdf_to_base64([$view], $file_name,$information_derivation, 'letter', $request->copies ?? 1, false),
            'derived' => $derived
        ]);
    }

    private function flow_message($procedure_type_id, $from_role, $to_role)
    {
        $sequence = RoleSequence::flow($procedure_type_id, $from_role->id);
        if (in_array($to_role->id, $sequence->next->all())) {
            $message = 'derivó';
            $type = 'derivacion';
        } else {
            $message = 'devolvió';
            $type = 'devolucion';
        }
        $message .= ' de ' . $from_role->display_name . ' a ' . $to_role->display_name;
        return [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
    * Eliminar en lote
    * Elimina trámites en un lote mediante sus IDs, aquellos tramites que se encuentre con estato pendiente de pago
    * @bodyParam ids array required Lista de IDs de los trámites a dereliminar. Example: [1,2,3]
    * @authenticated
    * @responseFile responses/loan_payment/bulk_destroy.200.json
    */
    
    public function bulk_destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|exists:loan_payments,id'
        ]);

        $paymentsDestroy = collect();
        $PendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;
        $loanPaymentse =  LoanPayment::whereIn('id',$request->ids)->where('state_id', $PendientePago);
        $loanPayments = $loanPaymentse->get();
        foreach ($loanPayments as $loanPayment) {
                $loanPayment->delete();
                $paymentsDestroy->push($loanPayment);
        }
        return $paymentsDestroy;
    }

    /**
    * Impresión del Registro de Pago de Préstamo
    * Devuelve un pdf del Pago acorde a un ID de registro de pago
    * @urlParam loan_payment required ID del pago. Example: 1
    * @queryParam copies Número de copias del documento. Example: 2
    * @authenticated
    * @responseFile responses/loan_payment/print_loan_payment.200.json
    */
    public function print_loan_payment(Request $request,$loan_payment, $standalone = true)
    {  
        $loan_payment = LoanPayment::withTrashed()->whereId($loan_payment)->first();
        $loan = $loan_payment->loan;
        $affiliate = $loan_payment->affiliate;
        $procedure_modality = $loan->modality;
        $is_dead = false;
        $quota_treat = 0;
        $global_parameter=LoanGlobalParameter::latest()->first();
        $max_current=$global_parameter->grace_period+$global_parameter->days_current_interest;
        $num_quota=$loan_payment->quota_number;
            if($num_quota == 1){
                $disbursement_date = CarbonImmutable::parse($loan->disbursement_date);
                $estimated_days['current'] = $disbursement_date->diffInDays(CarbonImmutable::parse($loan_payment->estimated_date))+1;
                if($estimated_days['current'] > $max_current)
                    $estimated_days['penal'] = $estimated_days['current'] - $global_parameter->days_current_interest;
                else
                    $estimated_days['penal'] = 0;
            }else{
                $reg_payment_date = CarbonImmutable::parse($loan_payment->previous_payment_date);
                $estimated_days['current'] = $reg_payment_date->diffInDays(CarbonImmutable::parse($loan_payment->estimated_date)->toDateString());
                if($estimated_days['current'] >= $max_current)
                $estimated_days['penal'] = $estimated_days['current'] - $global_parameter->days_current_interest;
                else
                $estimated_days['penal'] = 0;
            }
        $persons = collect([]);
        $data = [
            'header' => [
                'direction' => 'DIRECCIÓN DE ESTRATEGIAS SOCIALES E INVERSIONES',
                'unity' => 'UNIDAD DE INVERSIÓN EN PRÉSTAMOS',
                'table' => [
                    ['Tipo', $loan->modality->procedure_type->second_name],
                    ['Modalidad', $loan->modality->shortened],
                    ['Fecha', Carbon::now()->format('d/m/Y')],
                    ['Hora', Carbon::now()->format('H:i')],
                    ['Usuario', Auth::user()->username]
                ]
            ],
            'title' => 'AMORTIZACIÓN DE CUOTA',
            'loan' => $loan,
            'lenders' => $loan->borrower,
            'loan_payment' => $loan_payment,
            'signers' => $persons,
            'is_dead'=> $is_dead,
            'estimated_days' => $estimated_days
        ];
      
        $information_loan = $this->get_information_loan($loan);
        $file_name = implode('_', ['pagos', $procedure_modality->shortened, $loan->code]) . '.pdf';
        $view = view()->make('loan.payments.payment_loan')->with($data)->render();
        if ($standalone) return Util::pdf_to_base64([$view], $file_name, $information_loan, 'legal', $request->copies ?? 1);
        return $view;
    }

    public function get_information_loan(Loan $loan)
    {
        $file_name =implode(' ', ['Información:',$loan->code,$loan->modality->name,$loan->borrower->first()->fullname]); 
        return $file_name;
    }

    /**
    * Reactivar Registro de Pago
    * Reactiva un registro de pago
    * @urlParam loan_payment required ID del registro de pago. Example: 2
    * @authenticated
    * @responseFile responses/loan_payment/reactivate.200.json
    */
    public function reactivate($id)
    {
        $loanPayment = LoanPayment::withTrashed()->where('id', '=', $id)->first();
        if(!$loanPayment){
            abort(403, 'No existe el registro de pago a reactivar');
        }
        $loanPayment->restore();
        return $loanPayment;
    }

    public function deleteCanceledPaymentRecord(){
        $PendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;
        $dias = LoanGlobalParameter::latest()->first()->date_delete_payment;
        $loanPayment = LoanPayment::where('estimated_date','<=',Carbon::now()->subDay($dias))->whereStateId($PendientePago)->get();
        foreach ($loanPayment as $option) {
            $payment = LoanPayment::withoutEvents(function () use ($option){
                $payment = LoanPayment::find($option->id);
                $payment->state_id = LoanPaymentState::whereName('Anulado')->first()->id;
                $payment->update();
            });
        }
    }

    /**
    * Estado del Trámite de Cobro
    * Devuelve el estado del trámite de cobro
    * @urlParam loan_payment required ID de trámite de cobro. Example: 12
    * @authenticated
    * @responseFile responses/loan_payment/get_state.200.json
    */
    public function get_state(LoanPayment $loan_payment)
    {
        if ($loan_payment->state) return $loan_payment->state;
    }

    /**
    * Flujo de trabajo
    * Devuelve la lista de roles anteriores para devolver o posteriores para derivar el trámite
    * @urlParam loan_payment required ID del tramite de cobro. Example: 2
    * @authenticated
    * @responseFile responses/loan_payment/get_flow.200.json
    */
    public function get_flow(LoanPayment $loan_payment)
    {
        return response()->json(RoleSequence::flow($loan_payment->modality->procedure_type->id, $loan_payment->role_id));
    }

    /**
    * Voucher del registro de cobro
    * Devuelve el voucher del registro de cobro realizado por Tesoreria
    * @urlParam loan_payment required ID de trámite de cobro. Example: 1
    * @authenticated
    * @responseFile responses/loan_payment/get_voucher.200.json
    */
    public function get_voucher(LoanPayment $loan_payment)
    {
        if ($loan_payment->voucher_treasury){
            return $loan_payment->voucher_treasury;
        }
        abort(403, 'No existe el registro de pago');
    }


    /**
    * Importar y descargar excel por periodo(ANTIGUO)
    * genera los pagos del periodo y exporta el escel
	* @bodyParam estimated_date date Fecha para el cálculo del interés. Example: 2020-12-31
    * @authenticated
    * @responseFile responses/loan_payment/command_senasir_save_payment.200.json
    */
    public function download(Request $request)
    {
        $period = Carbon::parse($request->estimated_date)->format('M').'-'.Carbon::parse($request->estimated_date)->format('Y');
        $month = CarbonImmutable::parse($request->estimated_date)->format('M');
        $year = Carbon::parse($request->estimated_date)->format('Y');
        $file_name = $month.'-'.$year;
        $extension = '.xls';
        if(Storage::disk('public')->has($file_name.$extension)){
            return $file = Storage::disk('public')->download($file_name.$extension);
            //return $file = Storage::file($file_name.$extension);
        }
        else{
            $this->command_senasir_save_payment($request->estimated_date);
            $command_sheet=array(
                array("Fecha de desembolso", "numero", "Pagado por", "Tipo", "Matricula Titular", "Matricula Derecho Habiente", "CI", "Extension", "Primer Nombre", "Segundo Nombre", "Paterno", "Materno", "Saldo Actual", "Cuota", "Descuento", "Ciudad", "Interes")
            );
            $command_id = ProcedureModality::where('shortened', 'DES-COMANDO')->first();
            $loan_states = LoanPaymentState::whereName('Pendiente de Pago')->orWhere('name', 'Pendiente por confirmar')->get();
            $id = [];
            foreach($loan_states as $state)
                array_push($id, $state->id);
            $command = LoanPayment::where('estimated_date',$request->estimated_date)->where('procedure_modality_id', $command_id->id)->whereIn('state_id', $id)->get();
            foreach ($command as $row){
                if($row->loan->state->name == 'Vigente'){
                    array_push($command_sheet, array(
                        Carbon::parse($row->loan->disbursement_date)->format('d/m/Y H:m:s'),
                        $row->loan->code,
                        $row->paid_by,
                        $row->affiliate->affiliate_state->name,
                        $row->affiliate->registration,
                        $row->affiliate->registration,//verificar
                        $row->affiliate->identity_card,
                        $row->affiliate->city_identity_card->first_shortened,
                        $row->affiliate->first_name,
                        $row->affiliate->second_name,
                        $row->affiliate->last_name,
                        $row->affiliate->mothers_last_name,
                        $row->previous_balance,
                        $row->estimated_quota,
                        $row->estimated_quota,
                        $row->loan->city->name,
                        $row->loan->interest->annual_interest,
                    ));
                }
            }
            $command_sheet_deployed=array(
                array("N°", "PADRON DE LA ENTIDAD", "CI", "PATERNO", "MATERNO", "NOMBRE 1", "NOMBRE 2", "TOTAL DEUDA", "PLAZO", "MONTO DESC.")
            );

            $command_grouped = DB::table('loan_payments')->where('estimated_date', $request->estimated_date)->where('procedure_modality_id', $command_id->id)->whereIn('state_id', $id)
                            ->select('affiliate_id', DB::raw('sum(estimated_quota) as quota'))->groupBy('affiliate_id')->get();
            $c = 1;
            foreach ($command_grouped as $row){
                $affiliate = Affiliate::whereId($row->affiliate_id)->first();
                array_push($command_sheet_deployed, array(
                    $c,
                    '2345678021',
                    $affiliate->last_name,
                    $affiliate->mothers_last_name,
                    $affiliate->first_name,
                    $affiliate->second_name,
                    $row->quota,
                    '1',
                    $row->quota,
                ));$c++;
            }
            $senasir_sheet=array(
                array("Fecha de desembolso", "numero", "Pagado por", "Tipo", "Matricula Titular", "Matricula Derecho Habiente", "CI", "Extension", "Primer Nombre", "Segundo Nombre", "Paterno", "Materno", "Saldo Actual", "Cuota", "Descuento", "Ciudad", "Interes")
            );
            $senasir_id = ProcedureModality::where('shortened', 'DES-SENASIR')->first();
            $senasir = LoanPayment::where('estimated_date',$request->estimated_date)->where('procedure_modality_id', $senasir_id->id)->whereIn('state_id', $id)->get();
            foreach ($senasir as $row){
                if($row->loan->state->name == 'Vigente'){
                    array_push($command_sheet, array(
                        Carbon::parse($row->loan->disbursement_date)->format('d/m/Y'),
                        $row->loan->code,
                        $row->paid_by,
                        $row->affiliate->affiliate_state->name,
                        $row->affiliate->registration,
                        $row->affiliate->registration,//verificar
                        $row->affiliate->identity_card,
                        $row->affiliate->city_identity_card->first_shortened,
                        $row->affiliate->first_name,
                        $row->affiliate->second_name,
                        $row->affiliate->last_name,
                        $row->affiliate->mothers_last_name,
                        $row->previous_balance,
                        $row->estimated_quota,
                        $row->estimated_quota,
                        $row->loan->city->name,
                        $row->loan->interest->annual_interest,
                    ));
                }
            }
            $senasir_sheet_deployed=array(
                array("REGIONAL", "T-MATRICULA", "B-MATRICULA", "CI", "PATERNO", "MATERNO", "NOMBRES", "MONTO", "FECHA INI", "FECHA FIN")
            );
            $senasir_grouped = DB::table('loan_payments')->where('estimated_date', $request->estimated_date)->where('procedure_modality_id', $senasir_id->id)->whereIn('state_id', $id)
                            ->select('affiliate_id', DB::raw('sum(estimated_quota) as quota'))->groupBy('affiliate_id')->get();
            foreach ($senasir_grouped as $row){
                $affiliate = Affiliate::whereId($row->affiliate_id)->first();
                array_push($senasir_sheet_deployed, array(
                    $affiliate->city_identity_card->to_bank,
                    $affiliate->registration,
                    $affiliate->spouse ? $affiliate->spouse->registration : '',
                    $affiliate->identity_card,
                    $affiliate->last_name,
                    $affiliate->mothers_last_name,
                    $affiliate->first_name.' '.$affiliate->second_name,
                    $row->quota,
                    Carbon::now()->startOfMonth()->format('d/m/Y'),
                    Carbon::now()->endOfMonth()->format('d/m/Y'),
                    ));
            }
            $export = new FileWithMultipleSheets(/*$command_sheet_deployed, $senasir_sheet_deployed, */$command_sheet, $senasir_sheet);
            Excel::store($export, $file_name.$extension, 'public');
            return $file = Storage::disk('public')->download($file_name.$extension);
        }
        //return Excel::store($export, $File.'.xlsx', 'ftp');
    }

    /**
    * Listado de pagos automaticos por periodo
    * Devuelve Los pagos generados y pagados por un periodo
    * @bodyParam fecha periodo required fecha del periodo de pagos que se desea visualizar
    * @authenticated
    * @responseFile responses/loan_payment/payments_per_period.200.json
    */
    public function payments_per_period(Request $request)
    {
        $modalities = ProcedureModality::where('shortened', 'DES-COMANDO')->orWhere('shortened', 'DES-SENASIR')->get('id');
        $id = [];
        foreach($modalities as $modality)
            array_push($id, $modality->id);
        $payments = LoanPayment::whereIn('procedure_modality_id', $id)->where('estimated_date',$request->period)->get();
        foreach($payments as $payment)
            $payment->state = $payment->state;
        return $payments;
    }

    
    private function command_senasir_save_payment($estimated_date)
    {
        $estimated_date = $estimated_date? Carbon::parse($estimated_date) : Carbon::now()->endOfMonth();
        $loan_state = LoanState::where('name', 'Vigente')->first();
        $modalities = ProcedureModality::where('name', 'like', '%AFP')->get();
        $categorie=LoanPaymentCategorie::where('name','Regular')->where('type_register','SISTEMA')->first();

        $id = [];
        foreach($modalities as $modality)
        {
            array_push($id, $modality->id);
        }
        $loans = Loan::where('state_id', $loan_state->id)->whereNotIn('procedure_modality_id', $id)->get();
        $description = 'Por descuento automatico';
        $procedure_modality_command = ProcedureModality::whereShortened('DES-COMANDO')->first();//revisar
        $procedure_modality_senasir = ProcedureModality::whereShortened('DES-SENASIR')->first();//revisar
        $mestimated_date = $estimated_date->month;
        $yestimated_date = $estimated_date->year;
        $voucher = "AUT".'-'.'0'.$mestimated_date.'/'.$yestimated_date;
        $loans_quantity = 0;

        foreach($loans as $loan){
            $date_payment = Carbon::parse($loan->disbursement_date)->endOfMonth()->format('Y-m-d');
            $disbursement_day = Carbon::parse($loan->disbursement_date)->day;
            if($loan->balance != 0){
                if($loan->modality->procedure_type->name == 'Préstamo a Largo Plazo' || $loan->modality->procedure_type->name == 'Préstamo a Corto Plazo'){
                    if(!$loan->guarantor_amortizing){
                        foreach($loan->lenders as $lender)
                        {
                            $paid_by = "T";
                            if($lender->affiliate_state->name == 'Servicio' || $lender->affiliate_state->name == 'Disponibilidad') 
                                $procedure_modality = $procedure_modality_command;
                            if($lender->affiliate_state->name == 'Jubilado' && $lender->affiliate_state && $lender->pension_entity->name == 'SENASIR') 
                                $procedure_modality = $procedure_modality_senasir;
                            if($disbursement_day <= LoanGlobalParameter::latest()->first()->offset_interest_day && Carbon::parse($estimated_date)->toDateString() == Carbon::parse($date_payment)->toDateString()){
                                LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $lender->pivot->quota_treat, $lender->id);
                                $loans_quantity++;
                            }
                            else{
                                if(Carbon::parse($estimated_date)->toDateString() > Carbon::parse($date_payment)->toDateString())
                                {
                                    LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $lender->pivot->quota_treat, $lender->id);
                                    $loans_quantity++;
                                }
                            }
                        }
                    }
                    else
                    {
                        foreach($loan->guarantors as $guarantor)
                        {
                            $paid_by = "G";
                            if($guarantor->affiliate_state->name == 'Servicio' || $guarantor->affiliate_state->name == 'Disponibilidad') $procedure_modality = $procedure_modality_command;
                            if($guarantor->affiliate_state->name == 'Jubilado' && $guarantor->affiliate_state && $guarantor->pension_entity->name == 'SENASIR') $procedure_modality = $procedure_modality_senasir;
                            if($disbursement_day <= LoanGlobalParameter::latest()->first()->offset_interest_day && Carbon::parse($estimated_date)->toDateString() == Carbon::parse($date_payment)->toDateString()){
                                LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $guarantor->pivot->quota_treat, $guarantor->id);
                                $loans_quantity++;
                            }
                            else{
                                if(Carbon::parse($estimated_date)->toDateString() > Carbon::parse($date_payment)->toDateString())
                                {
                                    LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $guarantor->pivot->quota_treat, $guarantor->id);
                                    $loans_quantity++;
                                }
                            }
                        }
                    }
                }
                else{
                    foreach($loan->lenders as $lender)
                    {
                        $paid_by = "T";
                        if($lender->affiliate_state->name == 'Servicio' || $lender->affiliate_state->name == 'Disponibilidad') $procedure_modality = $procedure_modality_command;
                        if($lender->affiliate_state->name == 'Jubilado' && $lender->pension_entity && $lender->pension_entity->name == 'SENASIR') $procedure_modality = $procedure_modality_senasir;
                        if($disbursement_day <= LoanGlobalParameter::latest()->first()->offset_interest_day && Carbon::parse($estimated_date)->toDateString() == Carbon::parse($date_payment)->toDateString()){
                            LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $lender->pivot->quota_treat, $lender->id);
                            $loans_quantity++;
                        }
                        else{
                            if(Carbon::parse($estimated_date)->toDateString() > Carbon::parse($date_payment)->toDateString())
                            {
                                LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality->id, $voucher, $paid_by, $lender->pivot->quota_treat, $lender->id);
                                $loans_quantity++;
                            }
                        }
                    }
                }
            }
        }
        return response()->json([
            'loans_quantity' => $loans_quantity
        ]);
    }

    /**
    * Importación pendientes de Pagos Comando SENASIR
    * Realiza la importación de pagos.
	* @bodyParam file file required Archivo de importación. Example: file.xls
    * @bodyParam state boolean required Tipo importacion Activo(1) o Pasivo(0). Example: 1
    * @bodyParam estimated_date date Fecha estimada para la importacion. Example: 2020-12-31
    * @bodyParam voucher_payment string Comprobante de pago. Example: D-12/20
    * @authenticated
    * @responseFile responses/loan_payment/importation_pending.200.json
    */
    public function importation_pending_command_senasir(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'state'=> 'required|boolean',
            'estimated_date'=> 'nullable|date_format:"Y-m-d"',
            'voucher_payment' => 'nullable|string|min:3'
        ]);

        $file = $request->file('file');
        $json = collect([]);
        $payment_automatic = collect([]);//pagos automaticos confirmados
        $payment_no_automatic = collect([]);//pagos no efectivizados
        $array = Excel::toArray(new LoanPaymentImport, $file);
        $pendientePago = LoanPaymentState::whereName('Pendiente de Pago')->first()->id;
        $pendienteConfirmation = LoanPaymentState::whereName('Pendiente por confirmar')->first()->id;
        $pagado = LoanPaymentState::whereName('Pagado')->first()->id;
        $procedure_modality_automatic = ProcedureModality::whereName('A.AUT. Cuota pactada')->first();//revisar
        $procedure_modality_parcial = ProcedureModality::whereName('A.AUT. Parcial')->first();//revisar
        $estimated_date_importation = $request->estimated_date? Carbon::parse($request->estimated_date) : Carbon::now()->endOfMonth();
       // $payment_type = AmortizationType::get();
        //$payment_type_desc = $payment_type->where('name', 'LIKE', 'Descuento automático')->first();
        $categorie=LoanPaymentCategorie::where('name','Regular')->where('type_register','SISTEMA')->first();
        $contand=0;
        $concatenando='';
        $concatenandoCi='';
        $loanAll=collect([]);
        $loanPayments = new LoanPayment();

        $amount_Affiliate=0;
        
            for($i=1;$i<count($array[0]);$i++){   
                $amount_Affiliate = $array[0][$i][1];
                
                $totalLoanAmount = 0; 
                $have_payment=false;

                if($request->state){
                    $ci=(int)$array[0][$i][0];
                    $affiliate = Affiliate::where('identity_card', '=',$ci)->first();                    
                }else{
                    $matricula= $array[0][$i][0];
                    $affiliate = Affiliate::where('registration', '=',$matricula)->first();
                }
               
                $loanPayments = LoanPayment::where('affiliate_id',$affiliate->id)->where('state_id','=',$pendienteConfirmation)
                                            ->where('procedure_modality_id','=',$procedure_modality_automatic->id)->where('estimated_date','=',$estimated_date_importation)->get();

                
                foreach ($loanPayments as $loanPayment){
                      $payment_estimated_date=Carbon::parse($loanPayment->estimated_date);
                        $totalLoanAmount = $totalLoanAmount + $loanPayment->estimated_quota;
                        $have_payment=true;
                }
                
                if ($totalLoanAmount == $array[0][$i][1] && $have_payment){
                    foreach ($loanPayments as $loanPayment){
                        $loanPayment->state_id = $pagado;
                        if($request->voucher_payment){
                            $loanPayment->voucher = $request->voucher_payment;
                        }
                        $loanPayment->validated = true;
                        $loanPayment->user_id = auth()->id();
                        $loanPayment->update();
                        $payment_automatic->push($loanPayment);
                    }
                }else{
                    foreach ($loanPayments as $loanPayment){
                        $payment_no_automatic->push($loanPayment);
                    }
                }
            }
            return response()->json([
                'payments_automatic' => $payment_automatic,
                'payments_no_automatic' => $payment_no_automatic,
               // 'Contandooo ' =>  $contand,
                //'$concatenando' =>  $concatenando,
               // 'todosloans' => $loanLender,
               // 'Concatenando'=>$concatenandoCi
            ]);
    }

     /**
    * Importación de Pagos Comando SENASIR
    * Realiza la importación de pagos.
	* @bodyParam file file required Archivo de importación. Example: file.xls
    * @bodyParam state boolean required Tipo importacion Activo(1) o Pasivo(0). Example: 1
    * @bodyParam estimated_date date Fecha estimada para la importacion. Example: 2020-12-31
    * @bodyParam voucher_payment string Comprobante de pago. Example: D-12/20
    * @authenticated
    * @responseFile responses/loan_payment/importation_payments.200.json
    */
    public function importation_command_senasir(Request $request)
    {
        $request->validate([
           'file' => 'required',
            'state'=> 'required|boolean',
            'estimated_date'=> 'nullable|date_format:"Y-m-d"',
            //'voucher_payment' => 'nullable|string|min:3'
        ]);
       
        $file = $request->file('file');
        $json = collect([]);
        $payment_automatic = collect([]);//pagos automaticos confirmados
        $payment_no_automatic = collect([]);//pagos no efectivizados
        $array = Excel::toArray(new LoanPaymentImport, $file);
       // $procedure_modality_comando = ProcedureModality::whereShortened("DES-COMANDO")->first()->id;
       // $procedure_modality_senasir = ProcedureModality::whereShortened("DES-SENASIR")->first()->id;
       //amorttizacion a
        $procedure_type_id = ProcedureType::whereName("Amortización Automática")->first()->id;
        $pendiente_confirmar_id = LoanPaymentState::whereName('Pendiente por confirmar')->first()->id;
        $pagado = LoanPaymentState::whereName('Pagado')->first()->id;
        $categorie=LoanPaymentCategorie::where('name','Regular')->where('type_register','SISTEMA')->first();

        $estimated_date_importation = $request->estimated_date? Carbon::parse($request->estimated_date) : Carbon::now()->endOfMonth();
        $mestimated_date = $estimated_date_importation->month;
        $yestimated_date = $estimated_date_importation->year;
        $voucher_enter= $request->voucher_payment? "AUT".'-'.'0'.$mestimated_date.'/'.$yestimated_date : "AUT".'-'.'0'.$mestimated_date.'/'.$yestimated_date;
        $contand=0;
        $concatenando='';
        $concatenandoCi='';
        $loanAll=collect([]);
        $loanPayments = new LoanPayment();
        $amount_more_affiliate=collect([]);
        $amount_Affiliate=0;
            for($i=1;$i<count($array[0]);$i++){   
                $amount_Affiliate = $array[0][$i][1];
                $totalLoanAmount = 0; 
                $have_payment = false;
                if($request->state){//comando
                    $procedure_modality_id = ProcedureModality::whereShortened("DES-COMANDO")->first()->id;
                    $ci=$array[0][$i][0];
                    $affiliate = Affiliate::whereIdentityCard($ci)->first(); 
                    if($affiliate != NULL){
                        $loanPayments = LoanPayment::where('affiliate_id', $affiliate->id)->where('state_id',$pendiente_confirmar_id)
                    ->where('procedure_modality_id', $procedure_modality_id)->where('estimated_date',$estimated_date_importation)->get();
                    }               
                }else{ //senasir
                    $matricula= $array[0][$i][0];
                    $affiliate = Affiliate::whereRegistration($matricula)->first();
                    if($affiliate == null){
                        $affiliate = Spouse::whereRegistration($matricula)->first()->affiliate; //verificar logica     
                    }                    
                    $procedure_modality_id=ProcedureModality::whereShortened("DES-SENASIR")->first()->id;
                    $loanPayments = LoanPayment::where('affiliate_id',$affiliate->id)->where('state_id',$pendiente_confirmar_id)
                    ->where('procedure_modality_id',$procedure_modality_id)->where('estimated_date',$estimated_date_importation)->get();
                }
                foreach ($loanPayments as $loanPayment){                
                      $payment_estimated_date = Carbon::parse($loanPayment->estimated_date);
                        $totalLoanAmount = $totalLoanAmount + $loanPayment->estimated_quota; 
                        $have_payment = true;
                }
               
                if ($totalLoanAmount == $array[0][$i][1] && $have_payment){
                    foreach ($loanPayments as $loanPayment){
                        $loanPayment->state_id = $pagado;
                        $loanPayment->voucher = $voucher_enter;
                        $loanPayment->validated = true;
                        $loanPayment->user_id = auth()->id();
                        $loanPayment->loan_payment_date = Carbon::now();
                        $loanPayment->update();       
                        $payment_automatic->push($loanPayment);
                    }
                }else{
                    $amount_Affiliate = $array[0][$i][1];
                    $loanLender = collect([]);
                    $loanPaymentsLender = collect([]);
                    $loanGuarantor = collect([]);
                    $loanPaymentsGuarantor = collect([]);
                    foreach ($loanPayments as $loanPayment){
                        if($loanPayment->paid_by == 'T') $loanLender->push($loanPayment->loan);
                        if($loanPayment->paid_by == 'G') $loanGuarantor->push($loanPayment->loan);
                    }
                    //aqui entraria el metodo 
                    //$this->amortization_loan_priorities($loanLender,$loanPayments,$amount_Affiliate,'T',$estimated_date_importation,$affiliate,$request->voucher_payment);
                    
                    $loanLender=$loanLender->sortBy('disbursement_date',SORT_NATURAL);
                    $loanLender=$loanLender->values()->all();//ordenado de  deacuerdo a lo mas antiguo
                   
                    foreach($loanLender as $loanLenderPayment){
                        //$concatenando=$concatenando.' '.$amount_Affiliate.$i;//pruebas
                        
                        $has_affiliate_balance = true;
                        $loanPaymentsLender = $loanPayments->where('paid_by','T')->where('loan_id',$loanLenderPayment->id);//Aqui

                        $loanPaymentsLender=$loanPaymentsLender->first();
                      
                                if($loanPaymentsLender->estimated_quota <= $amount_Affiliate){
                                    $loanPaymentsLender->state_id = $pagado;
                                    $loanPaymentsLender->validated = true;
                                    $loanPaymentsLender->user_id = auth()->id();
                                    $loanPaymentsLender->loan_payment_date = Carbon::now();
                                    $loanPaymentsLender->update();
                               
                                    $payment_automatic->push($loanPaymentsLender);
                                    $amount_Affiliate = $amount_Affiliate - $loanPaymentsLender->estimated_quota;

                                }else{//si el registro de pago es mayor a 0
                                    if($amount_Affiliate > 0){
                                        $loan = $loanPaymentsLender->loan;
                                        $estimated_date=$estimated_date_importation;
                                        $description=$loanPaymentsLender->description;
                                        $procedure_modality=$procedure_modality_id;
                                        $voucher_pago=$loanPaymentsLender->voucher;
                                        $paid_by=$loanPaymentsLender->paid_by;
                                        $estimated_quota=$amount_Affiliate;
                                        $lender = $affiliate;
                                        $loanPaymentsLender->delete();
                                        $validated_payment = false;
                                        $state_id = $pagado;
                                        $new_loanPayment = LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality, $voucher_pago, $paid_by,$estimated_quota,$lender->id, $state_id, $validated_payment);
                                        $new_loanPayment->user_id = auth()->id();
                                        $new_loanPayment->state_id = $pagado;
                                        $new_loanPayment->validated = true;
                                        $new_loanPayment->loan_payment_date = Carbon::now();
                                        $new_loanPayment->update();
                                        $payment_automatic->push($new_loanPayment);
                                        $amount_Affiliate = $amount_Affiliate - $new_loanPayment->estimated_quota;
                                       //mono affiliado 
                                    }else{
                                        $payment_no_automatic->push($loanPaymentsLender);
                                    }
                                }
                    }//para garantias
                    if($amount_Affiliate>0){
                        $loanGuarantor=$loanGuarantor->sortBy('disbursement_date',SORT_NATURAL);
                        $loanGuarantor=$loanGuarantor->values()->all();//ordenado de  deacuerdo a lo mas antiguo

                        //prestamos garantizados 
                        foreach($loanGuarantor as $loanGuarantorPayment){
                            $loanPaymentsGuarantor = $loanPayments->where('paid_by','G')->where('loan_id',$loanGuarantorPayment->id);//Aqui

                            $loanPaymentsGuarantor = $loanPaymentsGuarantor->first();

                            if($loanPaymentsGuarantor->estimated_quota <= $amount_Affiliate){
                                $loanPaymentsGuarantor->state_id = $pagado;
                                $loanPaymentsGuarantor->validated = true;
                                $loanPaymentsGuarantor->user_id = auth()->id();
                                $loanPaymentsGuarantor->loan_payment_date = Carbon::now();
                                $loanPaymentsGuarantor->update();
                                $payment_automatic->push($loanPaymentsLender);
                                $amount_Affiliate=$amount_Affiliate - $loanPaymentsGuarantor->estimated_quota;

                            }else{//si el registro de pago es mayor a 0

                                if($amount_Affiliate > 0){
                                    $loan=$loanPaymentsGuarantor->loan;
                                    $estimated_date=$estimated_date_importation;
                                    $description=$loanPaymentsGuarantor->description;
                                    $procedure_modality=$procedure_modality_id;
                                    $voucher_pago=$loanPaymentsGuarantor->voucher;
                                    //$voucher=$loanPaymentsLender->voucher;
                                    $paid_by=$loanPaymentsGuarantor->paid_by;
                                    //$percentage_quota = 100;
                                    $lender=$affiliate;
                                    $loanPaymentsGuarantor->delete();
                                    $estimated_quota = $amount_Affiliate;
                                   /* if($request->voucher_payment){
                                        $voucher = $voucher_enter;
                                    }else{
                                        $voucher=$loanPaymentsGuarantor->voucher;
                                    }*/   
                                    $state_id = $pagado;
                                    $validated_payment = false;
                                    //registro del pago
                                    $new_loanPayment = LoanPayment::registry_payment($categorie->id,$loan, $estimated_date, $description, $procedure_modality, $voucher_pago, $paid_by, $estimated_quota,$lender->id, $state_id,$validated_payment );
                                    $new_loanPayment->validated = true;
                                    $new_loanPayment->state_id = $pagado;
                                    $new_loanPayment->user_id = auth()->id();
                                    $new_loanPayment->loan_payment_date = Carbon::now();
                                    $new_loanPayment->update();
                                    $payment_automatic->push($new_loanPayment);
                                    
                                    $amount_Affiliate = $amount_Affiliate - $new_loanPayment->estimated_quota;//mono affiliado 
                                }else{
                                    $payment_no_automatic->push($loanPaymentsGuarantor);
                                }
                            }
                        }
                    }
                    //verifica si el monto es mayor a garantias
                    if($amount_Affiliate>0 && $affiliate != null ){
                        $affiliate_mount = (object)['ci' => $affiliate->identity_card,'matricula' => $affiliate->registration,'monto_excedente' => $amount_Affiliate,'Estado afiliado' => $affiliate->registration];
                        $amount_more_affiliate->push($affiliate_mount);
                    }
                    // $payment_no_automatic->push($loanPaymentsLender);
                }
            }

           /* return response()->json([
                'payments_automatic' => $payment_automatic,
                'payments_no_automatic' => $payment_no_automatic,
                'amount_more_affiliate'=> $amount_more_affiliate
               // 'Contandooo ' =>  $contand,
                //'$concatenando' =>  $concatenando,
               // 'todosloans' => $loanLender,
               // 'Concatenando'=>$concatenandoCi
            ]);*/

        $File=$estimated_date_importation."AffiliadosConPagosExcedentes";
        $data = array(
            array("CI", "Matrícula", "Monto excedente")
        );
        if($amount_more_affiliate != null){
           
        foreach ($amount_more_affiliate as $row){
            array_push($data, array(
                $row->ci,
                $row->matricula,
                $row->monto_excedente
            ));
        }
       } 
        $export = new ArchivoPrimarioExport($data);
        return Excel::download($export, $File.'.xls');


    }



    public function import_payments(request $request)
    {
        $tempory = "create temporary table payments_aux(period_id integer, identity_card varchar, amount float)";
        $temporary = DB::select($temporary);

        $copy = "copy payments_aux(identity_card, amount)
                FROM '$request->ubication'
                WITH DELIMITER ':' CSV header;";
        $copy = DB::select($copy);

        $update = "update payments_aux
                    set period_id = 9";
        $update = DB::select($update);

        $update2 = "update payments_aux
                    set identity_card = REPLACE(LTRIM(REPLACE(identity_card,'0',' ')),' ','0')";
        $update2 = DB::select($update2);

        $insert = "INSERT INTO loan_payment_copy_commands(period_id, identity_card, amount)
                    SELECT period_id, identity_card, amount FROM payments_aux;";
        $insert = DB::select($insert);

        $drop = "drop table if exists payments_aux";
        $drop = DB::select($drop);

        $consult = "select count(*) from loan_payment_copy_commands where period_id = 9";
        $consult = DB::select($consult);

        return $consult;
    }
}