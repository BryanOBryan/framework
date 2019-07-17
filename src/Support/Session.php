<?php

namespace Kaliba\Support;
use Kaliba\Support\Flash;

class Session
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
     * Starts a session
     */
    public function start()
    {
        if( $this->id() == null ){
            session_start();                
        }
    }
    
    /**
     * Stores a value in the session
     * @param string $key Identifier of the value in the session storage
     * @param mixed $value The actual value
     */
    public  function set($key,$value)
    {
        $_SESSION[$key] = $value;
        return true;
    }
    
    /**
     * Gets a value from the session
     * @param string $key  Identifier of the value in the session storage
     * @return mixed
     */
    public  function get($key)
    {
        if($this->has($key)){
           return  $_SESSION[$key]; 
        }     
    }
    
    /**
     * Checks whether a key exists in the session storage
     * @param string $key Identifier of the value in the session storage
     * @return bool
     */
    public function has($key)
    {
        if(isset($_SESSION[$key])){
           return  true;
        }else{
            return false;
        } 
    }
    
    /**
     * Destroys a key and its associated value from the session storage
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        if($this->has($key)){
            unset($_SESSION[$key]);
        }
        
    }
    
    /**
     * Destroys the whole session
     * @param string $key
     * @return void
     */
    public function destroy($key=null)
    {
        if(!empty($key)){
            return $this->delete($key);
        }else{
            session_destroy();
        }
        
    }
	
    /**
     * Resets the session. 
     * This method empties global $_SESSION variable and restarts the session.
     * It regenerates session id
     * @return void
     */
    public function reset()
    {
        $_SESSION = [];
        $this->restart();
    }

    /**
     * Updates the old session id with a new session id
     * @return void
     */
    public  function restart()
    {
        session_regenerate_id();
    }

    /**
     * Gets the Session ID
     * @return string Session ID
     */
    public  function id()
    {
        return session_id();
    }

}
