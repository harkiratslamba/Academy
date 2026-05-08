<?php

namespace Modules\Classes\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public $timestamps  = false;
    protected $fillable = ['room_number', 'capacity', 'floor'];
}
