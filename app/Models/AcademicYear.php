<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    // Allow mass assignment for these fields
    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'is_current',
    ];

    // Cast fields to appropriate types
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    // Optional: Always return the current academic year
    public static function current()
    {
        return self::where('is_current', true)->first();
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->is_current) {
                self::where('id', '!=', $model->id)->update(['is_current' => false]);
            }
        });
    }
}
