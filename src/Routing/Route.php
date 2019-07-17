<?php

namespace Kaliba\Routing;

class Route
{

    /**
     *
     * @var mixed
     */
    protected $controller;
    
    /**
     *
     * @var string
     */
    protected $name;

    /**
     * Route constructor.
     * @param mixed $controller
     * @param mixed $name
     */
    public function __construct($controller, $name=null) 
    {
        $this->controller = $controller;
        $this->name = $name;
    }

    /**
     * 
     * @return mixed
     */
    public function getController() 
    {
        return $this->controller;
    }
    
    /**
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}