<?php
namespace Kaliba\Events;

interface EventInterface 
{
    /**
     * Get a single value or a list of values from the event data 
     * @return array
     */
    public function getData();
	
    /**
     * Returns the name of this event. This is usually used as the event identifier
     * @return string
     */
    public function getName();
    
}
