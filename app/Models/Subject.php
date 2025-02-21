<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name'];


    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_subject');
    }
    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'subject_id'); 
    }
}
