<?php

namespace Kaliba\Database\Connections;
use Kaliba\Database\Contracts\ConnectionInterface;
use Kaliba\Database\Query\DeleteExpression;
use Kaliba\Database\Query\InsertExpression;
use Kaliba\Database\Query\SelectExpression;
use Kaliba\Database\Query\UpdateExpression;
use Kaliba\Database\Query\QueryBuilder;
use PDO;

/**
 * Represents a connection with a database server.
 */
abstract class Connection implements ConnectionInterface
{
    /**
     * The name of the connected database.
     *
     * @var string
     */
    protected $database;

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     *  String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $startQuote = '';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $endQuote = '';

    /**
     *
     * @var PDO
     */
    protected $pdo;


    /**
     * Connection constructor.
     * @param PDO $pdo
     * @param null $database
     * @param null $tablePrefix
     */
    public function __construct(PDO $pdo, $database=null, $tablePrefix=null)
    {     
        $this->pdo = $pdo;
        $this->database = $database;
        $this->tablePrefix = $tablePrefix;
    }
  
    /**
     * Destructor
     *
     * Disconnects the driver to release the connection.
     */
    public function __destruct()
    {
        unset($this->pdo);
    }

    /**
     * Set the internal driver name
     *
     * @param  string $name
     * @return $this
     */
    public function setDriver($name)
    {
        $this->driver = $name;
        return $this;
    }

    /**
     * Get the intrnal driver name
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * Set the PDO connection.
     *
     * @param  \PDO|\Closure|null  $pdo
     * @return $this
     */
    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }
    
    /**
     * Get the internal PDO object
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each of those parameters.
     *
     * @param string $query SQL to be executed and interpolated with $params
     * @param array $bindings list or associative array of parameters to be interpolated in $query as values
     * @return \PDOStatement
     */
    public function execute($query, $bindings=[])
    {
        $statement = $this->pdo->prepare($query);
        $values = $this->bindings($bindings);
        $this->bindValues($statement, $values);
        $statement->execute();
        return $statement;

    }
      
    /**
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \PDOStatement
     */
    public function query($sql)
    {
        $statement = $this->pdo->query($sql);
        return $statement;
    }

    /**
     * Create a new Query instance for building data manipulation queries
     *
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return new QueryBuilder($this);
    }

    /**
     * Prepare SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \PDOStatement
     */
    public function prepare($sql)
    {
        $statement = $this->pdo->prepare($sql);
        return $statement;
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @return InsertExpression
     */
    public function insert($table)
    {
        return $this->newQuery()->insert($table);
    }

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @return UpdateExpression
     */
    public function update($table)
    {
        return $this->newQuery()->update($table);
    }

    /**
     * Executes a DELETE statement on the specified table.
     *
     * @param string $table the table to delete rows from
     * @return DeleteExpression
     */
    public function delete($table)
    {
        return $this->newQuery()->delete($table);
    }
    
    /**
     * Executes a SELECT statement on the specified table.
     *
     * @param string $table Database table name
     * @return SelectExpression
     */
    public function select($table)
    {
        return $this->newQuery()->select($table);
    }

    /**
     * Returns last id generated for a table or sequence in database
     * @return string|int
     */
    public function insertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Disconnects from database server
     *
     * @return void
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     * Check whether or not the driver is connected.
     *
     * @return bool
     */
    public function isConnected()
    {
        if ($this->pdo === null) {
            $connected = false;
        } else {
            try {
                $connected = $this->pdo->query('SELECT 1');
            } catch (\PDOException $e) {
                $connected = false;
            }
        }
        return $connected;
    }
    
    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return string
     */
    public function quote($value)
    {
        if(is_numeric($value) || is_int($value)){
            $type = \PDO::PARAM_INT;
        }
        elseif(is_bool($value)){
            $type = \PDO::PARAM_BOOL;
        }
        else{
            $type = \PDO::PARAM_STR;
        }
 
        return $this->pdo->quote($value, $type);
    }
    
    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function identify($identifier)
    {
        $identifier = trim($identifier);

        if ($identifier === '*') {
            return '*';
        }

        if ($identifier === '') {
            return '';
        }

        // string
        if (preg_match('/^[\w-]+$/', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        if (preg_match('/^[\w-]+\.[^ \*]*$/', $identifier)) {
            // string.string
            $items = explode('.', $identifier);
            return $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote;
        }

        if (preg_match('/^[\w-]+\.\*$/', $identifier)) {
            // string.*
            return $this->startQuote . str_replace('.*', $this->endQuote . '.*', $identifier);
        }

        if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) {
            // Functions
            return $matches[1] . '(' . $this->identify($matches[2]) . ')';
        }

        // Alias.field AS thing
        if (preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+AS\s*([\w-]+)$/i', $identifier, $matches)) {
            return $this->identify($matches[1]) . ' AS ' . $this->identify($matches[3]);
        }

        if (preg_match('/^[\w-_\s]*[\w-_]+/', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        return $identifier;

    }
    
    /**
     * Starts a new transaction.
     *
     * @return void
     */
    public function begin()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits current transaction.
     *
     * @return bool true on success, false otherwise
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback current transaction.
     *
     * @param bool|null $toBeginning Whether or not the transaction should be rolled back to the
     * beginning of it. Defaults to false if using savepoints, or true if not.
     * @return bool
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function bindings(array $bindings)
    { 
        foreach ($bindings as $key => $value) {
            if ($value instanceof \DateTime) {
                $bindings[$key] = $value->format('Y-m-d H:i:s');
            }elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }
        return $bindings;
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        
    }

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param  string  $database
     * @return string
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }


}
