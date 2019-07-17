<?php

namespace Kaliba\Database\Query\Concerns;
use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\ValueBinder;

trait ExpressionTrait
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var int
     */
    protected $logical;

    /**
     * Returns all values bound to this expression object at this nesting level.
     * Subexpression bound values will not be returned with this function.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Set all values bound to this expression object at this nesting level
     * @param array $bindings
     */
    public function setBindings(array $bindings)
    {
       $this->bindings = array_merge($this->bindings, $bindings);
    }

    /** Check whether the query objec has bindings
     * @return bool
     */
    public function hasBindings()
    {
        return !empty($this->bindings)?true:false;
    }

    /**
     * Get an instance of ValueBinder
     * @return ValueBinder
     */
    protected function binder()
    {
        return new ValueBinder();
    }

    /**
     * Get the logical operator
     * @return string
     */
    protected function getLogical()
    {
        if($this->logical == SQLExpression::LOGIC_AND || $this->logical == 'AND'){
            return 'AND';
        }
        if($this->logical == SQLExpression::LOGIC_OR || $this->logical == 'OR' ){
            return 'OR';
        }
        if($this->logical == SQLExpression::LOGIC_NOT || $this->logical == 'NOT' || $this->logical == 'XOR'){
            return 'NOT';
        }
    }

}