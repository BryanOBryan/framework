<?php
namespace Kaliba\Database\Query;
use Kaliba\Database\Connections\Connection;
use Kaliba\Database\Contracts\Executable;
use Kaliba\Database\Contracts\SQLExpression;
use Kaliba\Database\Query\Concerns\ExpressionTrait;


abstract class QueryExpression implements SQLExpression, Executable
{
    use ExpressionTrait;

    /**
     *
     * @var mixed
     */
    protected $table;

    /**
     * @var mixed
     */
    protected $functions;

    /**
     *
     * @var mixed
     */
    protected $columns;

    /**
     * @var mixed
     */
    protected $conditions;

    /**
     * @var mixed
     */
    protected $joins;

    /**
     *
     * @var mixed
     */
    protected $groupBy;

    /**
     *
     * @var mixed
     */
    protected $orderBy;

    /**
     *
     * @var int
     */
    protected $limit;

    /**
     *
     * @var int
     */
    protected $offset;

    /**
     *
     * @var Connection
     */
    protected  $connection;

    /**
     * QueryExpression constructor.
     * @param Connection|null $connection
     */
    public function __construct(Connection $connection = null)
    {
        $this->setConnection($connection);
    }

    /**
     * Sets the connection instance to be used for executing and transforming this query.
     *
     * @param Connection $connection Connection instance
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Gets the connection instance to be used for executing and transforming this query.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
	
    /**
     * Adds a single or multiple tables to be used in the FROM clause for this query.
     * Tables can be passed as an array of strings, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias tables using the value as the
     * real field to be aliased. It is possible to alias strings, ExpressionInterface objects or
     * even other Query objects.
     *
     * By default this function will append any passed argument to the list of tables
     * to be selected from
     *
     * ### Examples:
     *
     * ```
     *  $query->table(['authors', 'users']); //Produces FROM authors, users
     *  $query->table(['posts' => 'p']); // Produces FROM posts p
     *  $query->table('authors', 'users'); //Produces FROM authors, users
     * ```
     *
     * @param array|string $table tables to be added to the list
     * @return $this
     */
    public function table($table)
    {
        $tables = [];
        if(is_array($table)){
            $tables =  $table;
        }
        elseif(func_num_args() > 1){
            $tables = func_get_args();
        }
        else{
            $tables= (array)$table;
        }
        $_table = array_map([$this, 'identify'], $tables);
        $this->table = implode(',', $_table);
        return $this;
    }

    /**
     * Adds new fields to be returned by a SELECT statement when this query is
     * executed. Fields can be passed as an array of strings, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings
     *
     * By default this function will append any passed argument to the list of fields
     * to be selected, unless the second argument is set to true.
     *
     * ### Examples:
     *
     * ```
     * $query->select(['id', 'title']); // Produces SELECT id, title
     * $query->select(['author_id' => 'author']); // Appends author: SELECT id, title, author_id as author
     * $query->select('id', 'name');  // Produces SELECT id, name
     * $query->select('id', 'firstname as name');  // Produces SELECT id, firstname as name
     * ```
     *
     * By default all fields are selected
     *
     * @return $this
     */
    public function columns($columns=null)
    {
        $fields = [];
        if(is_array($columns)){
            $fields =  $columns;
        }
        elseif(func_num_args() > 1){
            $fields = func_get_args();
        }
        else{
            $fields = (array)$columns;
        }
        if(isset($fields) && current($fields) !== "*"){
            $fields = array_map([$this, 'identify'], $fields);
        }
        $this->columns .= implode(', ', $fields);
        return $this;
    }

    /**
     * Sets the number of records that should be retrieved from database,
     * accepts an integer..
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     * $query->limit(10) // generates LIMIT 10
     * ```
     *
     * @param int $limit number of records to be returned
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the number of records that should be skipped from the original result set
     * This is commonly used for paginating large results. Accepts an integer or an
     * expression object that evaluates to an integer.
     *
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     *  $query->offset(10) // generates OFFSET 10
     * ```
     *
     * @param int $offset number of records to be skipped
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Paginate the Records by setting the limit and offset by page number
     * @param int $pageNumber number of page where to start retriveting records from
     * @param int $perPage number of records to be returned
     *
     * @return $this
     */
    public function paginate($pageNumber, $perPage)
    {
        $page = ($pageNumber > 0) ? $pageNumber : 1;
        $rowCount = ($perPage > 0) ? $perPage : 1;
        $this->limit = (int) $rowCount;
        $this->offset = (int) $rowCount * ($page - 1);
        return $this;
    }

    /**
     * Adds a single or multiple fields to be used in the GROUP BY clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * @param array|string $column fields to be added to the list
     * @return $this
     */
    public function groupBy($column)
    {
        $expression = new GroupByExpression($column);
        $this->groupBy .= $expression->getSql();
        if($expression->hasBindings()){
            $this->setBindings($expression->getBindings());
        }
        return $this;
    }

    /**
     * Add an ORDER BY clause with an ASC direction.
     *
     * @param string|array $column The field to order on.
     * @param string $type Sorting type. Either ASC or DESC
     * @return $this
     */
    public function orderBy($column, $type='ASC')
    {
        $expression = new OrderByExpression($column);
        $this->orderBy .= $expression->getSql();
        if($expression->hasBindings()){
            $this->setBindings($expression->getBindings());
        }

        return $this;
    }

    /**
     * Adds one or more conditions to this expression object. Conditions can be
     * expressed in a one dimensional array, that will cause all conditions to
     * be added directly at this level of the tree or they can be nested arbitrarily
     * making it create more expression objects that will be nested inside and
     * configured to use the specified conjunction.
     * @param SQLExpression;
     */
    public function addCondition(SQLExpression $expression)
    {
        $this->conditions .= $expression->getSql();
        if($expression->hasBindings()){
            $this->setBindings($expression->getBindings());
        }
    }

    /**
     * Adds join expression to this expression object.
     * @param JoinExpression;
     */
    public function addJoin(JoinExpression $expression)
    {
        $this->joins .= $expression->getSql();

    }

    /**
     * Adds one function to this expression object. Conditions can be
     * expressed in a one dimensional array, that will cause all conditions to
     * be added directly at this level of the tree or they can be nested arbitrarily
     * making it create more expression objects that will be nested inside and
     * configured to use the specified conjunction.
     * @param FunctionExpression;
     */
    public function addFunction(FunctionExpression $expression)
    {
        $this->functions .= $expression->getSql();

    }

    /**
     * Execute the query statement and return a PDO Statement Object
     * @return \PDOStatement|void
     */
    public function execute()
    {
        $sql = $this->getSql();
        $bindings = $this->getBindings();
        return $this->connection->execute($sql, $bindings);
    }

    /**
     * Quotes value to be used safely in database query.
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return mixed quoted value
     */
    protected function quotes($data)
    {
        if(is_array($data)){ 
            return array_map([$this, 'quote'], $data);
        }
        return $this->quote($data);
       
    }
    
    /**
     * Quotes value to be used safely in database query.
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return mixed quoted value
     */
    protected function quote($value)
    {
        if($this->connection){
            return $this->connection->quote($value);
        }else{
            return $value;
        }

    }
    
    /**
     * Quotes identifiers to avoid using reserved key words in database query.
     *
     * @param string $identifier The string to quote.
     * @return string quoted string
     */
    protected function identify($identifier)
    {
        if($this->connection){
            return $this->connection->identify($identifier);
        }else{
            return $identifier;
        }

    }
      
    /**
     * @return string
     */
    public function __toString() 
    {
        return $this->getSql();
    }


}