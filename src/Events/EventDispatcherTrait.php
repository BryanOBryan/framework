<?php

namespace Kaliba\Events;


trait EventDispatcherTrait
{
    /**
     *
     * @var EventDispatcher
     */
    private static $dispatcher;
    
    /**
     * 
     * @return EventDispatcherInterface
     */
    private function dispatcher()
    {
        if(self::$dispatcher == null){ 
            self::$dispatcher = new EventDispatcher();
        }
        return self::$dispatcher;
    }

    /**
     * Add an Event Listener to this dispatcher
     * @param EventListenerInterface $listener
     * @param int|null $priority
     * @return mixed
     */
    public function addListener( EventListenerInterface $listener, int $priority = null )
    {
        $this->dispatcher()->addListener($listener, $priority);
    }

    /**
     * Remove and Event Listener from this dispatcher
     * @param EventListenerInterface $listener
     * @return mixed
     */
    public function removeListener( EventListenerInterface $listener )
    {
        $this->dispatcher()->removeListener($listener);
    }

    /**
     * Dispatch event to the subscribed listeners
     * @param \Kaliba\Events\EventInterface $event
     */
    public function dispatch(EventInterface $event)
    {
        return $this->dispatcher()->dispatch($event);
    }

}
