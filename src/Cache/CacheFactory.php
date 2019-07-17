<?php

namespace Kaliba\Cache;
use Kaliba\Contracts\Cache\CacheInterface;

class CacheFactory 
{    
    /**
     * List of supported drivers
     * @var array
     */
    private $drivers = [
        'apc'       =>  ApcEngine::class,
        'file'      =>  FileEngine::class,
        'memcache'  =>  MemEngine::class,
        'redis'     =>  RedisEngine::class,
        'wincache'  =>  WinEngine::class
    ];
   
     /**
     * Create A cache instance based on the configuration.
     * @param array $config an array of configurations
     * @return CacheInterface
     */
    public function create(array $config)
    {
        $driver =  (string)$config['driver'];
        if (empty($driver)) {
            throw new \InvalidArgumentException('A driver must be specified.');
        }      
        if(isset($this->drivers[$driver])){
            $driver = $this->drivers[$driver];
            $logger = new $driver($config);
            return $logger;
        }
        throw new \InvalidArgumentException("Unsupported driver {$driver}"); 
    }

}
