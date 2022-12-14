<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Util;

class Address extends Model
{
    use Traits\EloquentGetTableNameTrait;

    protected $table = "addresses";
    public $guarded =  [];
    protected $fillable = ['city_address_id', 'zone', 'street', 'number_address','description'];

    protected $attributes = array(
        'city_address_id' => null,
        'zone' => null,
        'street' => null,
        'number_address' => null,
        'description' => null
    );

    public function getFullAddressAttribute($value)
    {
        if (!$this->number_address || Util::trim_spaces($this->number_address) == '') {
            $number = 'S/N';
        } else {
            $number = 'Nº ' . $this->number_address;
        }
        return Util::trim_spaces(implode(' ', [$this->description]));
    }

    public function affiliate()
    {
    	return $this->belongsToMany(Affiliate::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class,'city_address_id','id');
    }
    public function cityName()
    {
        return $this->city->name;
    }
}
