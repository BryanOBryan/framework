<?php


namespace Kaliba\Events;


use SplObserver;

interface EventDispatcherInterface
{
    /**
     * Add an Event Listener to this dispatcher
     * @param EventListenerInterface $listener
     * @param int|null $priority
     * @return mixed
     */
    public function addListener(EventListenerInterface $listener, int $priority=null);

    /**
     * Remove and Event Listener from this dispatcher
     * @param EventListenerInterface $listener
     * @return mixed
     */
    public function removeListener(EventListenerInterface $listener);

    /**
     * Dispatch Event to the subscribed Listeners
     * @param EventInterface $event
     * @return mixed
     */
    public function dispatch(EventInterface $event);


}