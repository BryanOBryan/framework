<?php


namespace Kaliba\Database\Query;

use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class OrderByExpression implements SQLExpression
{
    use ExpressionTrait;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $orderType;

    /**
     * OrderByExpression constructor.
     * @param string $column
     * @param string $orderType
     */
    public function __construct($column, $orderType='ASC')
    {
        $this->column = $column;
        $this->orderType = $orderType;
    }

    /**
     * Converts the expression to its string representation
     * @return string
     */
    public function getSql()
    {
        if(is_string($this->column)){
            return " ORDER BY {$this->column} {$this->orderType} ";
        }elseif (is_array($this->column)){
            $columns = implode(',', $this->column);
            return " ORDER BY {$columns} {$this->orderType} ";
        }
    }


}