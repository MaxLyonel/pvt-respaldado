<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{$file_title}}</title>
    <link rel="stylesheet" href="{{ public_path("/css/report-print.min.css") }}" media="all"/>
</head>
<body>
    @include('partials.header', $header)
<div class="block">
        <div class="font-semibold leading-tight text-center m-b-10 text-lg">FORMULARIO DE CALIFICACIÓN Y APROBACIÓN DE PRÉSTAMO</div>
        @php ($lender = $lenders->first())
</div>
@php ($n = 1)

<div class="block">
        <div class="font-semibold leading-tight text-left m-b-10 text-sm">{{ $n++ }}. DATOS DEL TRÁMITE {{$Loan_type_title}}</div>
    </div>

<div class="block">
        <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10">
            <tr class="bg-grey-darker text-white">
                <td class="w-25">Código Tŕamite</td>
                @if ($loan->parent_loan || $loan->parent_reason)
                <td class="w-25">Trámite origen</td>
                @endif
                <td class="{{ $loan->parent_loan ? 'w-50' : 'w-75' }}" colspan="{{ $loan->parent_loan ? 1 : 2 }}">Modalidad de trámite</td>
            </tr>
            <tr>
                <td class="data-row py-5">{{ $loan->code }}</td>
                @if ($loan->parent_loan)
                <td class="data-row py-5">{{ $loan->parent_loan->code }}</td>
                @endif
                @if ($loan->parent_reason && !$loan->parent_loan_id)
                <td class="data-row py-5">{{ $loan->data_loan->code }}</td>
                @endif
                <td class="data-row py-5" colspan="{{ $loan->parent_loan ? 1 : 2 }}">@if($loan->parent_reason == "REPROGRAMACIÓN") {{$loan->parent_reason}} @endif {{ $loan->modality->name }}</td>
            </tr>
            <tr class="bg-grey-darker text-white">
                <td>Monto solicitado</td>
                <td>Plazo</td>
                <td>Tipo de Desembolso</td>
            </tr>
            <tr>
                <td class="data-row py-5">{{ Util::money_format($loan->amount_approved) }} <span class="capitalize">Bs.</span></td>
                <td class="data-row py-5">{{ $loan->loan_term }} <span class="capitalize">Meses</span></td>
                <td class="data-row py-5">
                    @if($loan->payment_type->name=='Deposito Bancario')
                        <div class="font-bold">Cuenta Banco Union</div>
                        <div>{{ $loan->number_payment_type }}</div>
                    @else
                        {{ $loan->payment_type->name}}
                    @endif
                </td>
            </tr>
        </table>
    </div>
    @if($Loan_type_title == "REFINANCIAMIENTO" || $Loan_type_title == "SISMU REFINANCIAMIENTO")
    <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10">
            <tr class="bg-grey-darker text-white">
                <td colspan="2" >REFINANCIAMIENTO DE PRÉSTAMO</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">FECHA DE SALDO DEUDOR </td>
            <td class="w-50 text-left px-10">{{Carbon::parse($loan->date_cut_refinancing())->format('d/m/y')}}
            </td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">SALDO DEUDOR EN Bs. </td>
            <td class="w-50 text-left px-10">{{Util::money_format($loan->balance_parent_refi())}}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">MONTO REFINANCIAMIENTO EN Bs.</td>
            <td class="w-50 text-left px-10">{{Util::money_format($loan->refinancing_balance)}}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">TOTAL NUEVO MONTO DE PRÉSTAMO</td>
            <td class="w-50 text-left px-10">{{Util::money_format($loan->amount_approved)}}</td>
            </tr> 
        </table>
        @endif
    <div class="block">
        <div class="font-semibold leading-tight text-left m-b-10 text-sm">{{ $n++ }}. DATOS DE BOLETA</div>
    </div>
<div class="block">
    <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10">        
        @php ($sum_prom_payable_liquid_calculated = 0)
        @php ($sum_prom_bonus_calculated = 0)
        @php ($count_lender = 0)
        @foreach($lenders as $lender_affiliate_loan)
            @php ($lender_affiliate = $lender_affiliate_loan->affiliate())
            @php ($count_lender = $count_lender + 1)
        <tr class="bg-grey-darker text-white">
            <td class=" text-left px-10"colspan="7"> "PRESTATARIO" {{$lender_affiliate_loan->full_name}} </td>
        </tr>
            @php ($sum_prom_payable_liquid_calculated += $lender_affiliate_loan->payable_liquid_calculated)
            @php ($sum_prom_bonus_calculated += $lender_affiliate_loan->bonus_calculated)
        @if($lender_affiliate_loan->contributionable_type == "contributions")
            <tr>
                <td class="bg-grey-darker text-white">Periodo</td>
                <td class="bg-grey-darker text-white">Liquido</td>
                <td class="bg-grey-darker text-white">Monto de Ajuste</td>
                <td class="bg-grey-darker text-white">Bono Frontera</td>
                <td class="bg-grey-darker text-white">Bono Cargo</td>
                <td class="bg-grey-darker text-white">Bono Oriente</td>
                <td class="bg-grey-darker text-white">Bono Seguridad Ciudadana</td>       
           </tr>        
           @foreach($loan->borrower->first()->ballots->ballot_adjusts as $ballot)
           <tr>
                <td>{{Carbon::parse($ballot['month_year'])->format('d/m/y')}}</td>
                <td> {{Util::money_format($ballot['payable_liquid'])}}</td>
                <td> {{Util::money_format($ballot['mount_adjust'])}}</td>  
                <td> {{Util::money_format($ballot['border_bonus'])}}</td>
                <td> {{Util::money_format($ballot['position_bonus'])}}</td>
                <td> {{Util::money_format($ballot['east_bonus'])}}</td>
                <td> {{Util::money_format($ballot['public_security_bonus'])}}</td>                       
           </tr>     
            @endforeach
            @php($average = $loan->borrower->first()->ballots->average_ballot_adjust->first())
           <tr>
                @php ($title_total = " ")
                @if($loan->modality->loan_modality_parameter->quantity_ballots >1)
                    @php ($title_total = "PROMEDIO")
                @endif
                    @php ($a = $title_total == "PROMEDIO" ? "DE LA":" ")

                <td>Total {{$title_total}}</td>
                <td> {{Util::money_format($average['average_payable_liquid'])}}</td>
                <td> {{Util::money_format($average['average_mount_adjust'])}}</td>
                <td> {{Util::money_format($average['average_border_bonus'])}}</td>
                <td> {{Util::money_format($average['average_position_bonus'])}}</td>
                <td> {{Util::money_format($average['average_east_bonus'])}}</td>
                <td> {{Util::money_format($average['average_public_security_bonus'])}}</td>          
           </tr>
        @endif
        @if($lender_affiliate_loan->contributionable_type =="aid_contributions")
            <tr>
                <td class="bg-grey-darker text-white">Periodo</td>
                <td class="bg-grey-darker text-white">Liquido</td>
                <td class="bg-grey-darker text-white">Monto de Ajuste</td>
                <td class="bg-grey-darker text-white">Bono Renta Dignidad</td>          
           </tr>
           @foreach($loan->borrower->first()->ballots->ballot_adjusts as $ballot)
            <tr>
                <td> {{Carbon::parse($ballot['month_year'])->format('d/m/y')}}</td> 
                <td> {{Util::money_format($ballot['payable_liquid'])}}</td> 
                <td> {{Util::money_format($ballot['mount_adjust'])}}</td>  
                <td> {{Util::money_format($ballot['dignity_rent'])}}</td>                 
            </tr >
            @endforeach      
            @php($average = $loan->borrower->first()->ballots->average_ballot_adjust->first())
            <tr>
                <td>Total Promedio</td>
                <td> {{Util::money_format($average['average_payable_liquid'])}}</td>
                <td> {{Util::money_format($average['average_mount_adjust'])}}</td>
                <td> {{Util::money_format($average['average_dignity_rent'])}}</td>             
             </tr>
        @endif
        @if($lender_affiliate_loan->contributionable_type == "loan_contribution_adjusts")
           <tr>
                <td class="bg-grey-darker text-white">Periodo</td>
                <td class="bg-grey-darker text-white">Liquido</td>
                <td class="bg-grey-darker text-white">Monto de Ajuste</td>
           </tr>
           @foreach($loan->borrower->first()->ballots->ballot_adjusts as $ballot)
            <tr>
                <td>{{Carbon::parse($ballot['month_year'])->format('d/m/y')}}</td>  
                <td>{{Util::money_format($ballot['payable_liquid'])}}</td> 
                <td>{{Util::money_format($ballot['mount_adjust'])}}</td>            
            </tr >   
            @endforeach
            @php($average = $loan->borrower->first()->ballots->average_ballot_adjust->first())
            <tr>
                <td>Total Promedio</td>
                <td> {{Util::money_format($average['average_payable_liquid'])}}</td> 
                <td> {{Util::money_format($average['average_mount_adjust'])}}</td>            
             </tr>
        @endif
        @endforeach
    </table>
        <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10"> 
            <tr class="bg-grey-darker text-white">
            @php ($title_average = " ")
            @if($loan->modality->loan_modality_parameter->quantity_ballots >1)
                @php ($title_average = "PROMEDIO")
            @endif
                @php ($a = $title_average == "PROMEDIO" ? "DE LA":" ")
            <td colspan="2" class="w-100">{{$title_average }} {{$a}} BOLETA</td>
            </tr>
            <tr >
            <td class="w-50 text-left px-10">TOTAL {{$title_average }} LÍQUIDO PAGABLE</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($sum_prom_payable_liquid_calculated)}} </td>
            </tr>
            <tr >
            <td class="w-50 text-left px-10">TOTAL {{$title_average }} BONOS</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($sum_prom_bonus_calculated)}}</td>
            </tr>
            <tr >
            <td class="w-50 text-left px-10">LÍQUIDO PARA CALIFICACIÓN</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->liquid_qualification_calculated) }}</td>      
            </tr>
        </table>
    </div>
   </div>
   <div class="block">
        <div class="font-semibold leading-tight text-left m-b-10 text-sm">{{ $n++ }}. DATOS DE EVALUACIÓN AL PRESTATARIO</div>
    </div>
    <div class="block">
        <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10">
            <tr class="bg-grey-darker text-white">
                <td colspan="2" >PROPUESTA Y APROBACIÓN </td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">NOMBRES</td>
            <td class="w-50 text-left px-10">{{ $lender->full_name }}
            </td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">INTERÉS CORRIENTE</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->interest->annual_interest) }} % Anual</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">INTERÉS PENAL</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->interest->penal_interest) }} % Anual</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">LÍQUIDO PARA CALIFICACIÓN</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->liquid_qualification_calculated) }}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">MONTO DEL PRÉSTAMO AUTORIZADO</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->amount_approved) }}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">PLAZO EN MESES</td>
            <td class="w-50 text-left px-10">{{ $loan->loan_term }}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">CUOTA MENSUAL</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->estimated_quota) }}</td>
            </tr>
            <tr  class="w-100">
            <td class="w-50 text-left px-10">ÍNDICE DE ENDEUDAMIENTO</td>
            <td class="w-50 text-left px-10">{{ Util::money_format($loan->indebtedness_calculated) }} %</td>
            </tr>      
        </table>
    </div>
    @php($plural = $loan->guarantors()->count() > 1)
    @php ($num_gar = 1)
    @if ($loan->guarantors()->count())
    <div class="block">
        <div class="font-semibold leading-tight text-left m-b-10 text-sm">{{ $n++ }}. DATOS DE EVALUACIÓN DE{{ $plural ? ' LOS' : 'L' }} GARANTE{{ $plural ? 'S' : ''}}</div>
    </div>
    <div class="block ">
        @foreach ($guarantors as $guarantor)
        <table style="font-size:12px;" class="table-info w-100 text-center uppercase my-10">
            <tr class="bg-grey-darker text-white">
                    <td class="w-100" colspan="2">Garante {{ $num_gar++ }}</td>               
            </tr>
            <tr class="w-100">
                <td class="w-50 text-left px-10">NOMBRES</td>
                <td class="w-50 text-left px-10">{{ $guarantor->full_name }}</td> 
            </tr>
            <tr class="w-100">
                <td class="w-50 text-left px-10">LÍQUIDO PARA CALIFICACIÓN</td>
                <td class="w-50 text-left px-10">{{ Util::money_format($guarantor->liquid_qualification_calculated) }}</td> 
            </tr>
            <tr class="w-100">
                <td class="w-50 text-left px-10">PORCENTAJE DE PAGO</td>
                <td class="w-50 text-left px-10">{{ $guarantor->payment_percentage }} %</td> 
            </tr>
        </table>
        @endforeach
    </div>
    @endif
    <table class="text-center">
            <tbody>
                <tr class="align-top">                  
                <td width="35%">                  
                ELABORADO POR
                </td>
                <td width="35%">
                APROBADO POR 
                </td>
                <td width="30%">
                AUTORIZADO POR
                </td>
                </tr>             
            </tbody>
        </table>
</body>
</html>
