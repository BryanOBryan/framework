<?php
namespace Kaliba\Routing;

use Kaliba\Http\Exception\NotFoundException;

class Router
{

    /**
     *
     * @var array
     */
    private $rules = [];

    /**
     * Add rule to the router
     * @param Rule $rule
     * @return self
     */
    public function addRule(Rule $rule) 
    {
        $this->rules[] = $rule;
        return $this;
    }
    
    /**
     * 
     * @param string Route Path
     * @return Route
     * @throws \Exception
     */
    public function getRoute($path)
    {
        foreach ($this->rules as $rule) {  
            $route = $rule->find($path);
            if(!empty($route)){
                return $route;
            }  
        }
        throw new NotFoundException();

    }

}