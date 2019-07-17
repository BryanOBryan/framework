<?php

namespace Kaliba\Database\Query;
use InvalidArgumentException;
use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class UnaryExpression implements SQLExpression
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
     * @var int
     */
    protected $type = self::UNARY_IN;

    /**
     * UnaryExpression constructor.
     * @param $string column
     * @param array $values
     * @param bool|null $negate
     * @param int|null $unaryType
     * @param int|null $logical
     */
    public function __construct($column, array $values, $negate=null, $unaryType=null, $logical=null)
    {
        $this->column = $column;
        $this->values = $values;
        $this->negate = $negate?? FALSE;
        $this->type = $unaryType?? SQLExpression::UNARY_IN;
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
        if(is_array($this->values)){
            $placeholders = [];
            foreach ($this->values as $value){
                $placeholders[] = $placeholder = $binder->placeholder();
                $binder->bind($placeholder,$value);
                $this->setBindings( $binder->bindings() );
                $implodes = implode(',', $placeholders);
                $sql = " {$logical} ( {$this->column} {$operator} ({$implodes}) ) ";
            }
            return $sql;
        }
        throw new InvalidArgumentException("IN OR EXISTS Expression expects an array of values");


    }

    /**
     * Get the UNARY operator
     * @return string
     */
    protected function getOperator()
    {
        $operator = '';
        if ($this->type == SQLExpression::UNARY_IN || $this->type == 'IN') {
            $operator = 'IN';
        }
        elseif($this->type == SQLExpression::UNARY_EXISTS || $this->type == 'EXISTS'){
            $operator = 'EXISTS';
        }

        if($this->negate){
            $operator = ' NOT '.$operator;
        }
        return $operator;
    }
}