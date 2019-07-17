<?php

namespace Kaliba\Support;

class Autoloader
{
    
    /**
     *paths to search and load class from
     * @var array
     */
    protected $paths = [];
    
    /**
     *
     * @var string
     */
    protected  $extension = '.php';

    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = [];
    
    /**
     * @inherit
     */
    public function addPath($path)
    {
        $this->addPaths((array)$path);
    }
    
    /**
     * @inherit
     */
    public function addPaths(array $paths)
    {
        if ($this->paths) {
            $this->paths = array_merge($this->paths, $paths);
        } else {
            $this->paths = $paths;
        }
    }
    
    /**
     * @inherit
     */
    public function addPsr4($prefix, $baseDir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        
        $baseDir = add_slash($baseDir);

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }
    }
   
    /**
     * @inherit
     */
    protected function addExtension($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if( is_null($ext) || ($ext != $this->extension) ){
            $file = $file.$this->extension;
        }
        return $file;
    }  
    
    /**
     * @inherit
     */
    public function load($class)
    {
        if(isset($this->paths)){
            foreach($this->paths as $path){
                if(is_file($path) && file_exists($path)){
                    require_once(real_path($path));
                }               
                $filename = $path .DIRECTORY_SEPARATOR.$class;
                $filename = real_path($this->addExtension($filename));
                if(file_exists($filename)){
                    require_once $filename;
                }               			
            }  
        }
        
        $prefix = $class;
        while (($pos = strrpos($prefix, '\\')) !== false) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            $this->loadMappedFile($prefix, $relativeClass);
            $prefix = rtrim($prefix, '\\');
        }    
    }
  
    /**
     * @inherit
     */
    public function register()
    {
        spl_autoload_register(array($this, 'load'));
    }
    
    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relativeClass The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix])) {
        
            foreach ($this->prefixes[$prefix] as $baseDir) {
                $file = $baseDir . forward_slashes($relativeClass) ;
                $file = real_path($this->addExtension($file)); 
                if (file_exists($file)) {
                    require_once $file;
                }   
            }
        
        }
    }
  
}
  
  
  



