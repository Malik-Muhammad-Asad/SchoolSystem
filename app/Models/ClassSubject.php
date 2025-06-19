<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubject extends Model
{
    use HasFactory;

    protected $fillable = ['class_id', 'subject_id'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('class_current_academic_year', function (Builder $builder) {
            $currentYearId = AcademicYear::where('is_current', true)->value('id');
            if ($currentYearId) {
                $builder->whereHas('class', function ($query) use ($currentYearId) {
                    $query->where('academic_year_id', $currentYearId);
                });
            }
        });
    }

}
