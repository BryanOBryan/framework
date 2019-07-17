<?php

namespace Kaliba\Support;

class Flash
{
    /**
     * @var self
     */
    private static $instance;

    /**
     *
     * @var string
     */
    const FLASH_ERROR = "FLASH_ERROR";

    /**
     *
     * @var string
     */
    const FLASH_SUCCESS = "FLASH_SUCCESS";

    /**
     *
     * @var string
     */
    const FLASH_INFO = "FLASH_INFO";

    /**
     *
     * @var string
     */
    const FLASH_WARNING = "FLASH_WARNING";

    /**
     * @var Session
     */
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

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
     * Set or  Get flash message
     * @param string $message Flash Message to send to client
     */
    public function error($message = null)
    {
        return $this->message($message, self::FLASH_ERROR);

    }

    /**
     * Set or  Get flash message
     * @param string $message Flash Message to send to client
     */
    public function success($message = null)
    {
        return $this->message($message, self::FLASH_SUCCESS);

    }

    /**
     * Set or  Get flash message
     * @param string $message Flash Message to send to client
     */
    public function info($message = null)
    {
        return $this->message($message, self::FLASH_INFO);

    }

    /**
     * Set or  Get flash message
     * @param string $message Flash Message to send to client
     */
    public function warning($message = null)
    {
        return $this->message($message, self::FLASH_WARNING);

    }

    /**
     * @param string $message
     * @param string $type
     * @return mixed
     */
    public function message($message, $type)
    {
        if(empty($message)){
            $message = $this->session->get($type);
            $this->session->destroy($type);
            return $message;
        }else{
            $this->session->set($type, $message);
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->session->has(static::FLASH_ERROR);
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->session->has(static::FLASH_SUCCESS);
    }

    /**
     * @return bool
     */
    public function isInfo()
    {
        return $this->session->has(static::FLASH_INFO);
    }

    /**
     * @return bool
     */
    public function isWarning()
    {
        return $this->session->has(static::FLASH_WARNING);
    }
}
