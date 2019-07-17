<?php

namespace Kaliba\Database\Query;

use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class GeneralExpression implements SQLExpression
{
    use ExpressionTrait;

    /**
     * @var string|array
     */
    protected $column;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string|int
     */
    protected $operator;

    /**
     * WhereExpression constructor.
     * @param string|array $column
     * @param null $value
     * @param int|string|null $operator
     * @param int|string|null $logical
     */
    public function __construct($column, $value=null, $operator=null, $logical=null)
    {
        if(is_array($column) && func_num_args() == 2 ){
            $this->column = $column;
            $this->logical = $value;
            $this->operator = SQLExpression::FIND_EXACT;
        }
        elseif(is_array($column) && func_num_args() == 3){
            $this->column = $column;
            $this->operator = $value;
            $this->logical = $operator;
        }
        else{
            $this->column = $column;
            $this->value = $value;
            $this->operator = $operator?? SQLExpression::FIND_EXACT;
            $this->logical = $logical;
        }
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
        if(is_string($this->column)){
            $placeholder = $binder->placeholder($this->column);
            $binder->bind($placeholder,$this->value);
            $this->setBindings( $binder->bindings() );
            return " {$logical} ( {$this->column} {$operator} {$placeholder} ) ";
        }
        elseif(is_array($this->column)){
            $sql = [];
            foreach ($this->column as $column => $value){
                $placeholder = $binder->placeholder($column);
                $binder->bind($placeholder,$value);
                $this->setBindings( $binder->bindings() );
                $sql[] = " ( {$column} {$operator} {$placeholder} ) ";
            }
            if($logical){
                return implode($logical, $sql);
            }else{
                return implode('AND', $sql);
            }
        }

    }

    /**
     * Get the arithmetic operator
     * @return string
     */
    protected function getOperator()
    {
        if ($this->operator == SQLExpression::FIND_EXACT || $this->operator == '=' ) {
            return '=';
        }
        if ($this->operator == SQLExpression::FIND_NOT || $this->operator == '!='){
            return '!=';
        }
        if ($this->operator == SQLExpression::FIND_GREATER || $this->operator == '>') {
            return '>';
        }
        if ($this->operator == SQLExpression::FIND_LESS || $this->operator == '<') {
            return '<';
        }
        if ($this->operator == SQLExpression::FIND_GEQ || $this->operator == '>=') {
            return '>=';
        }
        if ($this->operator == SQLExpression::FIND_LEQ || $this->operator == '<=') {
            return '<=';
        }

    }


}
