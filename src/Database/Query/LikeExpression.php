<?php

namespace Kaliba\Database\Query;

use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class LikeExpression implements  SQLExpression
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
     * @var bool
     */
    protected $negate =  false;

    /**
     * LikeExpression constructor.
     * @param string|array $column
     * @param null $value
     * @param bool|null $negate
     * @param int|null $logical
     */
    public function __construct($column, $value=null, $negate=null, $logical=null)
    {
        if(is_array($column) && func_num_args() == 2 ){
            $this->column = $column;
            $this->logical = $value;
        }
        elseif(is_array($column) && func_num_args() == 3){
            $this->column = $column;
            $this->negate = $value;
            $this->logical = $negate;
        }
        else{
            $this->column = $column;
            $this->value = $value;
            $this->negate = $negate;
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
            $binder->bind($placeholder,"%{$this->value}%");
            $this->setBindings( $binder->bindings() );
            return " {$logical} ( {$this->column} {$operator} {$placeholder} ) ";
        }
        elseif(is_array($this->column)){
            $sql = [];
            foreach ($this->column as $column => $value){
                $placeholder = $binder->placeholder($column);
                $binder->bind($placeholder,"%{$value}%");
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
     * Get the LIKE operator
     * @return string
     */
    protected function getOperator()
    {
        if ($this->negate) {
            return 'NOT LIKE';
        }else{
            return 'LIKE';
        }

    }
}