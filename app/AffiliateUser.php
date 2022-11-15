<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AffiliateUser extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'affiliate_token_id',
        'username',
        'password',
        'access_status'
    ];
    protected $primaryKey = 'affiliate_token_id';
    public $incrementing = false;

    public function affiliate_token()
    {
        return $this->belongsTo(AffiliateToken::class);
    }
}