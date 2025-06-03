<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AcademicYear;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'class';

    protected $fillable = ['name', 'academic_year_id'];

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

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
   public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
