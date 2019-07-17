<?php

namespace Kaliba\Database\Query\Concerns;
use Kaliba\Database\Query\FunctionExpression;

trait HasFunctions
{

    /**
     * Returns SQL SUM function.
     * @param string $name Function name
     * @param mixed $expression the function argument
     * @return $this
     */
    public function func($name, $expression=null)
    {
        $exp = new FunctionExpression($name, $expression);
        $this->addFunction($exp);
        return $this;
    }

    /**
     * Returns SQL SUM function.
     *
     * @param mixed $expression the function argument
     * @return string
     */
    public function sum($expression)
    {
        return $this->func('SUM', $expression);
    }

    /**
     * Returns  SQL AVG function.
     *
     * @param mixed $expression the function argument
     * @return string
     */
    public function avg($expression)
    {
        return $this->func('AVG', $expression);
    }

    /**
     * Returns SQL MAX function.
     *
     * @param mixed $expression the function argument
     * @return string
     */
    public function max($expression)
    {
        return $this->func('MAX', $expression);
    }

    /**
     * Returns  SQL MIN function.
     *
     * @param mixed $expression the function argument
     * @return string
     */
    public function min($expression)
    {
        return $this->func('MIN', $expression);
    }

    /**
     * Returns SQL COUNT function.
     *
     * @param mixed $expression the function argument
     * @return string
     */
    public function count($expression)
    {
        return $this->func('COUNT', $expression);
    }

  

}