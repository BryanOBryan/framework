<?php
namespace Kaliba\Routing;
use Kaliba\Foundation\Application;
use Kaliba\Support\ClassLocator;
use Kaliba\Support\Inflector;
use Kaliba\Support\Dice;

class Conversion implements Rule 
{
    /**
     * @var Application
     */
    private $app;

    /**
     * Default route
     * @var string
     */
    private $default = 'Index';

    /**
     *
     * @var string
     */
    private $postfix = 'Controller';

    /**
     * Conversion constructor.
     * @param string $namespace
     */
    public function __construct( Application $app)
    {
        $this->app = $app;
    }

    /**
     * Find the route object
     * @param string $route
     * @return Route
     */
    public function find($route) 
    {
        $name = Inflector::camelize($route);
        $class = $this->findClass($name);
        if(class_exists($class)){
            $controller = $this->app->make($class);
            return $this->app->make(Route::class, [$controller, $name]);
        }     
    }

    /**
     * @param $route
     * @return bool|string
     */
    private function findClass($route)
    {
        if(empty($route)){
            $route = $this->default;
        }
        $class = $this->getClass($route);
        $package = $this->getPackage($route);
        $className = $this->locate($package, $class);
        if(!class_exists($className)){
            $className = $this->locate($package, $class.$this->postfix);
        }
        if(!class_exists($className)){
            $className = $this->locate($package.'\\'.$route, $this->default);
        }
        if(!class_exists($className)){
            $className = $this->locate($package.'\\'.$route, $this->default.$this->postfix);
        }
        return $className;
    }

    /**
     * Extract class name from the route
     * @param string $route
     * @return string
     */
    private function getClass($route)
    {
        $class = basename($route);
        return ucfirst($class);
    }

    /** Extract package name from the route
     * @param string $route
     * @return string
     */
    private function getPackage($route)
    {
        $package = back_slashes(dirname($route));
        if($package === '.'){
            return $this->app->getNamespace();
        }else{
            return $this->app->getNamespace().'\\'.ucfirst($package);
        }
    }

    /**
     * Find the complete class name
     * @param string $package
     * @param string $class
     * @return string
     */
    private function locate($package, $class)
    {
        return ClassLocator::locate($class, $package);
    }


}