<?php

namespace Kaliba\Database\Query;
use Kaliba\Database\Connections\Connection;
use Kaliba\Database\Query\DeleteExpression;
use Kaliba\Database\Query\InsertExpression;
use Kaliba\Database\Query\SelectExpression;
use Kaliba\Database\Query\UpdateExpression;

class QueryBuilder
{

    /**
     *
     * @var Connection
     */
    private  $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create an insert query.
     *
     * @param string $table The table to insert values into
     * @return InsertExpression
     */
    public function insert($table)
    {
        $query = new InsertExpression($this->connection);
        $query->table($table);
        return $query;
    }

    /**
     * Create an update query.
     *
     * Can be combined with set() and where() methods to create update queries.
     *
     * @param string $table The table you want to update.
     * @return UpdateExpression
     */
    public function update($table)
    {
        $query = new UpdateExpression($this->connection);
        $query->table($table);
        return $query;
    }

    /**
     * Create a delete query.
     *
     * Can be combined with from(), where() and other methods to
     * create delete queries with specific conditions.
     *
     * @param string $table The table to use when deleting.
     * @return DeleteExpression
     */
    public function delete($table)
    {
        $query = new DeleteExpression($this->connection);
        $query->table($table);
        return $query;
    }

    /**
     * Adds new fields to be returned by a SELECT statement when this query is
     * executed. Fields can be passed as an array of strings, a single expression or a single string
     * @param string $table table name
     *
     * @return SelectExpression
     */
    public function select($table)
    {
        $query = new SelectExpression($this->connection);
        $query->table($table);
        return $query;
    }

}