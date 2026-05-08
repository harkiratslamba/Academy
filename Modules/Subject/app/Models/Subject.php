<?php

namespace Modules\Subject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Classes\Models\SchoolClass;

class Subject extends Model
{
    protected $fillable = ['subject_name', 'subject_code', 'class_id'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
