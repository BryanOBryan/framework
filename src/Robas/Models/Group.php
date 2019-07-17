<?php

namespace Kaliba\Robas\Models;

use Kaliba\Collection\Collection;
use Kaliba\ORM\Model;

class Group extends Model
{
    /**
     * Database table
     * @var string
     */
    protected $tableName = 'groups';

    /**
     * Add a user to the group
     *
     * @param integer|User $user Either a user ID or a UserModel instance
     */
    public function addUser( $user )
    {
        if ($this->getKey() === null) {
            return false;
        }
        if ($user instanceof User) {
            $user = $user->getKey();
        }
        $userGroup = new UserGroup();
        return $userGroup->add($user, $this->getKey());
    }

    /**
     * Remove a user from a group
     *
     * @param integer|UserModel $user User ID or model instance
     * @return boolean Success/fail of removal
     */
    public function removeUser( $user )
    {
        if ($this->getKey() === null) {
            return false;
        }
        if ($user instanceof User) {
            $user = $user->getKey();
        }
        return UserGroup::remove($user, $this->getKey());
    }

    /**
     * Attach a permission to a user account
     *
     * @param integer|Permission $permission Permission ID or model instance
     * @param integer $expire Expiration time of the permission relationship
     */
    public function addPermission( $permission, $expire = null )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if ($permission instanceof Permission) {
            $permission = $permission->getKey();
        }
        $model = new UserPermission();
        return $model->add($this->getKey(), $permission, $expire);
    }

    /**
     * Remove a permission from a group
     *
     * @param integer|Permission $permission Permission model or ID
     * @return boolean Success/fail of removal
     */
    public function removePermission( $permission )
    {
        if ($this->getKey() === null) {
            return false;
        }
        if ($permission instanceof Permission) {
            $permission = $permission->getKey();
        }
        return GroupPermission::remove($this->getKey(), $permission);
    }

    /**
     * Check to see if the group has a permission
     *
     * @param integer|Permission $permission Either a permission ID or PermissionModel
     * @return boolean Permission found/not found
     */
    public function hasPermission( $permission )
    {
        if ($this->getKey() === null) {
            return false;
        }
        if ($permission instanceof Permission) {
            $permission = $permission->getKey();
        }
        $groupPermission = GroupPermission::get($this->getKey(), $permission);
        if ($groupPermission === null) {
            return false;
        }
        return ($groupPermission->group_id !== null && $groupPermission->permission_id == $permission) ? true : false;
    }

    /** Get Group Permissions
     * @return Collection
     */
    public function getPermissions()
    {
        return $this->belongsToMany(
            GroupPermission::class,
            Permission::class
        );
    }

    /**
     * Check if the user is in the current group
     *
     * @param integer $userId User ID
     * @return boolean Found/not found in group
     */
    public function contains( $userId )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        $userGroup = UserGroup::get($userId, $this->getKey());
        if ($userGroup === null) {
            return false;
        }else{
			return true;
		}
    }

    /**
     * Check to see if the group is expired
     *
     * @return boolean Expired/Not expired result
     */
    public function isExpired()
    {
        return ($this->expire !== null && $this->expire <= time());
    }

    /**
     * Find Group by name
     * @param string $name
     * @return static
     */
    public static function findByName( $name )
    {
        return static::where('name', $name)->first();
    }


}