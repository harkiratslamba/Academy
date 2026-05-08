<?php

namespace Modules\Timetable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Teacher\Models\Teacher;

class TeacherAvailability extends Model
{
    protected $fillable = ['teacher_id', 'date', 'period_number', 'status', 'reason'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
