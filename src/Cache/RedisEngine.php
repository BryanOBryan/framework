<?php

namespace Kaliba\Cache;
use Redis;
use RedisException;

/**
 * Redis storage engine for cache.
 *
 */
class RedisEngine extends CacheEngine
{
    protected $database = 0;
    protected $password = null;
    protected $persistent = true;
    protected $port = null;
    protected $host = null;
    protected $server = '127.0.0.1';
    protected $timeout = 0;
    protected $unix_socket = false;
    
    /**
     * Redis wrapper.
     *
     * @var \Redis
     */
    protected $Redis = null;

    /**
     * Initialize the Cache Driver
     *
     * Called automatically by the cache front end
     *
     * @param array $options array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {
        if (!extension_loaded('redis')) {
            return false;
        }
        parent::__construct($options);
        $this->init($options);       
        return $this->connect();
    }
    
    protected function init(array $options)
    {
        $this->database = $options['database']??null;
        $this->password = $options['password']?? null;
        $this->persistent = $options['persistent']?? null;
        $this->port = $options['port']??null;
        $this->host = $options['host']??null;
        $this->server = $options['server']?? null;
        $this->timeout = $options['timeout']?? null;
        $this->unix_socket = $options['unix_socket']?? null;
    }

    /**
     * Connects to a Redis server
     *
     * @return bool True if Redis server was connected
     */
    protected function connect()
    {
        try {
            $this->Redis = new Redis();
            if (!empty($this->unix_socket)) {
                $return = $this->Redis->connect($this->unix_socket);
            } elseif (empty($this->persistent)) {
                $return = $this->Redis->connect($this->server, $this->port, $this->timeout);
            } else {
                $persistentId = $this->port. $this->timeout . $this->database;
                $return = $this->Redis->pconnect($this->server, $this->port, $this->timeout, $persistentId);
            }
        } catch (RedisException $e) {
            return false;
        }
        if ($return && $this->password) {
            $return = $this->Redis->auth($this->password);
        }
        if ($return) {
            $return = $this->Redis->select($this->database);
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function write($key, $value, $duration = 3600)
    {
        $this->setSharedDuration($duration);
        $key = $this->prefixedKey($key);

        if (!is_int($value)) {
            $value = serialize($value);
        }
        if ($this->duration === 0) {
            return $this->Redis->set($key, $value);
        }
        return $this->Redis->setex($key, $this->duration, $value);
    }

    /**
     * @inheritDoc
     */
    public function read($key)
    {
        $key = $this->prefixedKey($key);

        $value = $this->Redis->get($key);
        if (ctype_digit($value)) {
            $value = (int)$value;
        }
        if ($value !== false && is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return (int)$this->Redis->incrBy($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return (int)$this->Redis->decrBy($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $key = $this->prefixedKey($key);

        return $this->Redis->delete($key) > 0;
    }

    /**
     * @inheritDoc
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }
        $keys = $this->Redis->getKeys($this->prefix. '*');
        $this->Redis->del($keys);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function add($key, $value)
    {
        return $this->write($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function groups()
    {
        $result = [];
        foreach ($this->groups as $group) {
            $value = $this->Redis->get($this->prefix . $group);
            if (!$value) {
                $value = 1;
                $this->Redis->set($this->prefix . $group, $value);
            }
            $result[] = $group . $value;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearGroup($group)
    {
        return (bool)$this->Redis->incr($this->prefix . $group);
    }
    
    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (empty($this->persistent) && $this->Redis instanceof Redis) {
            $this->Redis->close();
        }
    }
    
}
