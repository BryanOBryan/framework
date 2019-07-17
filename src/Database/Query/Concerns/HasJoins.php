<?php

namespace Kaliba\Database\Query\Concerns;
use Kaliba\Database\Query\JoinExpression;
use Kaliba\Database\Contracts\SQLExpression;


trait HasJoins
{
    
    /**
     * Adds a single or multiple tables to be used as JOIN clauses to this query.
     * Tables can be passed as an array of strings
     *
     * By default this function will append any passed argument to the list of tables
     * to be joined.
     *
     * When no join type is specified an INNER JOIN is used by default:

     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @param string $type JOIN type. This method supports several joins 
     * (INNER JOIN, LEFT JOIN, RIGHT JOIN, CROSS JOIN, FULL JOIN, NATURAL JOIN)
     * @return $this
     */
    public function join($table, $columnA, $columnB, $type = SQLExpression::JOIN_INNER)
    {
        $tbl = $this->identify($table);
        $colA = $this->identify($columnA);
        $colB = $this->identify($columnB);
        $expression = new JoinExpression($tbl, $colA, $colB, $type);
        $this->addJoin($expression);
        return $this;
        
    }

    /**
     * Adds a single CROSS JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @return $this
     */
    public function crossJoin($table, $columnA, $columnB)
    {       
        return $this->join($table, $columnA, $columnB, SQLExpression::JOIN_CROSS);
    }
	
    /**
     * Adds a single FULL JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @return $this
     */
    public function fullJoin($table, $columnA, $columnB)
    {
        return $this->join($table, $columnA, $columnB, SQLExpression::JOIN_FULL);
    }
	
    /**
     * Adds a single NATURAL JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @return $this
     */
    public function naturalJoin($table, $columnA, $columnB)
    {
        return $this->join($table, $columnA, $columnB, SQLExpression::JOIN_NATURAL);
    }
    
    /**
     * Adds a single LEFT JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @return $this
     */
    public function leftJoin($table, $columnA, $columnB)
    {
        return $this->join($table, $columnA, $columnB, SQLExpression::JOIN_LEFT);
    }
    
    /**
     * Adds a single RIGHT JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $table table to be joined in the query
     * @param string $columnA table A column
     * @param string $columnB table B column
     * @return $this
     */
    public function rightJoin($table, $columnA, $columnB)
    {
        return $this->join($table, $columnA, $columnB, SQLExpression::JOIN_RIGHT);
    }

}