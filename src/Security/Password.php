<?php

namespace Kaliba\Security;
use RuntimeException;

class Password
{

    /**
     * Hash the given value.
     *
     * @param  string  $value plain text to be hashed
     * @param  int  $cost crypt cost factor.
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function hash($value, $cost=10)
    {
        $hash = password_hash(base64_encode($value), PASSWORD_BCRYPT, ['cost' => $cost]);
        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }
        return $hash;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @return bool
     */
    public static function verify($value, $hashedValue)
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }
        return password_verify(base64_encode($value), $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given cost.
     *
     * @param  string  $hashedValue a hashed value to be checked
     * @param  int  $cost crypt cost factor.
     * @return bool
     */
    public static function needsRehash($hashedValue, $cost=10)
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

}
