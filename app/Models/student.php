<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'father_name',
        'gr_no',
        'class_id',
        'note',
        'status',
        'CreatedBy',
    ];
    public function classes()  // Fixed typo here
    {
        return $this->belongsTo(Classes::class, 'class_id');  // Corrected relationship
    }
    protected static function booted(): void
    {
        static::addGlobalScope('currentAcademicYear', function (Builder $query) {
            $currentYearId = AcademicYear::where('is_current', true)->value('id');

            if ($currentYearId) {
                $query->whereHas('classes', function ($q) use ($currentYearId) {
                    $q->where('academic_year_id', $currentYearId);
                });
            }
        });
    }
}
