<?php
namespace Kaliba\Security;

class Hash
{
    
    private static $algo = "sha256";

    /**
     * One way hasher
     * Hashes the given string.
     * @param  string  $string plain text to be hashed
     * @param  string  $algo hashing algorithm to use for hashing i.e MD5, SHA1
     * @param  string   $salt an alphanum or alphabet text to use for masking 
     * @return string a hashed string that cannot be reversed
     *
     */
    public static function make($string, $algo=null, $salt= null){  
        if(empty($algo)){
            $algo = static::$algo;
        }
        if(empty($salt)){
            $salt = static::salt();
        }
        $context = hash_init($algo, HASH_HMAC, $salt);
        hash_update($context, $string);
        return hash_final($context);
        
    }
    
    /**
     * Create a salt value from random values
     * @param int $size
     * @return string
     */
    public static function salt($size=32)
    {
        return bin2hex(random_bytes($size));
    }
    
    /**
     * Create a unique hash
     * @return string
     */
    public static function unique()
    {
        return self::make(uniqid());
    }
    
}

