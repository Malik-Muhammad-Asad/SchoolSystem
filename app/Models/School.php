<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'expiry_date',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function schoolSetting()
    {
        return $this->hasOne(SchoolSetting::class);
    }
}
