<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Teacher\Models\Teacher;
use App\Models\User;

class Leave extends Model
{
    protected $fillable = [
        'teacher_id','leave_type','from_date','to_date',
        'reason','status','approved_by','approved_on','admin_remarks',
    ];

    protected $casts = ['approved_on' => 'datetime'];

    public function teacher(): BelongsTo    { return $this->belongsTo(Teacher::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function getDaysAttribute(): int
    {
        return now()->parse($this->from_date)->diffInDays($this->to_date) + 1;
    }
}
