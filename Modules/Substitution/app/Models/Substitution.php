<?php

namespace Modules\Substitution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Teacher\Models\Teacher;
use Modules\Timetable\Models\Timetable;
use App\Models\User;

class Substitution extends Model
{
    protected $fillable = [
        'original_teacher_id','substitute_teacher_id','timetable_id',
        'date','status','assigned_by','notes',
    ];

    public function originalTeacher(): BelongsTo   { return $this->belongsTo(Teacher::class, 'original_teacher_id'); }
    public function substituteTeacher(): BelongsTo  { return $this->belongsTo(Teacher::class, 'substitute_teacher_id'); }
    public function timetable(): BelongsTo          { return $this->belongsTo(Timetable::class); }
    public function assignedBy(): BelongsTo         { return $this->belongsTo(User::class, 'assigned_by'); }
}
