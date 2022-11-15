<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Util;

class LoanBorrower extends Model
{
    use Traits\EloquentGetTableNameTrait;
    use Traits\RelationshipsTrait;
    use softDeletes;

    protected $fillable = [
        'loan_id',
        'degree_id',
        'unit_id',
        'category_id',
        'type_affiliate',
        'unit_police_description',
        'affiliate_state_id',
        'identity_card',
        'city_identity_card_id',
        'city_birth_id',
        'registration',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'gender',
        'civil_status',
        'phone_number',
        'cell_phone_number',
        'address_id',
        'pension_entity_id',
        'payment_percentage',
        'payable_liquid_calculated',
        'bonus_calculated',
        'quota_previous',
        'quota_treat',
        'indebtedness_calculated',
        'indebtedness_calculated_previous',
        'liquid_qualification_calculated',
        'contributionable_ids',
        'contributionable_type',
        'type'
      ];

    public function getFullNameAttribute()
    {
      return rtrim($this->first_name.' '.$this->second_name.' '.$this->last_name.' '.$this->mothers_last_name.' '.$this->surname_husband);
    }

    public function getIdentityCardExtAttribute()
    {
        $data = $this->identity_card;
        if ($this->city_identity_card && $this->city_identity_card != 'NINGUNO'){
          $data .= ' ' . $this->city_identity_card->first_shortened;
        } 
        return rtrim($data);
    }

    public function getTitleAttribute()
    {
      $data = "";
      if ($this->degree) $data = $this->degree->shortened;;
      return $data;
    }

    public function loan()
    {
      return $this->belongsTo(Loan::class);
    }

    public function title()
    {
      return $this->loan->affiliate->title;
    }

    public function affiliate_state()
    {
      return $this->belongsTo(AffiliateState::class);
    }

    public function affiliate()
    {
      return Loan::find($this->loan_id)->affiliate;
    }

    public function Address()
    {
        return $this->hasOne(Address::class, 'id', 'address_id');
    }

    public function getFullUnitAttribute()
    {
        $data = "";
        if ($this->unit) $data .= ' ' . $this->unit->district.' - '.$this->unit->name.' ('.$this->unit->shortened.')';
        return $data;
    }

    public function unit()
    {
      return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function getCategoryAttribute()
    {
      $category = null;
      if($this->category_id){
        $category =  Category::whereId($this->category_id)->first();
      }
      return $category;
    }

    public function pension_entity()
    {
      return $this->belongsTo(PensionEntity::class, 'pension_entity_id', 'id');
    }

    public function getInitialsAttribute(){
      return (substr($this->first_name, 0, 1).substr($this->second_name, 0, 1).substr($this->last_name, 0, 1).substr($this->mothers_last_name, 0, 1).substr($this->surname_husband, 0, 1));
    }

    public function city_identity_card()
    {
      return $this->belongsTo(City::class,'city_identity_card_id', 'id');
    }

    public function financial_entity()
    {
      return $this->loan->financial_entity;
    }

    public function getBallotsAttribute()
    {
      $contributions = $this->contributionable_ids;
      $contributions_type = $this->contributionable_type;
      $ballots_ids = json_decode($contributions);
      $ballots = collect();
      $adjusts = collect();
      $ballot_adjust = collect();
      $average_ballot_adjust = collect();
      $mount_adjust = 0;
      $sum_payable_liquid = 0;
      $sum_mount_adjust = 0;
      $sum_border_bonus = 0;
      $sum_position_bonus = 0;
      $sum_east_bonus = 0;
      $sum_public_security_bonus = 0;
      $sum_dignity_rent = 0;
      $count_records = 0;
      $contribution_type = null;
      if($contributions_type == "contributions")
      { 
        $contribution_type = "contributions";
        foreach($ballots_ids as $is_ballot_id)
        {
          if(Contribution::find($is_ballot_id))
            $ballots->push(Contribution::find($is_ballot_id));
          if(LoanContributionAdjust::where('adjustable_id', $is_ballot_id)->where('loan_id',$this->loan_id)->first())
            $adjusts->push(LoanContributionAdjust::where('adjustable_id', $is_ballot_id)->where('loan_id',$this->loan_id)->first());
        }
        $count_records = count($ballots);
        foreach($ballots as $ballot)
        {
          $mount_adjust = 0;
          foreach($adjusts as $adjust)
          {
            if($ballot->id == $adjust->adjustable_id)
              $mount_adjust = $adjust->amount;                  
          }
          $ballot_adjust->push([
                            'month_year' => $ballot->month_year,
                            'payable_liquid' => (float)$ballot->payable_liquid,
                            'mount_adjust' => (float)$mount_adjust,
                            'border_bonus' => (float)$ballot->border_bonus,
                            'position_bonus' => (float)$ballot->position_bonus,
                            'east_bonus' => (float)$ballot->east_bonus,
                            'public_security_bonus' => (float)$ballot->public_security_bonus,                                
                        ]);
          $sum_payable_liquid = $sum_payable_liquid + $ballot->payable_liquid;
          $sum_mount_adjust = $sum_mount_adjust + $mount_adjust;
          $sum_border_bonus = $sum_border_bonus + $ballot->border_bonus;  
          $sum_position_bonus = $sum_position_bonus + $ballot->position_bonus;
          $sum_east_bonus = $sum_east_bonus + $ballot->east_bonus;
          $sum_public_security_bonus = $sum_public_security_bonus + $ballot->public_security_bonus;     
        }                              
        $average_ballot_adjust->push([
                                'average_payable_liquid' => $sum_payable_liquid/$count_records,
                                'average_mount_adjust' => $sum_mount_adjust/$count_records,
                                'average_border_bonus' => $sum_border_bonus/$count_records,
                                'average_position_bonus' => $sum_position_bonus/$count_records,
                                'average_east_bonus' => $sum_east_bonus/$count_records,
                                'average_public_security_bonus' => $sum_public_security_bonus/$count_records,                       
                            ]);
      }
      if($contributions_type == "aid_contributions")
      {
        $contribution_type = "aid_contributions";
        foreach($ballots_ids as $is_ballot_id)
        {
          if(AidContribution::find($is_ballot_id))
            $ballots->push(AidContribution::find($is_ballot_id));
          if(LoanContributionAdjust::where('adjustable_id', $is_ballot_id)->where('loan_id',$this->loan_id)->first())
            $adjusts->push(LoanContributionAdjust::where('adjustable_id', $is_ballot_id)->where('loan_id',$this->loan_id)->first());
        }
        $count_records = count($ballots);                
        foreach($ballots as $ballot)
        {
          $mount_adjust = 0;
          foreach($adjusts as $adjust)
          {
            if($ballot->id == $adjust->adjustable_id)
              $mount_adjust = $adjust->amount;
          }
          $ballot_adjust->push([
                            'month_year' => $ballot->month_year,
                            'payable_liquid' => (float)$ballot->rent,
                            'mount_adjust' => (float)$mount_adjust,
                            'dignity_rent' => (float)$ballot->dignity_rent,                              
                        ]); 
          $sum_payable_liquid = $sum_payable_liquid + $ballot->rent; 
          $sum_mount_adjust = $sum_mount_adjust + $mount_adjust; 
          $sum_dignity_rent = $sum_dignity_rent + $ballot->dignity_rent;
        }
        $average_ballot_adjust->push([
                        'average_payable_liquid' => $sum_payable_liquid/$count_records,
                        'average_mount_adjust' => $sum_mount_adjust/$count_records,
                        'average_dignity_rent' => $sum_dignity_rent/$count_records,
                    ]);
      }
      if($contributions_type == "loan_contribution_adjusts")
      {
        $contribution_type = "loan_contribution_adjusts";
        $liquid_ids= LoanContributionAdjust::where('loan_id',$this->loan_id)->where('type_adjust',"liquid")->get()->pluck('id');
        $adjust_ids= LoanContributionAdjust::where('loan_id',$this->loan_id)->where('type_adjust',"adjust")->get()->pluck('id');
        foreach($liquid_ids as $liquid_id)
        {
          $ballots->push(LoanContributionAdjust::find($liquid_id));
        }
        foreach($adjust_ids as $adjust_id)
        {
          $adjusts->push( LoanContributionAdjust::find($adjust_id));
        } 
        $count_records = count($ballots);      
        foreach($ballots as $ballot)
        {
          foreach($adjusts as $adjust)
          {
            if($ballot->period_date == $adjust->period_date)
              $mount_adjust = $adjust->amount;
          }
          $ballot_adjust->push([
                            'month_year' => $ballot->period_date,
                            'payable_liquid' => (float)$ballot->amount,
                            'mount_adjust' => (float)$mount_adjust,                              
                        ]); 
          $sum_payable_liquid = $sum_payable_liquid + $ballot->amount;
          $sum_mount_adjust = $sum_mount_adjust + $mount_adjust; 
        }            
        $average_ballot_adjust->push([
                        'average_payable_liquid' => $sum_payable_liquid/$count_records,
                        'average_mount_adjust' => $sum_mount_adjust/$count_records,
                    ]);         
      }       
      $data = [
            'contribution_type' =>$contribution_type,
            'average_ballot_adjust'=> $average_ballot_adjust,
            'ballot_adjusts'=> $ballot_adjust->sortBy('month_year')->values()->toArray(),
        ];
    return (object)$data;
  }

  public function getCivilStatusGenderAttribute()
  {
    return Util::get_civil_status($this->civil_status, $this->gender);
  }

  public function city_birth()
  {
    return $this->belongsTo(City::class, 'city_birth_id', 'id');
  }

  public function degree()
  {
    return $this->belongsTo(Degree::class);
  }
}
