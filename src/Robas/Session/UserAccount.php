<?php

namespace Kaliba\Robas\Session;
use Kaliba\Http\Session;
use Kaliba\Robas\Models\User;

class UserAccount
{
    /**
     *
     * @var string
     */
    private static $sessionName = 'UID';

    /**
     * Persist User Session
     * @param User $user
     */
    public static function persist(User $user)
    {
        session()->set(self::$sessionName, $user->getKey());
        return true;
    }

    /**
     * Check if User session is alive
     * @return bool
     */
    public static function isAlive()
    {
        return(session()->has(self::$sessionName))?true:false;
    }

    /**
     * Destroy User session
     */
    public static function logout()
    {
        session()->destroy(static::$sessionName);
        return true;
    }

    /**
     * Get User Model
     * @return User
     */
    public static function getUser()
    {
        if (self::isAlive()) {
            $userId = session()->get(self::$sessionName);
            return User::find($userId);
        }
    }

}