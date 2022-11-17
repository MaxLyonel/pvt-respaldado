<?php

namespace App\Notification;

use Illuminate\Database\Eloquent\Model;
use App\Module;

class NotificationCarrier extends Model
{
    protected $guarded = [];

    public function module() {
        return $this->belongsTo(Module::class);
    }

    public function send() {
        return $this->hasMany(NotificationSend::class, 'carrier_id');
    }
}
