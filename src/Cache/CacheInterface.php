<?php

namespace Kaliba\Cache;

interface CacheInterface
{
    /**
     * Write data for many keys into cache
     *
     * @param array $data An array of data to be stored in the cache
     * @return array of bools for each key provided, true if the data was successfully cached, false on failure
     */
    public function writeMany($data);
   
    /**
     * Read multiple keys from the cache
     *
     * @param array $keys An array of identifiers for the data
     * @return array For each cache key (given as the array key) the cache data associated or false if the data doesn't
     * exist, has expired, or if there was an error fetching it
     */
    public function readMany($keys);
    
    /**
     * Deletes keys from the cache
     *
     * @param array $keys An array of identifiers for the data
     * @return array For each provided cache key (given back as the array key) true if the value was successfully deleted,
     * false if it didn't exist or couldn't be removed
     */
    public function deleteMany($keys);
    
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
    public function add($key, $value);
       
    /**
     * Clears all values belonging to a group. Is up to the implementing engine
     * to decide whether actually delete the keys or just simulate it to achieve
     * the same result.
     *
     * @param string $group name of the group to be cleared
     * @return bool
     */
    public function clearGroup($group);
   
    /**
     * Does whatever initialization for each group is required
     * and returns the `group value` for each of them, this is
     * the token representing each group in the cache key
     *
     * @return array
     */
    public function groups();
        
    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null);  
        
    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public function read($key);
    
    /**
     * Write value for a key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param int $duration Time to live in seconds, default is 3600 seconds = 1 hour
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $value, $duration = 3600);
    
    /**
     * Increment a number under the key and return incremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @return bool|int New incremented value, false otherwise
     */
    public function increment($key, $offset = 1);

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New incremented value, false otherwise
     */
    public function decrement($key, $offset = 1);

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function delete($key);

    /**
     * Delete all keys from the cache
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check);
    
    /**
     * Checks if a key exists in the cache and is set
     * @param string $key
     * @return boolean
     */
    public function check($key);
}
