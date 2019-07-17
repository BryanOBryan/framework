<?php

namespace Kaliba\Robas;
use Kaliba\Robas\Exceptions\PasswordIncorrectException;
use Kaliba\Robas\Exceptions\PasswordResetInvalid;
use Kaliba\Robas\Exceptions\PasswordResetTimeout;
use Kaliba\Robas\Exceptions\UserBlockedException;
use Kaliba\Robas\Exceptions\UserInactiveException;
use Kaliba\Robas\Exceptions\UserNotFoundException;
use Kaliba\Robas\Restrict\Throttle;
use Kaliba\Robas\Session\Remember;
use Kaliba\Robas\Session\UserAccount;
use Kaliba\Robas\Support\Resolver;
use Kaliba\Robas\Models\User;
use Kaliba\Security\Password;


class Auth
{
    /**
     * Throttling enabled or disabled
     * @var boolean
     */
    private static $throttle = true;

    /**
     * Disable the throttling
     */
    public static function disableThrottle()
    {
        self::$throttle = false;
    }

    /**
     * Enable the throttling feature
     */
    public static function enableThrottle()
    {
        self::$throttle = true;
    }

    /**
     * Check username and password. set remember to true to remember users.
     * @param string $username Username of the user
     * @param string $password Password of the user
     * @param boolean $remember Remember me flag to enable user to be remembered
     * @return void
     */
    public static function login( $username, $password, $remember = false )
    {
        $user = self::find($username);

        if (empty($user)) {
            throw new UserNotFoundException();
        }
        if ($user->isInactive()) {
            throw new UserInactiveException();
        }
        if (self::$throttle) {
            self::throttle($user);
        }
        if (!Password::verify($password, $user->password)) {
            throw new PasswordIncorrectException();
        }     
        if ($remember) {
            Remember::persist($user);
        } 
        $user->updateLastLogin();
        $user->resetAttempts();
        UserAccount::persist($user);
    }

    /**
     * Perform logout
     * @return void
     */
    public static function logout()
    {
        Remember::forget();
        UserAccount::logout();
    }

    /**
     * Authenticate User
     * If user is remembered, setup user session
     * @return bool
     */
    public static function check()
    {
        if(UserAccount::isAlive()){
            return true;
        }
        $user = Remember::getUser();
        if ($user && !empty($user)) {
            return UserAccount::persist($user);
        }
        return false;
    }

    /**
     * Get the current authenticated user
     * @return User
     */
    public static function user()
    {
        return UserAccount::getUser();
    }
    
    /**
     * Check current user permission and permit user action
     * @param string $action
     * @return bool
     */
    public static function permit($action)
    {
        $name = str_replace("/", ".", $action);
        $permissions = static::permissions();
        foreach($permissions as $permission){
            list($permOne, $permTwo) = explode('or', $permission->name);
            if( (trim($permOne) == strtolower($name) ) || (trim($permTwo) == strtolower($name) ) ){
                return true;
            }
        }
        return false;
      
    }
    
    /**
     * Check user group
     * @param string $name
     * @return bool
     */
    public static function group($name)
    {
        $user = static::user();    
        foreach ($user->getGroups() as $group) {
            if(strtolower($group->name) == strtolower($name) ){
                return true;
            }
        }
        return false;
    }

    /**
     * Find a user in the data source by the specified username
     * @param string $criteria id or email of the user to get from the database
     * @return User
     */
    public static function find( $criteria )
    {
        if (is_int($criteria)) {
            return User::find((int)$criteria);
        } elseif (is_email($criteria)) {
            return User::findByEmail((string)$criteria);
        } else {
            return User::findByUsername((string)$criteria);
        }
    }

    /***
     * Check the given code against the value in the database
     * @param string  $resetCode
     * @return User
     * @throws PasswordResetInvalid
     * @throws PasswordResetTimeout
     */
    public static function checkResetCode( $resetCode )
    {
        $user = new User();
        if($user->checkResetCode($resetCode)){
            return $user;
        }
    }

    /**
     * Get current user permissions
     * @return array
     */
    public static function permissions()
    {
        $user = static::user();
        $permissions = Resolver::resolve($user);
        return $permissions;
    }

    /**
     * Enable user throttling
     * @param User $user
     * @throws UserBlockedException
     */
    private static function throttle(User $user)
    {
        $throttle = new Throttle($user);
        if ($throttle->evaluate() == false) {
            throw new UserBlockedException();
        }
    }



}