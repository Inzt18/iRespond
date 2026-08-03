<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponderHotline extends Model
{
    protected $fillable = [
        'type',
        'label',
        'phone_number',
    ];
}
