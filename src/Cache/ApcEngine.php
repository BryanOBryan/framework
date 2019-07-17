<?php

namespace Kaliba\Cache;
use APCIterator;

/**
 * APC storage engine for cache
 *
 */
class ApcEngine extends CacheEngine
{
    /**
     * Initialize the cache engine
     *
     *
     * @param array $options A property Object containing associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {	      
        if (!extension_loaded('apc')) {
            return false;
        }
        parent::__construct($options);
    }
   
    /**
     * Write value for a key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param int $duration Time to live in seconds, default is 3600 seconds = 1 hour
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $value, $duration = 3600)
    {
        $this->setSharedDuration($duration);       
        $key = $this->prefixedKey($key);

        $expires = $this->getExperyTime();
        apc_store($key . '_expires', $expires, $this->duration);
        return apc_store($key, $value, $this->duration);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $key = $this->prefixedKey($key);

        $time = time();
        $cachetime = (int)apc_fetch($key . '_expires');
        if ($cachetime !== 0 && ($cachetime < $time || ($time + $this->duration) < $cachetime)) {
            return false;
        }
        return apc_fetch($key);
    }

     /**
     * Increment a number under the key and return incremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @return bool|int New incremented value, false otherwise
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return apc_inc($key, $offset);
    }

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New incremented value, false otherwise
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return apc_dec($key, $offset);
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function delete($key)
    {
        $key = $this->prefixedKey($key);

        return apc_delete($key);
    }

    /**
     * Delete all keys from the cache
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }
        if (class_exists('APCIterator', false)) {
            $prefix = '/^'.preg_quote($this->prefix, '/').'/';
            $iterator = new APCIterator('user',$prefix,APC_ITER_NONE);
            apc_delete($iterator);
            return true;
        }
        $cache = apc_cache_info('user');
        foreach ($cache['cache_list'] as $key) {
            if (strpos($key['info'], $this->prefix) === 0) {
                apc_delete($key['info']);
            }
        }
        return true;
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
        return $this->write($key, $value);
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
        if (empty($this->compiledGroupNames)) {
            foreach ($this->groups as $group) {
                $this->compiledGroupNames[] = $this->prefix . $group;
            }
        }

        $groups = apc_fetch($this->compiledGroupNames);
        if (count($groups) !== count($this->groups)) {
            foreach ($this->compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    apc_store($group, 1);
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
     * Clears all values belonging to a group. Is up to the implementing engine
     * to decide whether actually delete the keys or just simulate it to achieve
     * the same result.
     *
     * @param string $group name of the group to be cleared
     * @return bool
     */
    public function clearGroup($group)
    {

        return apc_inc($this->prefix . $group, 1);

    }

}
