<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'from_class_id',
        'to_class_id',
        'academic_year_id',
        'transfer_date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass()
    {
        return $this->belongsTo(Classes::class, 'from_class_id');
    }

    public function toClass()
    {
        return $this->belongsTo(Classes::class, 'to_class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
      protected static function boot()
    {
        parent::boot();

        static::saved(function ($transfer) {
            if ($transfer->student && $transfer->to_class_id) {
                $student = $transfer->student;
                $student->class_id = $transfer->to_class_id;
                $student->save();
            }
        });
    }
}
