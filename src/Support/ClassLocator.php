<?php

namespace Kaliba\Support;

class ClassLocator
{
           
    /**
     * checks whether the given class is namespaced
     * @param string $class
     * @return boolean
     */
    private static function namespaced($class)
    {
        if (strpos($class, '\\')) {
            return true;
        }
        return false;
    }    
    
    /**
     * Normalises a class by building the complete class namespace
     * @param string $class Class name
     * @param string $namespace Class Namespace
     * @return string A normalized class
     */
    private static function normalize($class, $namespace='')
    {
        if(!empty($namespace)){
            return trim(back_slashes($namespace.'/'.$class), '\\');
        }else{
            return trim(back_slashes($class), '\\'); 
        }
    }
   
    /**
     * Gets full classname including namespace if class is namespaced. This method checks if the class is defined in the
     * Application namespace
     *
     * @param string $classname Class name
     * @param string $namespace Class type i.e if classname is Order you can specify class type as App\Models.
     * @return bool|string False if the class is not found or namespaced class name
     */
    public  static function locate($classname, $namespace='')
    {
        $normalized = static::normalize($classname, $namespace);
        if(static::namespaced($normalized)){
           return $normalized;
        }
        return $classname;
    }
    

}
