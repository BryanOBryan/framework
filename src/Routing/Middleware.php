<?php

namespace Kaliba\Routing;
use Kaliba\Foundation\Application;
use Kaliba\Support\Dice;

class Middleware
{
    /**
     *
     * @var Application
     */
    private $app;

    /**
     * Kernel constructor.
     * @param Dice $dice
     * @param array $middleware
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
    }
    
    /**
     * Get global middleware objects
     * @return array Array of Middle ware Objects
     */
    public function universal()
    {
        $objects = [];
        if($global = $this->app->getMiddleware('global') ){
            foreach ($global as $middleware) {
                $objects[] = $this->create($middleware);
            }
        }
        return $objects;
    }
    
    /**
     * Get Route middle ware objects
     * @param string $route route name
     * @return array Array of Middle ware objects
     */
    public function route($route)
    {
        $objects = [];
        if($global = $this->app->getMiddleware('route')[$route] ){
            foreach ($global as $middleware) {
                $objects[] = $this->create($middleware);
            }
        }
        
        return $objects;
    }

    /**
     * @param string $middleware
     * @return mixed
     */
    private function create($middleware)
    {
        return $this->app->make($middleware);
    }


}
