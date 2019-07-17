<?php


namespace Kaliba\Database\Query;


class ValueBinder
{
    /**
     * Array containing a list of bound values to the conditions on this
     * object. Each array entry is another array structure containing the actual
     * bound value, its type and the placeholder it is bound to.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * A counter of the number of parameters bound in this expression object
     *
     * @var int
     */
    protected $bindingsCount = 0;

    /**
     * Associates a query placeholder to a value and a type
     *
     * @param string|int $param placeholder to be replaced with quoted version
     * of $value
     * @param mixed $value The value to be bound
     * @param string|int $type the mapped type name, used for casting when sending
     * to database
     * @return void
     */
    public function bind($param, $value)
    {
        $this->bindings[$param] = $value;
    }

    /**
     * Creates a unique placeholder name if the token provided does not start with ":"
     * otherwise, it will return the same string and internally increment the number
     * of placeholders generated by this object.
     *
     * @param string $token string from which the placeholder will be derived from,
     * if it starts with a colon, then the same string is returned
     * @return string to be used as a placeholder in a query expression
     */
    public function placeholder($token='param')
    {
        if ($token[0] !== ':' && $token !== '?' && $token != 'param') {
            $token = sprintf(':%s', $token);
        }else{
            $number = $this->bindingsCount++;
            $token = sprintf(':%s%s', $token, $number);
        }
        return $token;
    }

    /**
     * Creates unique named placeholders for each of the passed values
     * and binds them with the specified type.
     *
     * @param array|\Traversable $values The list of values to be bound
     * @return array with the placeholders to insert in the query
     */
    public function placeholders($values)
    {
        $placeholders = [];
        foreach ($values as $key => $value) {
            $param = $this->placeholder($key);
            $this->bindings[$param] = $value;
            $placeholders[$key] = $param;
        }

        return $placeholders;
    }

    /**
     * Returns all values bound to this expression object at this nesting level.
     * Subexpression bound values will not be returned with this function.
     *
     * @return array
     */
    public function bindings()
    {
        return $this->bindings;
    }

    /**
     * Clears any bindings that were previously registered
     *
     * @return void
     */
    public function reset()
    {
        $this->bindings = [];
        $this->bindingsCount = 0;
    }

    /**
     * Resets the bindings count without clearing previously bound values
     *
     * @return void
     */
    public function resetCount()
    {
        $this->bindingsCount = 0;
    }
}