<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year_id'
    ];
    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'term_id'); 
    }
      protected static function booted(): void
    {
        static::addGlobalScope('currentAcademicYear', function ($query) {
            $currentYearId = AcademicYear::where('is_current', true)->value('id');

            if ($currentYearId) {
                $query->where('academic_year_id', $currentYearId);
            }
        });
         static::creating(function ($model) {
            if (empty($model->academic_year_id)) {
                $model->academic_year_id = AcademicYear::where('is_current', true)->value('id');
            }
        });
    }
}
