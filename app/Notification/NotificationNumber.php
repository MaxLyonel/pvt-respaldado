<?php

namespace App\Notification;

use Illuminate\Database\Eloquent\Model;

class NotificationNumber extends Model
{
    protected $guarded = [];

    public function send() {
        return $this->hasMany(NotificationSend::class, 'number_id');
    }
}
