<?php

namespace Kaliba\Database\Query;


use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class GroupByExpression implements  SQLExpression
{
    use ExpressionTrait;

    /**
     * @var string|array
     */
    protected $column;

    /**
     * GroupByExpression constructor.
     * @param string|array $column
     */
    public function __construct($column)
    {
        $this->column = $column;
    }

    /**
     * Converts the expression to its string representation
     * @return string
     */
    public function getSql()
    {
        if(is_string($this->column)){
            return " GROUP BY {$this->column} ";
        }elseif (is_array($this->column)){
            $columns = implode(',', $this->column);
            return " GROUP BY {$columns} ";
        }
    }

}