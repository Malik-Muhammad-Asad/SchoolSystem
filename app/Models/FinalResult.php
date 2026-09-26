<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinalResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',     
        'exam_id',
        'term_id',
        'report_type',
        'total_marks',
        'obtained_marks',
        'percentage',
        'grade',
        'rank',
        'is_locked',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
