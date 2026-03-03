<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->guard_name)) {
                $role->guard_name = 'web';
            }
        });
    }
}
