<?php

namespace App\Observers;

use App\Affiliate;
use App\AffiliateState;
use App\AffiliateToken;
use App\AffiliateUser;
use App\Helpers\Util;

class AffiliateObserver
{
    /**
    * Handle the contract "created" event.
    *
    * @param  \App\Affiliate  $contract
    * @return void
    */
    public function created(Affiliate $object)
    {
        Util::save_record($object, 'datos-personales', 'registró');
    }
    /**
    * Handle the affiliate "updating" event.
    *
    * @param  \App\Affiliate  $Affiliate
    * @return void
    */
    public function updating(Affiliate $affiliate)
    {
        if($affiliate->affiliate_state_id != $affiliate->getOriginal('affiliate_state_id'))
        {
            if ($affiliate->affiliate_state_id==4) {
                $affiliateToken=AffiliateToken::where('affiliate_id',$affiliate->id)->first();
                if ($affiliateToken) {
                    $affiliateUser=AffiliateUser::where('affiliate_token_id',$affiliateToken->id)->first();
                    if ($affiliateUser) {
                        $affiliateUser->access_status='Inactivo';
                        $affiliateUser->save();
                    }
                }
            }
        }
        Util::save_record($affiliate, 'datos-personales', Util::concat_action($affiliate));
    }
    /**
    * Handle the affiliate "deleted" event.
    *
    * @param  \App\Affiliate  $Affiliate
    * @return void
    */
    public function deleted(Affiliate $object)
    {
        Util::save_record($object, 'datos-personales', 'eliminó afiliado: ' . $object->full_name);
    }
}