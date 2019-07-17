<?php

namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class UserPermission extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'user_permissions';

    /**
     * Add User Permission
     * @param int $userId
     * @param int $permissionId
     * @return bool
     */
    public function add( $userId, $permissionId)
    {
        try {
            return $this->save([
                'user_id' => $userId,
                'permission_id' => $permissionId
            ]);
        } catch (\PDOException $ex) {}

    }

    /** Remove User Permission
     * @param int $userId
     * @param int $permissionId
     * @return bool
     */
    public static function remove( $userId, $permissionId )
    {
        try {
            return static::destroy([
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ]);
        } catch (\PDOException $ex) {}

    }

    /**
     * Get User Permission By UserID and PermissionID
     * @param int $userId
     * @param int $permissionId
     * @return static
     */
    public static function get( $userId, $permissionId )
    {
        return static::findBy([
            'user_id' => $userId,
            'permission_id' => $permissionId,
        ])->first();
    }

}