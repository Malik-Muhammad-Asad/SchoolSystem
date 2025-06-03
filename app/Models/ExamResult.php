<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'term_id',
        'exam_id',
        'subject_id',
        'student_id',
        'subject_number',
        'obtain_number',
    ];


    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    protected static function booted(): void
    {
        static::addGlobalScope('currentAcademicYear', function ($query) {
            $currentYearId = AcademicYear::where('is_current', true)->value('id');

            if ($currentYearId) {
                $query->whereHas('class', function ($q) use ($currentYearId) {
                    $q->where('academic_year_id', $currentYearId);
                });
            }
        });
    }
}
