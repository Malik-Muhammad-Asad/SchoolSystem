<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTestMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'test_name',
        'class_id',
        'subject_id',
        'term_id',
        'obtain_number',
        'subject_number',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class ,'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
