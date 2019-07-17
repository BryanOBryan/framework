<?php

namespace Kaliba\Events;

class Event implements EventInterface
{
    
    /**
     * Name of the event
     *
     * @var string
     */
    private $name;
    
    /**
     *
     * @var array
     */
    private $vars = [];
    
    /**
     * Constructor
     *
     *
     * @param string $name Name of the event
     * @param array $vars any value you wish to be transported with this event so that it can be read by listeners
     */
    public function __construct($name, array $vars = [])
    {
        $this->name = $name;
        $this->vars = $vars;

    }
    
    /**
     * Get a single value or a list of values from the event data 
     * @return array
     */
    public function getData()
    {
        return $this->vars;
    
    }
	
    /**
     * Returns the name of this event. This is usually used as the event identifier
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name;
    }

}
