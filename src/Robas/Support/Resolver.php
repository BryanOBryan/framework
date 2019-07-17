<?php
namespace Kaliba\Robas\Support;
use Kaliba\Robas\Models\User;

class Resolver 
{
    /**
     * Resolve the user's immediate permissions (directly on the user
     *    and on the groups the user belongs to)
     *
     * @param User $user User instance
     * @return array
     */
    public static function resolve( User $user )
    {
        // Start with the user's direct permissions
        $permissions = $user->getPermissions();
        $groups = $user->getGroups();
        // Now find the ones in the user's groups too
        foreach ($groups as $group) {
            $groupPermissions = $group->getPermissions();
            foreach ($groupPermissions as $permission) {
                $permissions[] = $permission;
            }
        }
        return $permissions;
    }
    
}
