<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'father_name',
        'gr_no',
        'class_id',
        'note',
        'status',
        'CreatedBy',
    ];
    public function classes()  // Fixed typo here
    {
        return $this->belongsTo(Classes::class, 'class_id');  // Corrected relationship
    }
    
    
}
