<?php
namespace Kaliba\ORM\Contracts;

interface DataSource 
{
    /**
     * Get table primary key .
     *
     * @return string
     */
    public function getPrimaryKey();

    /**
     * Get the table name
     *
     * @return string
     */
    public function getName();

    /**
     * Delete record from the database.
     * @param string|int $key
     * @return bool
     *
     */
    public function delete($key);

    /**
     * Delete records from the database table meeting the condition
     * @param array $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return bool
     *
     */
    public function deleteWhere(array $where, $order=null, $limit=null, $offset=null);

    /**
     * Fetch a single record from the database table if identifier is provided.
     * @param string|int $key Primary key of the record to fetch
     * @return mixed
     */
    public function fetch($key);

    /**
     * Fetch all records from the database table.
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function fetchAll($order = null, $limit=null,$offset=null);

    /**
     * Filter records by specific conditions
     * @param array $where An associate array with keys and their values
     * @param string $order sort value
     * @param int $limit limit value
     * @param int $offset limit value.
     * @return array
     */
    public function fetchWhere(array $where, $order = null, $limit=null,$offset=null);

    /**
     * Execute an SQL aggregate function
     * @param string $function
     * @param string $field
     * @param string $group
     * @param array $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function aggregate($function, $field, $group = null, array $where = [], $order = null, $limit = null, $offset = null);
       
    /**
     * Insert or update record in the database
     * @param mixed $data
     */
    public function save($data);
    
}
