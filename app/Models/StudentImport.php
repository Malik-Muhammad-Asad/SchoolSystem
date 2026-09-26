<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentImport extends Model
{
    protected $fillable = [
        'class_id',
        'file_name',
        'total_records',
        'failed_records',
        'failed_data',
    ];

    protected $casts = [
        'failed_data' => 'array',
    ];

    public function classes()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
