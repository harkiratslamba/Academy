<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotification extends Model
{
    protected $table = 'school_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
