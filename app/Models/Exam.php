<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'term_id', // Fixed typo from 'tream_id' to 'term_id'
    ];

    public function Term()  // Changed 'Term' to 'term' to follow Laravel naming conventions
    {
        return $this->belongsTo(Term::class, 'term_id'); // Correct relationship method
    }
}
