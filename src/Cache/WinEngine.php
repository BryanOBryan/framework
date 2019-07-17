<?php

namespace Kaliba\Cache;

/**
 * Wincache storage engine for cache
 *
 * Supports wincache 1.1.0 and higher.
 */
class WinEngine extends CacheEngine
{
    
    /**
     * Initialize the Cache Driver
     *
     * Called automatically by the cache front end
     *
     * @param array $options 
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {
        if (!extension_loaded('wincache')) {
            return false;
        }

        parent::__construct($options);
    }

    /**
     * Write data for key into cache
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

        $data = [ $key . '_expires' => $expires, $key => $value ];
        $result = wincache_ucache_set($data, null, $this->duration);
        return empty($result);
    }

    /**
     * @inheritDoc
     */
    public function read($key)
    {
        $key = $this->prefixedKey($key);

        $time = time();
        $cachetime = (int)wincache_ucache_get($key . '_expires');
        if ($cachetime < $time || ($time + $this->duration) < $cachetime) {
            return false;
        }
        return wincache_ucache_get($key);
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return wincache_ucache_inc($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->prefixedKey($key);

        return wincache_ucache_dec($key, $offset);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $key = $this->prefixedKey($key);

        return wincache_ucache_delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }
        $info = wincache_ucache_info();
        $cacheKeys = $info['ucache_entries'];
        unset($info);
        foreach ($cacheKeys as $key) {
            if (strpos($key['key_name'], $this->prefix) === 0) {
                wincache_ucache_delete($key['key_name']);
            }
        }
        return true;
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

        $groups = wincache_ucache_get($this->compiledGroupNames);
        if (count($groups) !== count($this->groups)) {
            foreach ($this->compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    wincache_ucache_set($group, 1);
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
        $success = null;
        wincache_ucache_inc($this->prefix . $group, 1, $success);
        return $success;
    }
}
