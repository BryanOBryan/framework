<?php

namespace Kaliba\Database\Contracts;
use Kaliba\Database\Query\Builder;
use PDO;

/**
 * Represents a connection with a database server.
 */
interface ConnectionInterface
{
    /**
     * Starts a new transaction.
     *
     * @return void
     */
    public function begin();

    /**
     * Commits current transaction.
     *
     * @return bool true on success, false otherwise
     */
    public function commit();

    /**
     * Rollback current transaction.
     *
     * @param bool|null $toBeginning Whether or not the transaction should be rolled back to the
     * beginning of it. Defaults to false if using savepoints, or true if not.
     * @return bool
     */
    public function rollback();

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function bindings(array $bindings);

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings);

    /**
     * Set the PDO connection.
     *
     * @param  \PDO|  $pdo
     * @return $this
     */
    public function setPdo(\PDO $pdo);
    
    /**
     * Get the internal PDO object
     * @return PDO
     */
    public function getPdo();

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each of those parameters.
     *
     * @param string $query SQL to be executed and interpolated with $params
     * @param array $bindings list or associative array of parameters to be interpolated in $query as values
     * @return \PDOStatement
     */
    public function execute($query, $bindings=[]);
      
    /**
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \PDOStatement
     */
    public function query($sql);

    /**
     * Prepare SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \PDOStatement
     */
    public function prepare($sql);

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @return Builder
     */
    public function insert($table);

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @return Builder
     */
    public function update($table);

    /**
     * Executes a DELETE statement on the specified table.
     *
     * @param string $table the table to delete rows from
     * @return Builder
     */
    public function delete($table);
    
    /**
     * Executes a SELECT statement on the specified table.
     *
     * @@param string $table the table to select rows from
     * @return Builder
     */
    public function select($table);

    /**
     * Returns last id generated for a table or sequence in database
     * @return string|int
     */
    public function insertId();

    /**
     * Disconnects from database server
     *
     * @return void
     */
    public function disconnect();

    /**
     * Check whether or not the driver is connected.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return string
     */
    public function quote($value);
       
    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $name The identifier to quote.
     * @return string
     */
    public function identify($name);

    /**
     * Run SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKey();

    /**
     * Run SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKey();

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    public function supportsDynamicConstraints();

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabase();

    /**
     * Set the name of the connected database.
     *
     * @param  string  $database
     * @return string
     */
    public function setDatabase($database);

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix();

    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setTablePrefix($prefix);

}
