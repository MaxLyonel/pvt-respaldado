<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AffiliateToken extends Model
{
    public $timestamps = true;
    public $guarded = ['id'];
    protected $fillable = [
        'affiliate_id',
        'api_token',
        'device_id',
        'firebase_token'
    ];
    public function affiliate(){
        return $this->belongsTo(Affiliate::class);
    }
    public function affiliate_user(){
        return $this->hasOne(AffiliateUser::class,'affiliate_token_id','id');
    }
}

