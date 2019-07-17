<?php

namespace Kaliba\Database\Query;


use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;

class JoinExpression implements SQLExpression
{
    use ExpressionTrait;

    /**
     * @var string
     */
    private $joinTable;

    /**
     * @var string
     */
    private $columnOne;

    /**
     * @var string
     */
    private $columnTwo;

    /**
     * @var string|int
     */
    private $joinType;

    /**
     * JoinExpression constructor.
     * @param string $joinTable
     * @param string $columnOne
     * @param string $columnTwo
     * @param $joinType
     */
    public function __construct(string $joinTable, string $columnOne, string $columnTwo, $joinType)
    {
        $this->joinTable = $joinTable;
        $this->columnOne = $columnOne;
        $this->columnTwo = $columnTwo;
        $this->joinType = $joinType;
    }

    /**
     * Converts the expression to its string representation
     * @return string
     */
    public function getSql()
    {
        $joinType = $this->getType();
        $sql = "{$joinType} {$this->joinTable} ON {$this->columnOne} = {$this->columnTwo}";
        return $sql;
    }

    /**
     * Get Join Type representation
     * @return string
     */
    private function getType()
    {
        if($this->joinType == SQLExpression::JOIN_INNER  || $this->joinType == 'INNER'){
            return 'INNER JOIN';
        }
        if($this->joinType == SQLExpression::JOIN_CROSS || $this->joinType == 'CROSS' ){
            return 'CROSS JOIN ';
        }
        if($this->joinType == SQLExpression::JOIN_FULL || $this->joinType == 'FULL'){
            return 'FULL JOIN';
        }
        if($this->joinType == SQLExpression::JOIN_NATURAL || $this->joinType == 'NATURAL'){
            return 'NATURAL JOIN';
        }
        if($this->joinType == SQLExpression::JOIN_RIGHT || $this->joinType == 'RIGHT'){
            return 'RIGHT JOIN';
        }
        if($this->joinType == SQLExpression::JOIN_LEFT || $this->joinType == 'LEFT'){
            return 'LEFT JOIN';
        }
    }


}