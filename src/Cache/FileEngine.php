<?php

namespace Kaliba\Cache;
use Kaliba\Support\Inflector;
use Exception;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplFileObject;

/**
 * File Storage engine for cache. Filestorage is the slowest cache storage
 * to read and write. However, it is good for servers that don't have other storage
 * engine available, or have content which is not performance sensitive.
 *
 * You can configure a FileCache cache, using Cache::config()
 *
 */
class FileEngine extends CacheEngine
{
    /**
     *
     * @var bool
     */
    protected $lock = true;
    
    /**
     *
     * @var int
     */
    protected $mask = 0664;
    
    /**
     *
     * @var bool
     */
    protected $serialize = true;
    
    /**
     *
     * @var string
     */
    protected $storage;
    
    /**
     * Instance of SplFileObject class
     *
     * @var \SplFileObject
     */
    protected $File = null;

    /**
     * True unless FileDriver::active(); fails
     *
     * @var bool
     */
    protected $init = true;

    /**
     * Initialize the cache engine
     *
     *
     * @param array $options A property Object containing associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {
       
        parent::__construct($options);
        $this->init($options);
        return $this->active();
    }
    
    protected function init(array $options)
    {
        $this->lock = $options['lock']??null;
        $this->mask = $options['mask']??null;
        $this->serialize = $options['serialize']??null;
        $this->storage = $options['storage']??null;
        if (empty($this->storage) ) {
            $this->storage = add_slash(forward_slashes(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kaliba'));
        }
        elseif (!is_dir($this->storage)) {
            mkdir($this->storage);
        }
        if (!empty($this->groupPrefix)) {
            $this->groupPrefix = str_replace('_', DIRECTORY_SEPARATOR, $this->groupPrefix);
        }
    }

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null)
    {
        return $this->clear(true);
    }

    /**
     * Write value for a key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param int $duration Time to live in seconds, default is 3600 seconds = 1 hour
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $data, $duration=null)
    {
        
        $this->setSharedDuration($duration);
        $key = $this->prefixedKey($key);
        if ($data === '' || !$this->init) {
            return false;
        }
        if ($this->setKey($key, true) === false) {
            return false;
        }
        $lineBreak = "\n";

        if ( is_windows() ) {
            $lineBreak = "\r\n";
        }
        if (!empty($this->serialize)) {
            if ( is_windows() ) {
                $data = str_replace('\\', '\\\\\\\\', serialize($data));
            } else {
                $data = serialize($data);
            }
        }
        $expires = $this->getExperyTime();
        $contents = $expires . $lineBreak . $data . $lineBreak;

        if ($this->lock) {
            $this->File->flock(LOCK_EX);
        }
        $this->File->rewind();
        $success = $this->File->ftruncate(0) && $this->File->fwrite($contents) && $this->File->fflush();
        if ($this->lock) {
            $this->File->flock(LOCK_UN);
        }
        $this->File = null;

        return $success;
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

        if (!$this->init || $this->setKey($key) === false) {
            return false;
        }

        if ($this->lock) {
            $this->File->flock(LOCK_SH);
        }

        $this->File->rewind();
        $time = time();
        $cachetime = (int)$this->File->current();
        if ($cachetime !== false &&
            ($cachetime < $time || ($time + $this->duration) < $cachetime)
        ) {
            if ($this->lock) {
                $this->File->flock(LOCK_UN);
            }
            return false;
        }
        $data = '';
        $this->File->next();
        while ($this->File->valid()) {
            $data .= $this->File->current();
            $this->File->next();
        }
        if ($this->lock) {
            $this->File->flock(LOCK_UN);
        }
        $data = trim($data);
        if ($data !== '' && !empty($this->serialize)) {
            if ( is_windows() ) {
                $data = str_replace('\\\\\\\\', '\\', $data);
            }
            $data = unserialize((string)$data);
        }
        return $data;
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
        if ($this->setKey($key) === false || !$this->init) {
            return false;
        }
        $storage = $this->File->getRealPath();
        $this->File = null;
        return @unlink($storage);
    }

    /**
     * Checks if a key exists in the cache and is set
     * @param string $key
     * @return boolean
     */
    public function clear($check)
    {
        if (!$this->init) {
            return false;
        }
        $this->File = null;

        $threshold = $now = false;
        if ($check) {
            $now = time();
            $threshold = $now - $this->duration;
        }

        $this->clearDirectory($this->storage, $now, $threshold);

        $directory = new RecursiveDirectoryIterator($this->storage);
        $contents = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::SELF_FIRST
        );
        $cleared = [];
        foreach ($contents as $storage) {
            if ($storage->isFile()) {
                continue;
            }

            $storage = $storage->getRealPath() . DIRECTORY_SEPARATOR;
            if (!in_array($storage, $cleared)) {
                $this->clearDirectory($storage, $now, $threshold);
                $cleared[] = $storage;
            }
        }
        return true;
    }

    /**
     * Delete everything from a directory
     * @param string $storage
     * @param int $now
     * @param int $threshold
     * @return bool
     */
    protected function clearDirectory($storage, $now, $threshold)
    {
        if (!is_dir($storage)) {
            return;
        }
        $prefixLength = strlen($this->prefix);

        $dir = dir($storage);
        while (($entry = $dir->read()) !== false) {
            if (substr($entry, 0, $prefixLength) !== $this->prefix) {
                continue;
            }

            try {
                $file = new SplFileObject($storage . $entry, 'r');
            } catch (Exception $e) {
                continue;
            }

            if ($threshold) {
                $mtime = $file->getMTime();
                if ($mtime > $threshold) {
                    continue;
                }

                $expires = (int)$file->current();
                if ($expires > $now) {
                    continue;
                }
            }
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $file = null;
                @unlink($filePath);
            }
        }
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
        throw new LogicException('Files cannot be atomically decremented.');
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
        throw new LogicException('Files cannot be atomically incremented.');
    }

    /**
     * Generate a key and return it
     * @param string $key
     * @return boolean
     */
    protected function setKey($key, $createKey = false)
    {
        
        $groups = null;
        if (!empty($this->groupPrefix)) {
            $groups = vsprintf($this->groupPrefix, $this->groups());
        }
        $dir = $this->storage . $groups;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $storage = new SplFileInfo($dir . $key);

        if (!$createKey && !$storage->isFile()) {
            return false;
        }
        if (empty($this->File) || $this->File->getBasename() !== $key) {
            $exists = file_exists($storage->getPathname());
            try {
                $this->File = $storage->openFile('c+');
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                return false;
            }
            unset($storage);

            if (!$exists && !chmod($this->File->getPathname(), (int)$this->mask)) {
                trigger_error(sprintf(
                    'Could not apply permission mask "%s" on cache file "%s"',
                    $this->File->getPathname(),
                    $this->mask
                ), E_USER_WARNING);
            }
        }
        return true;
    }

    /**
     * Active the file for writing
     * @return bool 
     */
    protected function active()
    {
        $dir = new SplFileInfo($this->storage);
        $storage = $dir->getPathname();
        if (!is_dir($storage)) {
            mkdir($storage, 0775, true);
        }

        if ($this->init && !($dir->isDir() && $dir->isWritable())) {
            $this->init = false;
            trigger_error(sprintf(
                '%s is not writable',
                $this->storage
            ), E_USER_WARNING);
            return false;
        }
        return true;
    }

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
        $key = Inflector::underscore(str_replace(
            [DIRECTORY_SEPARATOR, '/', '.', '<', '>', '?', ':', '|', '*', '"'],
            '_',
            strval($key)
        ));
        return $key;
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
        $this->File = null;
        $directoryIterator = new RecursiveDirectoryIterator($this->storage);
        $contents = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($contents as $object) {
            $containsGroup = strpos($object->getPathname(), DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR) !== false;
            $hasPrefix = true;
            if (strlen($this->prefix) !== 0) {
                $hasPrefix = strpos($object->getBasename(), $this->prefix) === 0;
            }
            if ($object->isFile() && $containsGroup && $hasPrefix) {
                $storage = $object->getPathname();
                $object = null;
                @unlink($storage);
            }
        }
        return true;
    }

    
}
