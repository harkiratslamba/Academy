<?php

namespace Modules\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Teacher extends Model
{
    protected $fillable = [
        'user_id','name','email','phone','employee_id',
        'subject_specialization','qualification','joining_date','status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(\Modules\Timetable\Models\Timetable::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(\Modules\Attendance\Models\Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(\Modules\Leave\Models\Leave::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(\Modules\Timetable\Models\TeacherAvailability::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isAbsentOn(string $date): bool
    {
        return $this->attendances()
            ->where('date', $date)
            ->whereIn('status', ['absent', 'on_leave'])
            ->exists();
    }
}
