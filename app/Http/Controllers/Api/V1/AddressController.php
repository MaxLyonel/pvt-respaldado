<?php

namespace App\Http\Controllers\Api\V1;
use App\Address;
use App\Http\Requests\AddressForm;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** @group Direcciones
* Datos de las direcciones de los afiliados y de aquellas relacionadas con los trámites
*/
class AddressController extends Controller
{
    /**
    * Nueva dirección
    * Inserta nueva dirección
    * @bodyParam city_address_id integer ID de ciudad del CI. Example: 4
    * @bodyParam zone string Zona. Example: Chuquiaguillo
    * @bodyParam street string Calle. Example: Av. Panamericana
    * @bodyParam number_address integer Número de casa. Example: 45
    * @bodyParam description string Los prestamos se añade en este campo toda la direccion. Example: Avenida heroes del Mar nro 100
    * @authenticated
    * @responseFile responses/address/store.200.json
    */
    public function store(AddressForm $request)
    {
        return Address::create($request->all());
    }

    /**
    * Actualizar dirección
    * Actualizar los datos de una dirección existente
    * @urlParam address required ID de dirección. Example: 11805
    * @bodyParam city_address_id integer ID de ciudad del CI. Example: 4
    * @bodyParam zone string Zona. Example: Chuquiaguillo
    * @bodyParam street string Calle. Example: Av. Panamericana
    * @bodyParam number_address integer Número de casa. Example: 45
    * @bodyParam description string Los prestamos se añade en este campo toda la direccion. Example: Avenida heroes del Mar nro 100
    * @authenticated
    * @responseFile responses/address/update.200.json
    */
    public function update(AddressForm $request, Address $address)
    {
        $address->fill($request->all());
        $address->save();
        return $address;
    }

    /**
    * Eliminar dirección
    * Eliminar una dirección solo en caso de que no este relacionada ningún trámite
    * @urlParam address required ID de dirección. Example: 1077
    * @authenticated
    * @responseFile responses/address/destroy.200.json
    */
    public function destroy(Address $address)
    {
        $address->delete();
        return $address;
    }
}