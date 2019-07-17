<?php

namespace Kaliba\Routing;

interface Rule
{
    /**
     * 
     * @param string $route
     * @return Route
     */
    public function find($route);
}
