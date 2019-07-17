<?php

namespace Kaliba\Database\Query;
use InvalidArgumentException;
use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class NullExpression implements SQLExpression
{

    use ExpressionTrait;

    /**
     * @var string
     */
    protected $column;


    /**
     * @var bool
     */
    protected $negate =  false;

    /**
     * BetweenExpression constructor.
     * @param string $column
     * @param array $values
     * @param null $negate
     * @param null $logical
     */
    public function __construct($column, $negate=null, $logical=null)
    {
        $this->column = $column;
        $this->negate = $negate?? FALSE;
        $this->logical = $logical;

    }

    /**
     * Converts the expression to its string representation
     * @return string
     */
    public function getSql()
    {
        $operator = $this->getOperator();
        $logical = $this->getLogical();
        return " {$logical} ( {$operator} {$this->column} )";
    }

    /**
     * Get the UNARY operator
     * @return string
     */
    protected function getOperator()
    {
        if($this->negate){
            return 'IS NOT NULL';
        }else{
            return 'IS NULL';
        }

    }
}