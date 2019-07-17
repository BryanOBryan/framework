<?php


namespace Kaliba\Events;


interface EventListenerInterface
{
    /**
     * Listen to Event
     * @param EventInterface $event
     * @return mixed
     */
    public function listen(EventInterface $event);
}