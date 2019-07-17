<?php

namespace Kaliba\Database\Query;
use InvalidArgumentException;
use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class BetweenExpression implements SQLExpression
{
    use ExpressionTrait;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var array
     */
    protected $values;

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
    public function __construct($column, array $values, $negate=null, $logical=null)
    {
        $this->column = $column;
        $this->values = $values;
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
        $binder = $this->binder();
        $sql = '';
        if(count($this->values) == 2){
            $from = $binder->placeholder('from');
            $to = $binder->placeholder('to');
            $binder->bind($from, array_shift($this->values));
            $binder->bind($to, array_shift($this->values));
            $this->setBindings( $binder->bindings() );
            $sql =  " {$logical} ( {$this->column} {$operator} $from AND $to ) ";
            return $sql;
        }
        throw new InvalidArgumentException("BETWEEN Expression expects an array of 2 values");

    }

    /**
     * Get the UNARY operator
     * @return string
     */
    protected function getOperator()
    {
        if($this->negate){
            return 'NOT BETWEEN';
        }else{
            return 'BETWEEN';
        }

    }

}