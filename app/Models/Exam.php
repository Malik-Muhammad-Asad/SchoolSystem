<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'term_id', 
        'academic_year_id'
    ];

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

    public function Term()  // Changed 'Term' to 'term' to follow Laravel naming conventions
    {
        return $this->belongsTo(Term::class, 'term_id'); // Correct relationship method
    }
    public function examResults()
    {
        return $this->hasMany(ExamResult::class,'exam_id'); // Adjust model name if different
    }
}
