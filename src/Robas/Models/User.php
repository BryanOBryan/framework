<?php

namespace Kaliba\Robas\Models;

use Kaliba\Collection\Collection;
use Kaliba\Robas\Exceptions\PasswordResetInvalid;
use Kaliba\Robas\Exceptions\PasswordResetTimeout;
use Kaliba\ORM\Model;

class User extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Database table name
     * @var string
     */
    protected $tableName = 'users';

    /**
     * Get full name
     * @return mixed|string
     */
    public function getName()
    {
        if(!empty($this->name)){
            return $this->name;
        }else{
            return $this->firstname. ' '. $this->lastname;
        }
    }

    /**
     * Attach a permission to a user account
     *
     * @param integer|Permission $permission Permission ID or model instance
     */
    public function addPermission( $permission)
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if ($permission instanceof Permission) {
            $permission = $permission->getKey();
        }
        $model = new UserPermission();
        return $model->add($this->getKey(), $permission);
    }

    /**
     * Revoke a user permission
     *
     * @param integer|Permission $permission Permission ID or model instance
     * @return boolean Success/fail of delete
     */
    public function revokePermission( $permission )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if ($permission instanceof Permission) {
            $permission = $permission->getKey();
        }
        return UserPermission::remove($this->getKey(), $permission);
    }

    /**
     * Check to see if a user has a permission
     *
     * @param integer $permissionId Permission ID or name
     * @return boolean Found/not found in user permission set
     */
    public function hasPermission( $permissionId )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if (!is_numeric($permissionId)) {
            $permission = Permission::findByName($permissionId);
            $permissionId = $permission->getKey();
        }

        $userPermission = UserPermission::get($this->getKey(), $permissionId);
        if ($userPermission === null) {
            return false;
        }else{
			return true;
		}
    }

    /**
     * Get User Permissions
     * @return Collection
     */
    public function getPermissions()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        return $this->belongsToMany(
            UserPermission::class,
            Permission::class
        );
    }

    /**
     * Add a group to the user
     *
     * @param integer|Group $group Add the user to a group
     * @return boolean Success/fail of add
     */
    public function addGroup( $group)
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if ($group instanceof Group) {
            $group = $group->getKey();
        }
        $model = new UserGroup();
        return $model->add($this->getKey(), $group);
    }

    /**
     * Revoke access to a group for a user
     *
     * @param integer|Group $group ID or model of group to remove
     * @return boolean
     */
    public function revokeGroup( $group )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if ($group instanceof Group) {
            $group = $group->getKey();
        }
        return UserGroup::remove($this->getKey(), $group);
    }

    /**
     * Check to see if the user is in the group
     *
     * @param integer $groupId Group ID or name
     * @return boolean Found/not found in the group
     */
    public function inGroup( $groupId )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        if (!is_numeric($groupId)) {
            $group = Group::findByName($groupId);
            $groupId = $group->getKey();
        }

        $userGroup = UserGroup::get($this->getKey(), $groupId);
        if ($userGroup === null) {
            return false;
        }else{
			return true;
		}
    }

    /**
     * Get User Group to which user belongs
     * @return Collection
     */
    public function getGroups()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        return $this->belongsToMany(
            UserGroup::class,
            Group::class
        );
    }

    /**
     * Get User Group to which user belongs
     * @return Group
     */
    public function getGroup()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        return $this->belongsToMany(
            UserGroup::class,
            Group::class
        )->first();
    }

    /**
     * Activate the user (status)
     *
     * @return boolean Success/fail of activation
     */
    public function activate()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }

    /**
     * Deactivate the user
     *
     * @return boolean Success/fail of deactivation
     */
    public function deactivate()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        $this->status = self::STATUS_INACTIVE;
        return $this->save();
    }

    /**
     * Generate and return the code for a password reset
     *     Also updates the user record
     *
     * @param integer $length Length of returned string
     * @return string Geenrated code
     */
    public function getResetCode($length = 80 )
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        // Generate a random-ish code and save it to the user record
        $code = substr(bin2hex(random_bytes($length)), 0, $length);
        $timeout = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $passwordReset = new PasswordReset();
        $passwordReset->saveResetCode($this->getKey(), $code, $timeout);
        return $code;
    }

    /**
     * Check the given code against the value in the database
     *
     * @param string $resetCode Reset code to verify
     * @return bool
     * @throws PasswordResetInvalid
     * @throws PasswordResetTimeout
     */
    public function checkResetCode($resetCode )
    {
        $passwordReset = PasswordReset::findResetCode($resetCode);
        if(empty($passwordReset)){
            throw new PasswordResetInvalid();
        }
        // Verify the timeout
        if ($passwordReset->reset_timeout <= new \DateTime()) {
            $passwordReset->delete();
            throw new PasswordResetTimeout();
        }
        // We made it this far, compare the hashes
        if(hash_equals($passwordReset->reset_code, $resetCode)){
            $passwordReset->delete();
            $this->load($passwordReset->user_id);
            return true;
        }else{
            return false;
        }

    }


    /**
     * Check to see if a user is banned
     *
     * @return boolean User is/is not banned
     */
    public function isBanned()
    {
        // Verify we have a user
        if ($this->getKey() === null) {
            return false;
        }
        $throttle = UserThrottle::findByUser($this->getKey());

        return ($throttle->isBlocked()) ? true : false;
    }

    /**
     * Check if user is not active
     * @return bool
     */
    public function isInactive()
    {
        return ($this->status == static::STATUS_INACTIVE) ? true : false;
    }

    /**
     * Find the number of login attempts for a user
     *
     * @param integer $userId User ID [optional]
     * @return integer Number of login attempts
     */
    public function findAttempts($userId = null )
    {
        $userId = ($userId === null) ? $this->getKey() : $userId;

        $throttle = UserThrottle::findByUser($userId);

        return ($throttle->attempts === null) ? 0 : $throttle->attempts;
    }

    /**
     * Handle granting of multiple permissions
     *
     * @param array $permissions Set of permissions (either IDs or objects)
     * @return boolean Success/fail of all saves
     */
    public function grantPermissions(array $permissions )
    {
        $return = false;
        foreach ($permissions as $permission) {
            $return = $this->addPermission($permission);
        }
        return $return;
    }

    /**
     * Handle granting of multiple groups
     *
     * @param array $groups Set of groups (either IDs or objects)
     * @return boolean Success/fail of all saves
     */
    public function grantGroups( array $groups)
    {
        $return = false;
        foreach ($groups as $group) {
            $return = $this->addGroup($group);
        }
        return $return;
    }

    /**
     * Update the last login time for the current user
     *
     * @param integer $time Unix timestamp [optional]
     * @return boolean Success/fail of update
     */
    public function updateLastLogin()
    {
        $this->last_login = new \DateTime();
        return $this->save();
    }
	
    /**
     * Reset login attempts
     * @param int|null $userId
     * @return bool
     */
    public function resetAttempts()
    {
        $throttle = UserThrottle::findByUser($this->getKey());
        $throttle->attempts = 1;
        return $throttle->save();
    }

    /**
     * Find the user by username
     * If found, user is automatically loaded into model instance
     * @param string $username Username
     * @return static
     */
    public static function findByUsername( $username )
    {
        return static::where('username', $username)->first();
    }

    /**
     * Find the user by email
     * If found, user is automatically loaded into model instance
     * @param string $email Email adddress
     * @return static
     */
    public static function findByEmail( $email )
    {
        return static::where('email', $email)->first();
    }


}