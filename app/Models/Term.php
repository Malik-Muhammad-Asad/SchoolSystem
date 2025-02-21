<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'term_id'); 
    }
}
