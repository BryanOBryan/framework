<?php

namespace Kaliba\Support;


 class Cookie
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @return self
     */
    public static function instance()
    {
        if(!self::$instance instanceof self){
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Stores a value in the cookie
     * @param string $key Identifier of the value in the cookie storage
     * @param mixed $value The actual value
     * @param long $expiry The expiry time in seconds
     */
    public  function set($key,$value, $expiry)
    {
        if(setcookie($key, $value, (int)$expiry, '/')){
            return true;
        }
        return true;
    }
    
    /**
     * Gets a value from the cookie
     * @param string $key  Identifier of the value in the cookie storage
     * @return mixed
     */
    public function get($key)
    {
        if(self::has($key)){
           return  $_COOKIE[$key]; 
        }     
    }
    
    /**
     * Checks whether a key exists in the cookie storage
     * @param string $key Identifier of the value in the cookie storage
     * @return bool
     */
    public function has($key)
    {
        if(isset($_COOKIE[$key])){
           return  true;
        }else{
            return false;
        } 
    }
    
    /**
     * Destroys a key and its associated value from the cookie storage
     * @param string $key
     * @return void
     */
    public function destroy($key)
    {
        if($this->has($key)){
            $this->set($key, '', time()-1);
        }
        
    }
	    
}
