<?php

namespace Kaliba\Robas\Restrict;

interface Policy
{
    
    /**
     * Evaluate user access on resources
     * @param mixed $data
     * @return bool
     */
    public function evaluate($data = null);
    
}