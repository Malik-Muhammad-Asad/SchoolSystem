<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Classes extends Model
{
    use HasFactory;
    protected $table = 'class';
    protected $fillable = ['name'];


    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id');
    }
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

}


