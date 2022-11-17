<?php

namespace App\Notification;

use Illuminate\Database\Eloquent\Model;
use App\User;

class NotificationSend extends Model
{
    protected $guarded = [];

    public function number(){
        return $this->belongsTo(NotificationNumber::class);
    }

    public function carrier(){
        return $this->belongsTo(NotificationCarrier::class);
    }
    
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function sendable(){
        return $this->morphTo();
    }
}
