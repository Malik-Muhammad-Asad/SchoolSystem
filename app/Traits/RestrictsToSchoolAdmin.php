<?php

namespace App\Traits;

trait RestrictsToSchoolAdmin
{
    public static function canViewAny(): bool
    {
        return !auth()->user()->is_super_admin;
    }
}
