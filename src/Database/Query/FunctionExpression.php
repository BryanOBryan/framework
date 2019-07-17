<?php
namespace Kaliba\Database\Query;

use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

/**
 * This class represents a function call string in a SQL statement. Calls can be
 * constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 */
class FunctionExpression implements SQLExpression
{
    use ExpressionTrait;

    /**
     * The name of the function to be constructed when generating the SQL string
     *
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $params;

    /**
     * FunctionExpression constructor.
     * Constructor. Takes a name for the function to be invoked and a list of params
     * to be passed into the function. Optionally you can pass a list of types to
     * be used for each bound param.
     * @param string $name
     * @param string|array $params
     */
    public function __construct($name, $params=NULL)
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * Returns the string representation of this object so that it can be used in a
     * SQL query.
     *
     * @return string
     */
    public function getSql()
    {
        $binder = $this->binder();
        $sql = null;
        if(empty($this->params)){
            $sql = " {$this->name}() ";
        }
        elseif (is_string($this->params)){
            $sql = " {$this->name}({$this->params}) ";
        }
        elseif(is_array($this->params)){
            $params = implode(',', $this->params);
            $sql = " {$this->name}({$params}) ";
        }

        return $sql;
    }

}
