<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function afterCreate(): void
    {
        $school = $this->record;
        
        // Create a user for this school
        $user = \App\Models\User::create([
            'name' => $school->name . ' Admin',
            'email' => $school->email ?? strtolower(str_replace(' ', '.', $school->name)) . '@system.com',
            'password' => bcrypt('password'), // You might want to generate a random password and send it via email
            'is_super_admin' => false,
        ]);

        // Attach user to school
        $user->schools()->attach($school->id);

        // Assign role if needed (assuming FilamentShield is used)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);
            
            // If the role was just created or anyway, ensure it has all permissions
            // This is a simple way to ensure the user can access pages as requested
            if (class_exists(\Spatie\Permission\Models\Permission::class)) {
                $allPermissions = \Spatie\Permission\Models\Permission::all();
                $role->syncPermissions($allPermissions);
            }
            
            $user->assignRole($role);
        }
    }
}
