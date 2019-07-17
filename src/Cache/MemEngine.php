<?php

namespace Kaliba\Cache;
use InvalidArgumentException;
use Memcached;

/**
 * Memcached storage engine for cache. Memcached has some limitations in the amount of
 * control you have over expire times far in the future. See MemcachedDriver::write() for
 * more information.
 *
 * Main advantage of this Memcached engine over the memcached engine is
 * support of binary protocol, and igbinary serialization
 * (if memcached extension compiled with --enable-igbinary)
 * Compressed keys can also be incremented/decremented
 *
 */
class MemEngine extends CacheEngine
{

    protected $compress = false;
    protected $username = null;
    protected $password = null;
    protected $persistent = false;
    protected $serialize = 'php';
    protected $port = null;
    protected $servers = ['127.0.0.1'];
    protected $host = null;
    protected $options = [];
    
    /**
     * memcached wrapper.
     *
     * @var \Memcached
     */
    protected $Memcached = null;

    /**
     * List of available serializer engines
     *
     * Memcached must be compiled with json and igbinary support to use these engines
     *
     * @var array
     */
    protected $serializers = [];
    
    /**
     * Initialize the cache engine
     *
     *
     * @param array $options A property Object containing associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {
        if (!extension_loaded('memcached')) {
            return false;
        }
        parent::__construct($options);
        $this->init($options);
        $this->setSerializers();
        $this->createMemcache();
        $this->setOptions();
        $this->configMemcache();
        
    }
    
    protected function init(array $options)
    {
        $this->compress = $options['compress']?? null;
        $this->username = $options['username']?? null;
        $this->password = $options['password']?? null;
        $this->persistent = $options['persistent'] ?? null;
        $this->serialize = $options['serialize'] ?? null;
        $this->port = $options['port'] ?? null;
        $this->servers = $options['servers'] ?? [];
        $this->host = $options['host'] ?? null;
        $this->options = $options['options'] ?? [];
    }

    protected function setSerializers()
    {
        $this->serializers = [
            'igbinary' => Memcached::SERIALIZER_IGBINARY,
            'json' => Memcached::SERIALIZER_JSON,
            'php' => Memcached::SERIALIZER_PHP
        ];
        if (defined('Memcached::HAVE_MSGPACK') && Memcached::HAVE_MSGPACK) {
            $this->serializers['msgpack'] = Memcached::SERIALIZER_MSGPACK;
        }
    }
    
    protected function createMemCache()
    {
        if (isset($this->Memcached)) {
            return true;
        }
        if ($this->persistent) {
            $this->Memcached = new Memcached((string)$this->persistent);
        } else {
            $this->Memcached = new Memcached();
        }
    }
    
    /**
     * Settings the memcached instance
     *
     * @return void
     * @throws InvalidArgumentException When the Memcached extension is not built
     *   with the desired serializer engine.
     */
    protected function setOptions()
    {
        $this->Memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        $serializer = strtolower($this->serialize);
        if (!isset($this->serializers[$serializer])) {
            throw new InvalidArgumentException(sprintf('%s is not a valid serializer engine for Memcached', $serializer));
        }
        if ($serializer !== 'php' &&!constant('Memcached::HAVE_' . strtoupper($serializer))) {
            throw new InvalidArgumentException(sprintf('Memcached extension is not compiled with %s support', $serializer));
        }
        $this->Memcached->setOption(Memcached::OPT_SERIALIZER,$this->serializers[$serializer]);

        // Check for Amazon ElastiCache instance
        if (defined('Memcached::OPT_CLIENT_MODE') & defined('Memcached::DYNAMIC_CLIENT_MODE')) {
            $this->Memcached->setOption(Memcached::OPT_CLIENT_MODE, Memcached::DYNAMIC_CLIENT_MODE);
        }
        $this->Memcached->setOption(Memcached::OPT_COMPRESSION,(bool)$this->config['compress']);
    }

    protected function configMemcache()
    {
        if (count($this->Memcached->getServerList())) {return true;}
        $servers = [];
        foreach ($this->servers as $server) {
            $servers[] = $this->parseServerString($server);
        }
        if (!$this->Memcached->addServers($servers)) {
            return false;
        }
        if (is_array($this->options)) {
            foreach ($this->options as $opt => $value) {
                $this->Memcached->setOption($opt, $value);
            }
        }
        if ($this->username !== null && $this->password !== null) {
            $sasl = method_exists($this->Memcached, 'setSaslAuthData') && ini_get('memcached.use_sasl');
            if (!$sasl) {throw new InvalidArgumentException('Memcached extension is not build with SASL support');}
            $this->Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->Memcached->setSaslAuthData($this->username,$this->password);
        }
    }
      
    /**
     * Parses the server address into the host/port. Handles both IPv6 and IPv4
     * addresses and Unix sockets
     *
     * @param string $server The server address string.
     * @return array Array containing host, port
     */
    protected function parseServerString($server)
    {
        if (strpos($server, 'unix://') === 0) {
            return [$server, 0];
        }
        if (substr($server, 0, 1) === '[') {
            $position = strpos($server, ']:');
            if ($position !== false) {
                $position++;
            }
        } else {
            $position = strpos($server, ':');
        }
        $port = 11211;
        $host = $server;
        if ($position !== false) {
            $host = substr($server, 0, $position);
            $port = substr($server, $position + 1);
        }
        return [$host, (int)$port];
    }

    /**
     * @inheritDoc
     */
    public function write($key, $value, $duration = 3600)
    {
        $this->setSharedDuration($duration);
        
        if ($this->duration > 30 *  DAY) {
            $this->setSharedDuration(0);
        }

        $key = $this->prefixedKey($key);

        return $this->Memcached->set($key, $value, $this->duration);
    }

    /**
     * @inheritDoc
     */
    public function writeMany($data)
    {
        $cacheData = [];
        foreach ($data as $key => $value) {
            $cacheData[$this->prefixedKey($key)] = $value;
        }

        $success = $this->Memcached->setMulti($cacheData);

        $return = [];
        foreach (arraykeys($data) as $key) {
            $return[$key] = $success;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function read($key)
    {
        $key = $this->prefixedKey($key);

        return $this->Memcached->get($key);
    }

    /**
     * @inheritDoc
     */
    public function readMany($keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->prefixedKey($key);
        }

        $values = $this->Memcached->getMulti($cacheKeys);
        $return = [];
        foreach ($keys as &$key) {
            $return[$key] = arraykey_exists($this->prefixedKey($key), $values) ?
                $values[$this->prefixedKey($key)] : false;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return $this->Memcached->increment($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return $this->Memcached->decrement($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $key = $this->prefixedKey($key);

        return $this->Memcached->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteMany($keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->prefixedKey($key);
        }

        $success = $this->Memcached->deleteMulti($cacheKeys);

        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $success;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }

        $keys = $this->Memcached->getAllKeys();
        if ($keys === false) {
            return false;
        }

        foreach ($keys as $key) {
            if (strpos($key, $this->prefix) === 0) {
                $this->Memcached->delete($key);
            }
        }

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
        if (empty($this->compiledGroupNames)) {
            foreach ($this->groups as $group) {
                $this->compiledGroupNames[] = $this->prefix . $group;
            }
        }

        $groups = $this->Memcached->getMulti($this->compiledGroupNames);
        if (count($groups) !== count($this->groups)) {
            foreach ($this->compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    $this->Memcached->set($group, 1, 0);
                    $groups[$group] = 1;
                }
            }
            ksort($groups);
        }

        $result = [];
        $groups = array_values($groups);
        foreach ($this->groups as $i => $group) {
            $result[] = $group . $groups[$i];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearGroup($group)
    {
        return (bool)$this->Memcached->increment($this->prefix . $group);
    }

}
