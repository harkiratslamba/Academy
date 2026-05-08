<?php

namespace Modules\Timetable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Classes\Models\{SchoolClass, Section, Room};
use Modules\Subject\Models\Subject;
use Modules\Teacher\Models\Teacher;

class Timetable extends Model
{
    protected $fillable = [
        'class_id','section_id','subject_id','teacher_id','room_id',
        'day','period_number','start_time','end_time','is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function schoolClass(): BelongsTo  { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function section(): BelongsTo      { return $this->belongsTo(Section::class); }
    public function subject(): BelongsTo      { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo      { return $this->belongsTo(Teacher::class); }
    public function room(): BelongsTo         { return $this->belongsTo(Room::class); }

    public function scopeActive($query)       { return $query->where('is_active', true); }
    public function scopeForDay($query, $day) { return $query->where('day', $day); }
}
