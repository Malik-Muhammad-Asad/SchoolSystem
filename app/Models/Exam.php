<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{ use HasFactory;
    protected $fillable = [
        'name',
        'tream_id',];

    public function Trem()  // Fixed typo here
    {
        return $this->belongsTo(Term::class, 'term_id');  // Corrected relationship
    }
    
}
