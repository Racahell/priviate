<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected static function booted(): void
    {
        static::creating(function (Permission $permission) {
            if (empty($permission->guard_name)) {
                $permission->guard_name = 'web';
            }
        });
    }
}
