<?php

namespace Kaliba\Cache;

/**
 * Storage engine for  Cache
 * This class is from CakePHP(tm) Framework
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * 
 */
abstract class CacheEngine  implements CacheInterface
{
    /**
     *
     * @var int
     */
    protected $duration = 3600;
    
    /**
     *
     * @var int
     */
    protected $probability = 100;
    
    /**
     *
     * @var string
     */
    protected $prefix;    
   
    /**
     *
     * @var array
     */
    protected $groups = [];
    
    /**
     *
     * @var string
     */
    protected $groupPrefix = null; 
    
    /**
     *
     * @var array
     */
    protected $compiledGroupNames = [];   
	
    /**
     * Initialize the cache engine
     *
     *
     * @param array $options A property Object containing associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {	      
        $this->duration = $options['duration']?? null;
        $this->prefix = $options['prefix']?? null;
        $this->probability = $options['probability']?? null;
        $this->groups = $options['groups']??null;
        if (!empty($this->groups)) {
            sort($this->groups);
            $this->groupPrefix = str_repeat('%s_', count($this->groups ));
        }
        return true;
    }
    
    /**
     * Set duration period
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }
    
    /**
     * Get duration period
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }
    
    /**
     * Set filename prefix
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
    
    /**
     * Get filename prefix
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * Set Probability
     * @param int $probability
     */
    public function setProbability($probability)
    {
        $this->probability = $probability;
    }
    
    /**
     * Get Probability
     * @return int
     */
    public function getProbability()
    {
        return $this->probability;
    }
    
    /**
     * Set Groups
     * @param array $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }
    
    /**
     * Does whatever initialization for each group is required
     * and returns the `group value` for each of them, this is
     * the token representing each group in the cache key
     *
     * @return array
     */
    public function groups()
    {
        return $this->groups;
    }
    
    /**
     * Write data for many keys into cache
     *
     * @param array $data An array of data to be stored in the cache
     * @return array of bools for each key provided, true if the data was successfully cached, false on failure
     */
    public function writeMany($data)
    {
        $return = [];
        foreach ($data as $key => $value) {
            $return[$key] = $this->write($key, $value);
        }
        return $return;
    }

    /**
     * Read multiple keys from the cache
     *
     * @param array $keys An array of identifiers for the data
     * @return array For each cache key (given as the array key) the cache data associated or false if the data doesn't
     * exist, has expired, or if there was an error fetching it
     */
    public function readMany($keys)
    {
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->read($key);
        }
        return $return;
    }
    
    /**
     * Deletes keys from the cache
     *
     * @param array $keys An array of identifiers for the data
     * @return array For each provided cache key (given back as the array key) true if the value was successfully deleted,
     * false if it didn't exist or couldn't be removed
     */
    public function deleteMany($keys)
    {
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->delete($key);
        }
        return $return;
    }

    /**
     * Add a key to the cache if it does not already exist.
     *
     * Defaults to a non-atomic implementation. Subclasses should
     * prefer atomic implementations.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     */
    public function add($key, $value)
    {
        $cachedValue = $this->read($key);
        if ($cachedValue === false) {
            return $this->write($key, $value);
        }
        return false;
    }
    
    /**
     * Clears all values belonging to a group. Is up to the implementing engine
     * to decide whether actually delete the keys or just simulate it to achieve
     * the same result.
     *
     * @param string $group name of the group to be cleared
     * @return bool
     */
    public function clearGroup($group)
    {
        return false;
    }

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null){}   
    
    /**
     * Generate a key and return it
     * @param string $key
     * @return boolean
     */
    public function key($key)
    {
        if (empty($key)) {
            return false;
        }

        $prefix = '';
        if (!empty($this->groupPrefix)) {
            $prefix = vsprintf($this->groupPrefix, $this->groups());
        }

        $key = preg_replace('/[\s]+/', '_', strtolower(trim(str_replace([DS, '/', '.'], '_', strval($key)))));
        return $prefix . $key;
    }

    /**
     * Generates a safe key, taking account of the configured key prefix
     *
     * @param string $key the key passed over
     * @return mixed string $key or false
     * @throws \InvalidArgumentException If key's value is empty
     */
    protected function prefixedKey($key)
    {
        $key = $this->key($key);
        if ($key === false) {
            throw new \InvalidArgumentException('An empty value is not valid as a cache key');
        }

        return $this->prefix . $key;
    }
    
    /**
     * Set shared duration
     * @param int $duration
     */
    protected function setSharedDuration($duration)
    {
        if(!isset($this->duration)){
            $this->duration = $duration;
        }
        if(isset( $this->duration) && isset($duration)){
            if( $duration > $this->duration || $duration < $this->duration){
                $this->duration = $duration;
            }
        }
    }
    
    /**
     * Get expiry time
     * @return int
     */
    protected function getExperyTime()
    {
        $expires = 0;
        if ($this->duration) {
            $expires = time() + $this->duration;
        }
        return $expires;
    }

    /**
     * Checks if a key exists in the cache and is set
     * @param string $key
     * @return boolean
     */
    public function check($key)
    {
        $values = $this->read($key);
        return !empty($values)?true:false;
    }
    
}
