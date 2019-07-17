<?php

namespace Kaliba\Robas\Models;

use Kaliba\ORM\Model;

class GroupPermission extends Model
{

    /**
     * Database table
     * @var string
     */
    protected $tableName = 'group_permissions';


    /**
     * Add Group Permission
     * @param int $groupId
     * @param int $permissionId
     * @param int $expire
     * @return bool
     */
    public function add( $groupId, $permissionId )
    {
        try {
            return $this->save([
                'group_id' => $groupId,
                'permission_id' => $permissionId
            ]);
        } catch (\PDOException $ex) {}

    }

    /** Remove Group Permission
     * @param int $groupId
     * @param int $permissionId
     * @return bool
     */
    public static function remove( $groupId, $permissionId )
    {
        try {
            return static::destroyBy([
                'group_id' => $groupId,
                'permission_id' => $permissionId,
            ]);
        } catch (\PDOException $ex) {}

    }

    /**
     * Get Group Permission By GroupID and PermissionID
     * @param int $groupId
     * @param int $permissionId
     * @return static
     */
    public static function get( $groupId, $permissionId )
    {
        return static::where([
            'group_id' => $groupId,
            'permission_id' => $permissionId,
        ])->first();
    }

}