<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_image',
        'signature_image',
        'watermark_image',
        'school_name',
        'school_address',
        'school_phone',
        'school_email',
    ];
}
