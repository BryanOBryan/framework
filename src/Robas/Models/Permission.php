<?php

namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class Permission extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'permissions';

    /**
     * Get grouped permissions
     * @return array
     */
    public static function grouped()
    {
        $permissions = self::all();
        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission->object][] = $permission;
        }
        return $grouped;
    }

    /**
     * Find Permission by name
     * @param string $name
     * @return static
     */
    public static function findByName( $name )
    {
        return static::where('name', $name)->first();
    }

}