<?php
namespace Kaliba\Database\Query\Concerns;
use Kaliba\Database\Query\BetweenExpression;
use Kaliba\Database\Query\GeneralExpression;
use Kaliba\Database\Query\LikeExpression;
use Kaliba\Database\Query\NullExpression;
use Kaliba\Database\Query\UnaryExpression;
use Kaliba\Database\Contracts\SQLExpression;

trait HasConditions
{
    /**
     * Adds a where condition or to be used in the WHERE clause for this
     * query. The condition contains a table column, operator and value
     * if only column is provided, the whole condition is evaluated as a single string
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to compare with column value in the where condition
     * @param string $operator Comparison operator or Set operator.Supported operators: =, !=, <>, <, <=, >, >=
     * @param string|int $logical Logical operator
     * 
     * @return $this
     */
    public function where($column, $value=null, $operator = null, $logical=null)
    {
        $expression = new GeneralExpression($column,$value,$operator, $logical);
	    $this->addCondition($expression);
	    return $this;
    }
   
    /**
     * Adds Logical operator AND to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column, operator and value
     * if only column is provided, the whole condition is evaluated as a single string
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to compare with column value in the where condition
     * @param string $operator Comparison operator or Set operator. 
	 * Supported operators: =, !=, <>, <, <=, >, >= 
     * 
     * @return $this
     */
    public function andWhere($column, $value=null, $operator=null)
    {
        return $this->where($column,$value,$operator, SQLExpression::LOGIC_AND);
    }

    /**
     * Adds Logical operator AND to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column, operator and value
     * if only column is provided, the whole condition is evaluated as a single string
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to compare with column value in the where condition
     *
     * @return $this
     */
    public function andNot($column, $value=null)
    {
        return $this->where($column,$value,SQLExpression::FIND_NOT, SQLExpression::LOGIC_AND);
    }
    
    /**
     * Adds Logical operator OR to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column, operator and value
     * if only column is provided, the whole condition is evaluated as a single string
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to compare with column value in the where condition
     * @param string $operator Comparison operator or Set operator. 
     * Supported operators: =, !=, <>, <, <=, >, >= 
     * 
     * @return $this
     */
    public function orWhere($column, $value=null, $operator=null)
    {
        return $this->where($column,$value,$operator, SQLExpression::LOGIC_OR);
    }

    /**
     * Adds Logical operator OR to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column, operator and value
     * if only column is provided, the whole condition is evaluated as a single string
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to compare with column value in the where condition
     * @param string $operator Comparison operator or Set operator.
     * Supported operators: =, !=, <>, <, <=, >, >=
     *
     * @return $this
     */
    public function orNot($column, $value=null)
    {
        return $this->where($column,$value,SQLExpression::FIND_NOT, SQLExpression::LOGIC_OR);
    }
     
    /**
     * Adds IN clause to the WHERE clause to the query. It checks if a column value exists in a set of values
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @param bool $negate Negates the expression
     * @param string|int $logical Logical operator
     * @return $this
     */
    public function whereIn($column, array $values, $negate=false, $logical=null)
    {
        $expression = new UnaryExpression($column, $values, $negate, SQLExpression::UNARY_IN, $logical);
        $this->addCondition($expression);
        return $this;
    }
    
    /**
     * Adds NOT IN clause to the WHERE clause to the query. It checks if a column value does not exist in a set of values
     * 
     * This is a shorthand method for building joins via `where()`.
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function whereNotIn($column, array $values)
    {
        return $this->whereIn($column, $values, TRUE);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the AND operator in the IN clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function andIn($column, array $values)
    {
        return $this->whereIn($column, $values, FALSE, SQLExpression::LOGIC_AND);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the AND operator in the NOT IN clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function andNotIn($column, array $values)
    {
        return $this->whereIn($column, $values, TRUE, SQLExpression::LOGIC_AND);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the OR operator in the IN clause
     * 
     * This is a shorthand method for building joins via `orWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function orIn($column, array $values)
    {
        return $this->whereIn($column, $values, FALSE, SQLExpression::LOGIC_OR);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the OR operator in the NOT IN clause
     * 
     * This is a shorthand method for building joins via `orWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function orNotIn($column, array $values)
    {
        return $this->whereIn($column, $values, TRUE, SQLExpression::LOGIC_OR);
    }
    
    /**
     * Adds EXISTS clause to the WHERE clause to the query.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @param bool $negate Negates the expression
     * @param string|int $logical Logical operator
     * @return $this
     */
    public function whereExists($column, array $values, $negate=false, $logical=null)
    {
        $expression = new UnaryExpression($column, $values, $negate, SQLExpression::UNARY_EXISTS, $logical);
        $this->addCondition($expression);
        return $this;
    }
    
    /**
     * Adds NOT EXISTS clause to the WHERE clause to the query.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function whereNotExists($column, array $values)
    {
        return $this->whereExists($column, $values, TRUE);
    }
    
    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator in the EXISTS clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function andExists($column, array $values)
    {
        return $this->whereExists($column, $values, FALSE, SQLExpression::LOGIC_AND);
    }
    
    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator in the NOT EXISTS clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function andNotExists($column, array $values)
    {
        return $this->whereExists($column, $values, TRUE, SQLExpression::LOGIC_AND);
    }
    
    /**
     * Connects any previously defined set of conditions to the provided list
     * using the OR operator in the EXISTS clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     * If negate is set to true, it adds NOT LIKE clause
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function orExists($column, array $values)
    {
        return $this->whereExists($column, $values, FALSE, SQLExpression::LOGIC_OR);
    }
    
    /**
     * Connects any previously defined set of conditions to the provided list
     * using the OR operator in the NOT EXISTS clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     * If negate is set to true, it adds NOT LIKE clause
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values The list of values to check the the specified column value in
     * @return $this
     */
    public function orNotExists($column, array $values)
    {
        return $this->whereExists($column, $values, TRUE, SQLExpression::LOGIC_OR);
    }
       
    /**
     * Adds LIKE clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in
     * @param bool $negate Negates the expression
     * @param string|int $logical Logical operator
     * @return $this
     */
    public function whereLike($column, $value=null, $negate=false, $logical=null)
    {
        $expression = new LikeExpression($column,$value, $negate, $logical);
        $this->addCondition($expression);
        return $this;
    }
    
    /**
     * Adds NOT LIKE clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in
     * @return $this
     */
    public function whereNotLike($column, $value=null)
    {
        return $this->whereLike($column, $value, TRUE);
    }

    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the AND operator in the LIKE clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in
     * @return $this
     */
    public function andLike($column, $value=null)
    {
        return $this->whereLike($column, $value, FALSE, SQLExpression::LOGIC_AND);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the AND operator in the LIKE clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in
     * @return $this
     */
    public function andNotLike($column, $value)
    {
        return $this->whereLike($column, $value, TRUE, SQLExpression::LOGIC_AND);
    }
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the OR operator in the LIKE  clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in

     * @return $this
     */
    public function orLike($column, $value)
    {
        return $this->whereLike($column, $value, FALSE, SQLExpression::LOGIC_OR);
    } 
    
    /**
     *  Connects any previously defined set of conditions to the provided list
     * using the OR operator in the NOT LIKE  clause
     * 
     * This is a shorthand method for building joins via `andWhere()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param mixed $value The value to check the the specified column value in

     * @return $this
     */
    public function orNotLike($column, $value=null)
    {
        return $this->whereLike($column, $value, TRUE, SQLExpression::LOGIC_OR);
    } 
    
    /**
     * Adds BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * @param bool $negate Negates the expression
     * @param string|int $logical Logical operator
     * @return $this
     */
    public function whereBetween($column, array $values, $negate=false, $logical=null)
    {
        $expression = new BetweenExpression($column,$values, $negate, $logical);
        $this->addCondition($expression);
        return $this;
    }
    
    /**
     * Adds NOT BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * 
     * @return $this
     */
    public function whereNotBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, TRUE);
    }
    
    /**
     * Adds AND BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * @return $this
     */
    public function andBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, FALSE,SQLExpression::LOGIC_AND);
    }
    
    /**
     * Adds AND NOT BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * @return $this
     */
    public function andNotBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, TRUE,SQLExpression::LOGIC_AND);
    }
    
    /**
     * Adds OR BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * @return $this
     */
    public function orBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, FALSE,SQLExpression::LOGIC_OR);
                
    } 
    
    /**
     * Adds OR NOT BETWEEN clause to the WHERE clause to the query in order to do searching
     * 
     * This is a shorthand method for building joins via `where()`.
     *
     * The table name can be passed as a string, or as an array 
     * @param string $column Column name. If only column is provided, the whole condition is evaluated as a single string
     * @param array $values A range of values
     * @return $this
     */
    public function orNotBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, TRUE,SQLExpression::LOGIC_OR);
                
    }
	
    /**
     * Adds a new condition to the expression object in the form "field IS NULL".
     *
     * @param string $column database field to be tested for null
     * @param bool $negate Negates the expression
     * @param string|int $logical Logical operator
     * @return $this
     */
    public function whereIsNull($column, $negate=false, $logical=null)
    {
        $expression = new NullExpression($column,$negate, $logical);
        $this->addCondition($expression);
        return $this;
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NOT NULL".
     *
     * @param string $column database field to be tested for null
     * @return $this
     */
    public function whereIsNotNull($column)
    {
        return $this->whereIsNull($column, TRUE);
    }

    /**
     * Adds Logical operator AND to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     *
     * @return $this
     */
    public function andIsNull($column)
    {
        return $this->whereIsNull($column,FALSE ,SQLExpression::LOGIC_AND);
    }

    /**
     * Adds Logical operator AND to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     *
     * @return $this
     */
    public function andNotNull($column)
    {
        return $this->whereIsNull($column, TRUE,SQLExpression::LOGIC_AND);
    }

    /**
     * Adds Logical operator AND to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     *
     * @return $this
     */
    public function orIsNull($column)
    {
        return $this->whereIsNull($column, FALSE,SQLExpression::LOGIC_OR);
    }

    /**
     * Adds Logical operator OR to concatenate condition in the WHERE clause for this
     * query. The condition contains a table column
     * @param string|array $column Column name. If only column is provided, the whole condition is evaluated as a single string
     *
     * @return $this
     */
    public function orNotNull($column)
    {
        return $this->whereIsNull($column, TRUE,SQLExpression::LOGIC_OR);
    }

    /**
     * Adds a new condition to the expression object in the form "field > value".
     *
     * @param string $column Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison

     * will be created, one per each value in the array.
     * @return $this
     */
    public function whereGreater($column, $value)
    {
	    return $this->where($column, $value, '>');
    }

    /**
     * Adds a new condition to the expression object in the form "field < value".
     *
     * @param string $column Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison

     * will be created, one per each value in the array.
     * @return $this
     */
    public function whereLess($column, $value)
    {
	    return $this->where($column, $value, '<');
    }

    /**
     * Adds a new condition to the expression object in the form "field =. value".
     *
     * @param string $column Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison

     * will be created, one per each value in the array.
     * @return $this
     */
    public function greaterOrEqual($column, $value)
    {
	    return $this->where($column, $value, '=>');
    }

    /**
     * Adds a new condition to the expression object in the form "field =. value".
     *
     * @param string $column Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison

     * will be created, one per each value in the array.
     * @return $this
     */
    public function lessOrEqual($column, $value)
    {
	    return $this->where($column, $value, '<=');
    }
	

	
}