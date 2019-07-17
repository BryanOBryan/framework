<?php

namespace Kaliba\Events;
use SplSubject;
use Spllistener;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     *
     * @var array
     */
    protected $linkedList = array();
    
    /**
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Add an Event Listener to this dispatcher
     * @param EventListenerInterface $listener
     * @param int|null $priority
     * @return mixed
     */
    public function addListener( EventListenerInterface $listener, int $priority = null )
    {
        $listenerKey = spl_object_hash($listener);
        if(!array_key_exists($listenerKey, $this->listeners)){
            $this->listeners[$listenerKey] = $listener;
            $this->linkedList[$listenerKey] = $priority;
            arsort($this->linkedList);
        }
    }

    /**
     * Remove and Event Listener from this dispatcher
     * @param EventListenerInterface $listener
     * @return mixed
     */
    public function removeListener( EventListenerInterface $listener )
    {
        $listenerKey = spl_object_hash($listener);
        if(array_key_exists($listenerKey, $this->listeners)){
            unset($this->listeners[$listenerKey]);
            unset($this->linkedList[$listenerKey]);
        }
    }

    /**
     * Dispatch event to the subscribed listeners
     * @param EventInterface $event
     */
    public function dispatch(EventInterface $event)
    {
        if(!empty($this->listeners)){
            foreach ($this->linkedList as $key => $value){
                $listener = $this->listeners[$key];
                if($listener instanceof EventListenerInterface){
                    $listener->listen($event);
                }
            }
        }
    }

}
