<?php
namespace Kaliba\Configure;
use Kaliba\Filesystem\FileManager;
use Kaliba\Support\Arraybag;

/**
 *
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration
 * for future use.
 *
 * This class is from CakePHP(tm) Framework
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * 
 */
class Config implements  \ArrayAccess
{
    /**
     * Array of values currently stored in Configure.
     *
     * @var array
     */
    private $values = [];
    
    /**
     * Loads stored configuration information from a resource
     * @param string $source name of configuration resource to load.
     * @param bool $merge if files should be merged instead of simply overridden
     */
    public function __construct($source, $merge=true) 
    {
        if(is_dir($source)){
            $this->loadFromDirectory($source);
        }
        elseif(is_file($source)){
            $this->loadFromFile($source, $merge);
        }
        
    }
    
    /**
     * Returns true if given variable is set in Configure.
     *
     * @param string $var Variable name to check for
     * @return bool True if variable is there
     */
    public function check($var) 
    {
        if (empty($var)) {
            return false;
        }
        return $this->get($var) !== null;
    }

    /**
     * Clear all values stored in Configure.
     *
     * @return bool success.
     */
    public function clear() 
    {
        $this->values = [];
        return true;
    }

    /**
     * Read and delete a variable from Configure.
     *
     * This is primarily used during bootstrapping to move configuration data
     * out of configure into the various other classes in CakePHP.
     *
     * @param string $var The key to read and remove.
     * @return array|null
     */
    public function consume($var) 
    {
        if (strpos($var, '.') === false) {
            if (!isset($this->values[$var])) {
                return null;
            }
            $value = $this->values[$var];
            unset($this->values[$var]);
            return $value;
        }
        $value = Arraybag::get($this->values, $var);
        $this->delete($var);
        return $value;
    }

    /**
     * Used to delete a variable from Configure.
     *
     * Usage:
     * ```
     * Config::delete('Name'); will delete the entire Config::Name
     * Config::delete('Name.key'); will delete only the Config::Name[key]
     * ```
     *
     * @param string $var the var to be deleted
     * @return void
     */
    public function delete($var) 
    {
        $this->values = Arraybag::remove($this->values, $var);
    }

    /**
     * Used to read information stored in Configure. It's not
     * possible to store `null` values in Configure.
     *
     * Usage:
     * ```
     * Config::get('Name'); will return all values for Name
     * Config::get('Name.key'); will return only the value of Config::Name[key]
     * ```
     *
     * @param string $var Variable to obtain. Use '.' to access array elements.
     * @return mixed value stored in configure, or null.
     * 
     */
    public function get($var = null) 
    {
        if ($var === null) {
            return $this->values;
        }
        return Arraybag::get($this->values, $var);
    }

    /**
     * Checks whether configurations are loaded
     * @return bool
     */
    public function isLoaded() 
    {
        return empty($this->values)? false:true;
    }
    
    /**
     * Used to store a dynamic variable in Config.
     *
     * Usage:
     * ```
     * Config::set('One.key1', 'value of the Config::One[key1]');
     * Config::set(['One.key1' => 'value of the Config::One[key1]']);
     * Config::set('One', [
     *     'key1' => 'value of the Config::One[key1]',
     *     'key2' => 'value of the Config::One[key2]'
     * ]);
     *
     * Configure::set([
     *     'One.key1' => 'value of the Config::One[key1]',
     *     'One.key2' => 'value of the Config::One[key2]'
     * ]);
     * ```
     *
     * @param string|array $key The key to write, can be a dot notation value.
     * Alternatively can be an array containing key(s) and value(s).
     * @param mixed $value Value to set for var
     * @return bool True if setting was successful
     */
    public function set($key, $value = null)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $name => $value) {
            $this->values = Arraybag::insert($this->values, $name, $value);
        }

        return true;
    }

    /**
     * Dump data currently in Configure into $source. The serialization format
     * is decided by the config engine. For example, if the
     * 'default' adapter is a PhpConfig, the generated file will be a PHP
     * configuration file loadable by the PhpConfig.
     *
     * ### Usage
     *
     * Given that the 'default' engine is an instance of PhpConfig.
     * Save all data in Configure to the file `my_config.php`:
     *
     * ```
     * Configure::write('my_config', 'default');
     * ```
     *
     * Save only the error handling configuration:
     *
     * ```
     * Configure::write('error', 'default', ['Error', 'Exception'];
     * ```
     *
     * @param string $source The identifier to create in the config adapter.
     *   This could be a filename or a cache key depending on the adapter being used.
     * @param array $data The name of the top-level keys you want to dump.
     *   This allows you save only some data stored in Configure.
     * @return bool success
     * @throws Exception if the adapter does not implement a `dump` method.
     */
    public function write($source, $data = array()) 
    {
        $ext = pathinfo($source, PATHINFO_EXTENSION);
        $engine = ParserFactory::create($ext, $source);
        $values = $$this->values;
        if (!empty($data) && is_array($data)) {
            $values = array_intersect_key($values, array_flip($data));
        }
        return (bool)$engine->write($values);
    }

    /**
     * checks if an element or key exists in the collection
     * @param type $element
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->check($key);
    }

    /**
     * gets an element from the collection
     * @param mixed
     * @param mixed  $default The default value if the parameter key does not exist
     * @return string|int|object
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * adds a new element to the collection
     * @param int|string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * removes an element from the collection
     * @param string|int|object
     * @return string|int|object
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }
    
    /**
     * Loads stored configuration information from a file. 
     *
     * Loaded configuration information will be merged with the current
     * runtime configuration.
     * @param string $file name of configuration file to load.
     * @param bool $merge if files should be merged instead of simply overridden
     * @return mixed false if file not found, void if load successful.
     */
    private function loadFromFile($file,$merge = true) 
    {  
        if($this->isLoaded()){
            return;
        }
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        $factory = new ParserFactory();
        $engine = $factory->create($fileExtension, $file);
        if(empty($engine)){
            return;
        }
        $isloaded = Arraybag::contains($this->values, $engine->read());  
        if(!$isloaded){
            $values = $engine->read(); 
        }
        if ($merge) {
            $values = Arraybag::merge($this->values, $values);
            $this->set($values);
        }       
        
    }
    
    /**
     * Loads stored configuration files from a directory. 
     *
     * Loaded configuration information will be merged with the current
     * runtime configuration.
     * @param string $source name of configuration resource to load.
     * @param bool $merge if files should be merged instead of simply overridden
     * @return mixed false if file not found, void if load successful.
     */
    private function loadFromDirectory($source,$merge = true) 
    {  
        $finder = new FileManager();
        $files = $finder->search($source);
        foreach ($files as $file){
            $fileName =  pathinfo($file, PATHINFO_FILENAME);
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            $factory = new ParserFactory();
            $engine = $factory->create($fileExtension, $file);
            if(!empty($engine)){
                $values = $engine->read();
                $this->set($fileName, $values);
            }
        }     
        
    }
    
   

}
