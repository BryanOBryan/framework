<?php

namespace Kaliba\Robas\Session;
use Kaliba\Robas\Models\UserToken;
use Kaliba\Robas\Models\User;
use Kaliba\Http\Cookie;
use Kaliba\Security\Hash;

class Remember
{
    /**
     * Token name
     * @var string
     */
    private static $cookieName = 'RMT';

    /**
     * Default expiration time
     * @var string
     */
    private static $expireInterval = '+365 days';

    /**
     * Verify if the token exists and return user
     *
     * @return boolean|User Pass/fail result of the validation
     */
    public static function getUser()
    {
        if (!cookie()->has(self::$cookieName)) {
            return false;
        }
        $cookie = cookie()->get(self::$cookieName);
        $tokenParts = explode(':', $cookie);
        $token = $tokenParts[0];
        $verifier = $tokenParts[1];
        $userToken = UserToken::findByToken($token);
        if ($userToken === null) {
            return false;
        }
        $tokenString = self::combine($token, $verifier);
        if (hash_equals($cookie, $tokenString) === false) {
            return false;
        }
        $user = $userToken->getUser();
        return $user;

    }

    /**
     * Setup the "remember me" session and cookies
     *
     * @param User $user User model instance [optional]
     * @return boolean Success/fail of setting up the session/cookies
     */
    public static function persist( User $user )
    {
        $userToken = UserToken::findByUser($user->getKey());
        
        $token = Hash::unique();
        $verifier = Hash::unique();
        $expires = new \DateTime(self::$expireInterval);
		
        $tokenModel = new UserToken();		
		
        $tokenModel->saveToken($token, $user->getKey(), $expires);

        $tokenString = self::combine($token, $verifier);
        
        cookie()->set( self::$cookieName, $tokenString, $expires->getTimestamp());
    }

    /**
     * Delete remember me tokens
     * @return bool
     */
    public static function forget()
    {
        if (!cookie()->has(self::$cookieName)) {
            return false;
        }
        $cookie = cookie()->get(self::$cookieName);
        $tokenParts = explode(':', $cookie);
        $token = $tokenParts[0];
        $verifier = $tokenParts[1];
        UserToken::deleteByToken($token);
        cookie()->destroy(self::$cookieName);
    }

    /**
     * @param string $token
     * @param string $verifier
     * @return string
     */
    private static function combine( $token, $verifier )
    {
        return $token.':'.$verifier;
    }


}
