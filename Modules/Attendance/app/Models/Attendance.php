<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Teacher\Models\Teacher;
use App\Models\User;

class Attendance extends Model
{
    protected $fillable = ['teacher_id', 'date', 'status', 'check_in', 'notes', 'marked_by'];

    public function teacher(): BelongsTo    { return $this->belongsTo(Teacher::class); }
    public function markedBy(): BelongsTo   { return $this->belongsTo(User::class, 'marked_by'); }

    public function scopeAbsent($query)
    {
        return $query->whereIn('status', ['absent', 'on_leave']);
    }
}
