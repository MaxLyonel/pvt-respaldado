<?php

namespace App\Helpers;

use Carbon;
use Config;
use App\RecordType;
use App\Role;
use App\RoleSequence;
use App\loanPayment;
use Illuminate\Support\Facades\Auth;
use App\Events\LoanFlowEvent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\LoanCorrelative;
use Illuminate\Support\Facades\Http;
use App\Notification\NotificationCarrier;
use App\Notification\NotificationNumber;
use App\Notification\NotificationSend;
use Illuminate\Support\Facades\DB;
use App\Loan;

class Util
{
    private static $display_names = ['display_name', 'name', 'code', 'shortened', 'number', 'correlative', 'description'];

    public static function trim_spaces($string)
    {
        return preg_replace('/[[:blank:]]+/', ' ', $string);
    }

    public static function bool_to_string($value)
    {
        if (is_bool($value)) {
            if ($value) {
                $value = 'SI';
            } else {
                $value = 'NO';
            }
        } else {
            try {
                $value = Carbon::createFromFormat('Y-m-d', $value)->format('d-m-Y');
            } catch (\Exception $e) {}
        }
        return $value;
    }

    public static function translate($string)
    {
        $translation = static::translate_table($string);
        if ($translation) {
            return $translation;
        } else {
            return static::translate_attribute($string);
        }
    }

    public static function translate_table($string)
    {
        if (array_key_exists($string, Config::get('translations'))) {
            return Config::get('translations')[$string];
        } else {
            return null;
        }
    }

    public static function translate_attribute($string)
    {
        $path = app_path() . '/resources/lang/es/validation.php';
        if(@include $path) {
            $translations_file = include(app_path().'/resources/lang/es/validation.php');
        }
        if (isset($translations_file)) {
            if (array_key_exists($string, $translations_file['attributes'])) {
                return $translations_file['attributes'][$string];
            }
        }
        return $string;
    }

    public static function round($value)
    {
        return round($value, 4, PHP_ROUND_HALF_EVEN);
    }

    public static function round2($value)
    {
        return round($value, 2, PHP_ROUND_HALF_EVEN);
    }

    public static function money_format($value, $literal = false)
    {
        if ($literal) {
            $f = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $data = $f->format(intval($value)) . ' ' . explode('.', number_format(round($value, 2), 2))[1] . '/100';
            $mil = explode(" ",$data);
            $mil = $mil[0] == "mil" ? 'un ':"";
            $data =   $mil.$data;

        } else {
            $data = number_format($value, 2, ',', '.');
        }
        return $data;
    }

    public static function number_integer($value, $literal = false)
    {
        if ($literal) {
            $f = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $data = $f->format(intval($value));
        }
        return $data;
    }

    public static function search_sort($model, $request, $filter = [], $relations = [], $pivot = [])
    {
        $query = $model::query();
        if (count($relations) > 0) {
            foreach ($relations as $relation => $constraints) {
                if (count($pivot) > 0) {
                    $query = $query->with([$relation => function ($q) use ($pivot) {
                        $q->select($pivot);
                    }]);
                }
                if (count($constraints) > 0) {
                    $query = $query->whereHas($relation, function($q) use ($constraints) {
                        foreach ($constraints as $column => $constraint) {
                            $q->where($column, $constraint);
                        }
                        return $q;
                    });
                }
            }
        }
        foreach ($filter as $column => $constraint) {
            if (!is_array($constraint)) $constraint = ['=', $constraint];
            if (!is_string(reset($constraint))) {
                $query = $query->whereIn($column, $constraint);
            } else {
                $query = $query->where($column, $constraint[0], $constraint[1]);
            }
        }
        if ($request->has('search') || $request->has('sortBy')) {
            $columns = Schema::getColumnListing($model::getTableName());
        }
        if ($request->has('search')) {
            if ($request->search != 'null' && $request->search != '') {
                $search = explode(' ', $request->search);
                $query = $query->where(function ($query) use ($search, $model, $columns) {
                    foreach ($search as $word) {
                        foreach (['d/m/y', 'd-m-y', 'd/m/Y', 'd-m-Y'] as $date_format) {
                            try {
                                $date = Carbon::createFromFormat($date_format, $word)->format('Y-m-d');
                                break;
                            } catch (\Exception $e) {}
                        }
                        if (isset($date)) $word = $date;
                        $query = $query->where(function ($q) use ($word, $model, $columns) {
                            foreach ($columns as $column) {
                                $q->orWhere($column, 'ilike', '%' . $word . '%');
                            }
                        });
                    }
                });
            }
        }
        if ($request->has('sortBy')) {
            if (count($request->sortBy) > 0 && count($request->sortDesc) > 0) {
                foreach ($request->sortBy as $i => $sort) {
                    if (in_array($sort, $columns))
                    $query = $query->orderBy($sort, filter_var($request->sortDesc[$i], FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc');
                }
            }
        }
        if ($request->has('trashed')) {
            if ($request->boolean('trashed') && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) $query = $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 10);
    }
    //
    public static function search_sort_contribution($model, $request, $filter = [],$filterAffiliate=[])
    {
        $query = $model::query();
        
        foreach ($filterAffiliate as $column => $constraint) {
            if (!is_array($constraint)) $constraint = ['=', $constraint];

            if (!is_string(reset($constraint))) {
                $query = $query->whereIn($column, $constraint);
            } else {
                $query = $query->where($column, $constraint[0], $constraint[1]);
            }
        }
        foreach ($filter as $column => $constraint) {
            if (!is_array($constraint)) $constraint = ['>=', $constraint];

            if (!is_string(reset($constraint))) {
                $query = $query->whereIn($column, $constraint);
            } else {
                $query = $query->where($column, $constraint[0], $constraint[1]);
            }
        }
       if ($request->has('search') || $request->has('sortBy')) {
            $columns = Schema::getColumnListing($model::getTableName());
        }
        $query = $query->orderBy('month_year','asc');
        if ($request->has('trashed')) {
            if ($request->boolean('trashed') && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) $query = $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 10);
    }

    public static function pivot_action($relationName, $pivotIds, $message)
    {
        $dirty = false;
        $action = $message . ' ';
        $action .= self::translate($relationName) . ': ';
        if (substr($relationName, 0, 4) != 'App\\') {
            $relationName = 'App\\'.Str::studly(strtolower(Str::singular($relationName)));
        }
        if (is_subclass_of($relationName, 'Illuminate\Database\Eloquent\Model')) {
            $dirty = true;
            $action .= '(';
            foreach ($pivotIds as $id) {
                $action .= app($relationName)::find($id)->display_name;
                if (next($pivotIds)) {
                    $action .= ', ';
                } else {
                    $action .= ')';
                }
            }
        }
        return $dirty ? $action : '';
    }

    public static function relation_action($model, $relationName, $pivotIds, $pivotIdsAttributes, $message)
    {
        $dirty = false;
        $action = $message . ' ';
        $action .= self::translate($relationName);
        $pivots = $model[$relationName]->whereIn('id', $pivotIds);
        foreach ($pivots as $pivot) {
            foreach (self::$display_names as $title) {
                if (isset($pivot[$title])) {
                    $action .= ' [' . $pivot[$title] . '] ';
                    break;
                }
            }
            foreach ($pivotIdsAttributes[$pivot->id] as $key => $attribute) {
                if ($pivot['pivot'][$key] != $attribute && !$dirty) $dirty = true;
                $action .= '(' . self::translate($key) . ') ';
                $action .= self::bool_to_string($pivot['pivot'][$key]) . '->' . self::bool_to_string($attribute);
                if (next($pivotIdsAttributes[$pivot->id])) {
                    $action .= ', ';
                }
            }
            if (next($pivots)) {
                $action .= '; ';
            }
        }
        return $dirty ? $action : '';
    }

    public static function concat_action($object, $message = 'editó')
    {
        $old = app(get_class($object));
        $old->fill($object->getOriginal());
        $action = $message;
        $updated_values = $object->getDirty();
        try {
            $relationships = $object->relationships();
        } catch (\Exception $e) {
            $relationships = [];
        }
        foreach ($updated_values as $key => $value) {
            $concat = false;
            $action .= ' [' . self::translate($key) . '] ';
            if (substr($key, -3, 3) == '_id') {
                $attribute = substr($key, 0, -3);
                if (array_key_exists($attribute, $relationships)) {
                    if ($relationships[$attribute]['type'] == 'BelongsTo') {
                        $old_relation = app($relationships[$attribute]['model'])::find($old[$key]);
                        $new_relation = app($relationships[$attribute]['model'])::find($value);
                        if ($old_relation) {
                            foreach (self::$display_names as $title) {
                                if (isset($old_relation[$title])) {
                                    $action .= $old_relation[$title];
                                    break;
                                }
                            }
                        }
                        $action .= ' -> ';
                        if ($new_relation) {
                            foreach (self::$display_names as $title) {
                                if (isset($new_relation[$title])) {
                                    $action .= $new_relation[$title];
                                    break;
                                }
                            }
                        }
                        $concat = true;
                    }
                }
            }
            if (!$concat) {
                $action .= self::bool_to_string($old[$key]) . ' -> ' . self::bool_to_string($object[$key]);
            }
            if (next($updated_values)) {
                $action .= ', ';
            }
        }
        return $action;
    }

    public static function save_record($object, $type, $action, $recordable = null)
    {
        $role_id = $object->role_id;
        if ($action) {
            $record_type = RecordType::whereName($type)->first();
            if ($record_type) {
                $role = Auth::user()->roles()->whereHas('module', function($query) {
                    return $query->whereName('prestamos');
                })->orderBy('name')->first();
                $record = $object->records()->make([
                    'action' => $action
                ]);
                $record->record_type()->associate($record_type);
                //if($record->role_id)
                    $record->role_id = $role_id ? $role_id : $role->id;
                if ($recordable) $record->recordable()->associate($recordable);
                $record->save();
            }
        }
    }

    public static function male_female($gender, $capìtalize = false)
    {
        if ($gender) {
            $ending = strtoupper($gender) == 'M' ? 'o' : 'a';
        } else {
            $ending = strtoupper($gender) == 'M' ? 'el' : 'la';
        }
        if ($capìtalize) $ending = strtoupper($ending);
        return $ending;
    }

    public static function get_civil_status($status, $gender = null)
    {
        $status = self::trim_spaces($status);
        switch ($status) {
            case 'S':
            case 's':
                $status = 'solter';
                break;
            case 'D':
            case 'd':
                $status = 'divorciad';
                break;
            case 'C':
            case 'c':
                $status = 'casad';
                break;
            case 'V':
            case 'v':
                $status = 'viud';
                break;
            default:
                return '';
                break;
        }
        if (is_null($gender) || is_bool($gender) || $gender == '') {
            $status .= 'o(a)';
        } else {
            switch ($gender) {
                case 'M':
                case 'm':
                case 'F':
                case 'f':
                    $status .= self::male_female($gender);
                    break;
                default:
                    return '';
                    break;
            }
        }
        return $status;
    }

    public static function pdf_to_base64($views, $file_name, $informationqr, $size = 'letter', $copies = 1, $portrait = true)
    {
        $footerHtml = view()->make('partials.footer')->with(array('paginator' => true, 'print_date' => true, 'date' => Carbon::now()->ISOFormat('L HH:mm a'),'informationqr'=>$informationqr))->render();
        $options = [
            'copies' => $copies ?? 1,
            'footer-html' => $footerHtml,
            'user-style-sheet' => public_path('css/report-print.min.css'),
            'orientation' => $portrait ? 'portrait' : 'landscape',
            'margin-top' => '5',
            'margin-bottom' => '16',
            'margin-left' => '15',
            'margin-right' => '15',
            'encoding' => 'UTF-8',
            'page-width' => '216'
        ];
        $options['page-height'] = $size == 'letter' ? '279' : '330';
        $content = base64_encode(\PDF::getOutputFromHtml($views, $options));
        return [
            'content' => $content,
            'type' => 'pdf',
            'file_name' => $file_name
        ];
    }

    
    public static function pdf_to_base64contract($views, $file_name,$informationqr,$object, $size = 'letter', $copies = 1, $portrait = true)
    {
        if(empty($informationqr)|| empty($object))
        $footerHtml = view()->make('partials.footer')->with(array('paginator' => true, 'print_date' => true, 'date' => Carbon::now()->ISOFormat('L HH:mm a')))->render();
        else
        $footerHtml = view()->make('partials.footer')->with(array('paginator' => true, 'print_date' => true, 'date' => Carbon::now()->ISOFormat('L HH:mm a'),'informationqr'=>$informationqr,'object'=>$object))->render();
        $options = [
            'copies' => $copies ?? 1,
            'footer-html' => $footerHtml,
            'user-style-sheet' => public_path('css/report-print.min.css'),
            'orientation' => $portrait ? 'portrait' : 'landscape',
            'margin-top' => '15',
            'margin-bottom' => '16',
            'margin-left' => '15',
            'margin-right' => '15',
            'encoding' => 'UTF-8',
            'page-width' => '216'
        ];
        $options['page-height'] = $size == 'letter' ? '279' : '330';
        $content = base64_encode(\PDF::getOutputFromHtml($views, $options));
        return [
            'content' => $content,
            'type' => 'pdf',
            'file_name' => $file_name
        ];
    }
    public static function pdf_to_treasury_receipt($views, $file_name,$informationqr, $size = 'letter', $copies = 1, $portrait = true)
    {

        $options = [
            'copies' => $copies ?? 1,
            'user-style-sheet' => public_path('css/report-print.min.css'),
            'orientation' => $portrait ? 'portrait' : 'landscape',
            'margin-top' => '0',
            'margin-right' => '20',
            'margin-left' => '13', 
            'margin-bottom' => '0',
            'encoding' => 'UTF-8',
            'page-width' => '216'  
        ];
        $options['page-height'] = $size == 'letter' ? '279' : '330';
        $content = base64_encode(\PDF::getOutputFromHtml($views, $options));
        return [
            'content' => $content,
            'type' => 'pdf',
            'file_name' => $file_name
        ];
    }

    public static function request_rrhh_employee($position)
    {
        $employee = [
            'name' => '_______________',
            'identity_card' => '_______________',
            'position' => $position
        ];
        try {
            $req = collect(json_decode(file_get_contents(env("RRHH_URL") . '/position?name=' . $position), true))->sortByDesc('id');
            if ($req->count() >= 1) {
                $pos = $req->first();
            } else {
                throw new Exception();
            }
            $req = collect(json_decode(file_get_contents(implode('/', [env("RRHH_URL"), 'position', $pos['id'], 'employee'])), true));
            $employee['name'] = self::trim_spaces(implode(' ', [$req['first_name'], $req['second_name'], $req['last_name'], $req['mothers_last_name']]));
            $employee['identity_card'] = $req['identity_card'];
            $req = collect(json_decode(file_get_contents(implode('/', [env("RRHH_URL"), 'city', $req['city_identity_card_id']])), true));
            $employee['identity_card'] .= ' ' . $req['shortened'];
        } catch (\Exception $e) {
            \Log::channel('error')->error('RRHH server not found');
        } finally {
            return $employee;
        }
    }

    public static function derivation($request, $to_role, $derived, $model){
        $to_role = Role::find($to_role);
        if (count(array_unique($model->pluck('role_id')->toArray()))) $from_role = $derived->first()->role_id;
        if ($from_role) {
            $from_role = Role::find($from_role);
            $flow_message = Util::flow_message($derived->first()->modality->procedure_type->id, $from_role, $to_role);
        }
        $derived->map(function ($item, $key) use ($from_role, $to_role, $flow_message) {
            if (!$from_role) {
                $item['from_role_id'] = $item['role_id'];
                $from_role = Role::find($item['role_id']);
                $flow_message = Util::flow_message($item->modality->procedure_type->id, $from_role, $to_role);
            }
            $item['role_id'] = $to_role->id;
            $item['validated'] = false;
            Util::save_record($item, $flow_message['type'], $flow_message['message']);
        });
        //$loanPayment->update(['role_id' => $to_role->id]);
        $model->update(array_merge($request->only('role_id'), ['validated' => false]));
        event(new LoanFlowEvent($derived));
        return $derived;
    }

    public static function flow_message($procedure_type_id, $from_role, $to_role)
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

    public static function process_by_procedure_type($model, $object, $module){ //aadecuar para amortizaciones
        foreach ($object as $key => $procedure_type) {
            $data[] = [
                'procedure_type_id' => $procedure_type->id,
                'total' => [
                    'received' => 0,
                    'validated' => 0,
                    'trashed' => 0,
                    'my_received' => 0
                ]
            ];
            $user_roles = Auth::user()->roles()->where('module_id','=',$module->id)->get();
           // foreach ($module->roles()->whereNotNull('sequence_number')->orderBy('sequence_number')->orderBy('display_name')->get() as $subkey => $role) {
            foreach ( $user_roles as $subkey => $role) {
                $data[$key]['data'][$subkey] = [
                    'role_id' => $role->id
                ];
                $values = [
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(false)->count(), //received
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(true)->whereUserId(Auth::user()->id)->count(), //validated
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->onlyTrashed()->count(), //trashed
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(false)->count(), //my_received
                ];
                $i = 0;
                foreach ($data[$key]['total'] as $total_key => $v) {
                    $data[$key]['total'][$total_key] += $values[$i];
                    $data[$key]['data'][$subkey]['data'][$total_key] = $values[$i];
                    $i++;
                }
            }
        }
        return $data;
    }

    public static function process_by_role($model, $module){
        $user_roles = Auth::user()->roles()->where('module_id','=',$module->id)->get();
       // foreach ($module->roles()->whereNotNull('sequence_number')->orderBy('sequence_number')->orderBy('display_name')->get() as $role) {
        foreach ($user_roles as $role) {
            $data[] = [
                'role_id' => $role->id,
                'data' => [
                    'received' => $model::whereRoleId($role->id)->whereValidated(false)->whereUserId(null)->count(),
                    'validated' => $model::whereRoleId($role->id)->whereValidated(true)->whereUserId(Auth::user()->id)->count(),
                    'trashed' => $model::whereRoleId($role->id)->onlyTrashed()->count(),
                    'my_received' => $model::whereRoleId($role->id)->whereValidated(false)->whereUserId(Auth::user()->id)->count()
                ]
            ];
        }
        return $data;
    }

    public static function loans_by_user($model, $object, $module){
        foreach ($object as $key => $procedure_type) {
            $data[] = [
                'procedure_type_id' => $procedure_type->id,
                'total' => [
                    'received' => 0,
                    'validated' => 0,
                    'trashed' => 0,
                    'my_received' => 0
                ]
            ];
            $user_roles = Auth::user()->roles()->where('module_id','=',$module->id)->get();
            //foreach ($module->roles()->whereNotNull('sequence_number')->orderBy('sequence_number')->orderBy('display_name')->get() as $subkey => $role) {
            foreach ($user_roles as $subkey => $role) {
                $data[$key]['data'][$subkey] = [
                    'role_id' => $role->id
                ];
                $values = [
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(false)->whereUserId(null)->count(), //received
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(true)->whereUserId(Auth::user()->id)->count(), //validated
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->onlyTrashed()->whereUserId(Auth::user()->id)->count(), //trashed
                    $model::whereRoleId($role->id)->whereHas('modality', function($q) use ($procedure_type) {
                        $q->whereProcedureTypeId($procedure_type->id);
                    })->whereValidated(false)->whereUserId(Auth::user()->id)->count(), //my_received
                ];
                $i = 0;
                foreach ($data[$key]['total'] as $total_key => $v) {
                    $data[$key]['total'][$total_key] += $values[$i];
                    $data[$key]['data'][$subkey]['data'][$total_key] = $values[$i];
                    $i++;
                }
            }
        }
        return $data;
    }

    public static function amortizations_by_user($model, $object, $module){
        $user_roles = Auth::user()->roles()->where('module_id','=',$module->id)->get();
       // foreach ($module->roles()->whereNotNull('sequence_number')->orderBy('sequence_number')->orderBy('display_name')->get() as $role) {
        foreach ($user_roles as $role) {
            $data[] = [
                'role_id' => $role->id,
                'data' => [
                    'received' => $model::whereRoleId($role->id)->whereValidated(false)->count(),
                    'validated' => $model::whereRoleId($role->id)->whereValidated(true)->whereUserId(Auth::user()->id)->count(),
                    'trashed' => $model::whereRoleId($role->id)->onlyTrashed()->count(),
                    'my_received' => $model::whereRoleId($role->id)->whereValidated(false)->whereUserId(Auth::user()->id)->count()
                ]
            ];
        }
        return $data;
    }

    public static function correlative($type)
    {
        if(LoanCorrelative::where('year', Carbon::now()->year)->where('type', $type)->count() == 0)
        {
            $correlative = new LoanCorrelative();
            $correlative->year = Carbon::now()->year;
            $correlative->correlative = 1;
            $correlative->type = $type;
            $correlative->save();
            $correlative_number = 1;
        }
        else
        {
            $correlative = LoanCorrelative::where('year', Carbon::now()->year)->where('type', $type)->first();
            $correlative->correlative++;
            $correlative->save();
            $correlative_number = $correlative->correlative;
        }
        return $correlative_number;
    }

    public static function delegate_shipping($sms_num, $message, $id) {
        // public function delegate_shipping(Request $request) {
            
            $threshold = 10;
            $flag = false;
            if(Util::check_balance() <= $threshold) {
                logger("entra aca");
                return $flag;
            }
            $sms_server_url = env('SMS_SERVER_URL', 'http://172.16.1.34/goip/en/');
            $root = env('SMS_SERVER_ROOT', 'root');
            $password = env('SMS_SERVER_PASSWORD', 'root');
            $sms_provider = env('SMS_PROVIDER', 1);
    
            $code_num = '591' . $sms_num; 
            $user_id = 1;
            $transmitter_id = 1;            
            $issuer_number = NotificationNumber::find($transmitter_id)->number;
            $response = Http::get($sms_server_url . "dosend.php?USERNAME=$root&PASSWORD=$password&smsprovider=$sms_provider&smsnum=$code_num&method=2&Memo=$message");
    
            if($response->successful()) {
                $clipped_chain = substr($response, strrpos($response, "id=") + 3);
                $end_of_chain = substr($clipped_chain,  strrpos($clipped_chain, "&U"));
                $id = substr($clipped_chain, 0, -strlen($end_of_chain));
                $result = Http::timeout(60)->get($sms_server_url . "resend.php?messageid=$id&USERNAME=$root&PASSWORD=$password");
                if($result->successful()) {
                    $var = $result->getBody();
                    if(strpos($var, "ERROR") === false) {
                        $flag = true;
                        $loan = new Loan();
                        $alias = $loan->getMorphClass();
                        $notification_send = new NotificationSend();
                        $notification_send->create([
                            'user_id' => $user_id,
                            'carrier_id' => NotificationCarrier::whereName('SMS')->first()->id,
                            'number_id' => NotificationNumber::whereNumber($issuer_number)->first()->id,
                            'sendable_type' => $alias,
                            'sendable_id' => $id,
                            'send_date' => Carbon::now(),
                            'delivered' => true,
                            'message' => json_encode(['data' => $message]),
                            'subject' => null
                        ]);
                    }
                } 
            } 
            return $flag;
    }

    public static function remove_special_char($string) {        
        return preg_replace('/[\(\)\-]+/', '', $string);
    }

    public static function check_balance() {

        $sms_server_url = env('SMS_SERVER_URL', 'http://172.16.1.34/goip/en/');
        $root = env('SMS_SERVER_ROOT', 'root');
        $password = env('SMS_SERVER_PASSWORD', 'root');
        $sms_provider = env('SMS_PROVIDER', 1);
        $flag = false;

        $response = Http::get($sms_server_url . "dosend.php?USERNAME=$root&PASSWORD=$password&smsprovider=$sms_provider&smsnum=330&method=2&Memo=Saldo");

        if($response->successful()) {
            $clipped_chain = substr($response, strrpos($response, "id=") + 3);
            $end_of_chain = substr($clipped_chain,  strrpos($clipped_chain, "&U"));
            $id = substr($clipped_chain, 0, -strlen($end_of_chain));
            $result = Http::timeout(60)->get($sms_server_url . "resend.php?messageid=$id&USERNAME=$root&PASSWORD=$password");
            if($result->successful()) {
                $var = $result->getBody();
                if(strpos($var, "ERROR") === false) {
                    $flag = true;
                }
            }
        }
        
        if($flag) {
            sleep(7);
            logger("aca");
            $message = DB::connection('mysql')->table('receive')->select('msg')->where('srcnum', 330)->orderBY('id', 'desc')->first();
            $clipped_chain = substr($message->msg, strrpos($message->msg, "Bs.") + 4);
            $end_of_chain = substr($clipped_chain, strrpos($clipped_chain, "Paq"));
            $balance = substr($clipped_chain, 0, -strlen($end_of_chain));
            $balance = floatval($balance);
            return $balance;
        }
        return 0;
    }
    
}
