<?php
namespace Kaliba\ORM\Datasource;
use Kaliba\Database\Connections\Connection;
use Kaliba\ORM\Contracts\DataSource;

class Database implements DataSource
{

    /**
     * The table associated with the entity.
     *
     * @var string
     */
    protected $tableName;

    /**
     * The primary key for the entity.
     *
     * @var mixed
     */
    protected $primaryKey;

    /**
     *
     * @var Connection
     */
    protected $connection;

    /***
     * Database constructor.
     * @param Connection $connection
     * @param $tableName
     * @param string $primaryKey
     */
    public function __construct(Connection $connection, $tableName, $primaryKey='id' )
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->primaryKey = is_array($primaryKey) ? $primaryKey : [$primaryKey];

    }

    /**
     * Get the current connection.
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get table primary key .
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getName()
    {
        return $this->tableName;
    }

    /**
     * Delete record from the database.
     * @param string|int $key
     * @return bool
     *
     */
    public function delete($key)
    {
        $primaryKey = current($this->primaryKey);
        $stmt = $this->getConnection()
            ->delete($this->tableName)
            ->where($primaryKey, $key)
            ->execute();
        return($stmt->rowCount()>= 1)?true:false;
    }

    /**
     * Delete records from the database table meeting the condition
     * @param array $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return bool
     *
     */
    public function deleteWhere(array $where, $order=null, $limit=null, $offset=null)
    {
        $stmt = $this->getConnection()
            ->delete($this->tableName)
            ->where($where)
            ->orderBy($order)
            ->limit($limit)
            ->offset($offset)
            ->execute();
        return($stmt->rowCount()>= 1)?true:false;
    }

    /**
     * Fetch a single record from the database table if identifier is provided.
     * @param string|int $key Primary key of the record to fetch
     * @return mixed
     */
    public function fetch($key)
    {
        $primaryKey = current($this->primaryKey);
        return $this->getConnection()
            ->select($this->tableName)
            ->where($primaryKey, $key)
            ->fetch();
    }

    /**
     * Fetch all records from the database table.
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function fetchAll($order = null, $limit=null,$offset=null)
    {
        return $this->getConnection()
            ->select($this->tableName)
            ->orderBy($order)
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();
    }

    /**
     * Filter records by specific conditions
     * @param array $where An associate array with keys and their values
     * @param string $order sort value
     * @param int $limit limit value
     * @param int $offset limit value.
     * @return array
     */
    public function fetchWhere(array $where, $order = null, $limit=null,$offset=null)
    {
        return $this->getConnection()
            ->select($this->tableName)
            ->where($where)
            ->orderBy($order)
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();
    }

    /**
     * Execute an SQL aggregate function
     * @param string $function
     * @param string $field
     * @param string $group
     * @param array $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     */
    public function aggregate($function, $field, $group = null, array $where = [], $order = null, $limit = null, $offset = null)
    {
        if(empty($group)){
            $group = $field;
        }
        return $this->getConnection()
            ->select($this->tableName)->func($function, $field)
            ->where($where)->groupBy($group)->orderBy($order)
            ->limit($limit)->offset($offset)
            ->execute()->fetchColumn();
    }

    /**
     * Insert or update record in the database
     * @param mixed $data
     */
    public function save($data)
    {
        $data = $this->sync($data);
        $isNew = $this->isNew($data);
        if($isNew){
            return $this->insert($data);
        }else{
            return $this->update($data);
        }
    }

    /**
     * Synchronize incoming data with the database table
     * @param mixed $data
     * @return array
     */
    private function sync($data )
    {
        $sync = [];
        $stmt = $this->getConnection()->select($this->tableName)->limit(1)->execute();
        for ($index = 0; $index < $stmt->columnCount(); $index++) {
            $meta = $stmt->getColumnMeta($index);
            $column = $meta['name'];
            if(property_exists($data, $column)){
                $sync[$column] = $data->{$column};
            }
        }
        return $sync;
    }

    /**
     * Check if record is fresh and new
     * @param array $data
     * @return bool
     */
    private function isNew(array $data)
    {
        $new = false;
        foreach ($this->primaryKey as $key) {
            if(empty($data[$key])) {
                $new = true;
            }
        }
        return $new;
    }

    /**
     * Perform insert operation
     * @param array $data
     * @return bool
     */
    private function insert(array $data )
    {
        $stmt = $this->getConnection()
            ->insert($this->tableName)
            ->values($data)
            ->execute();
        return($stmt->rowCount()>= 1)?true:false;
    }

    /**
     * Perform update operation
     * @param array $data
     * @return bool
     */
    private function update(array $data)
    {
        $where = $this->where($data);
        $data = $this->data($data);
        $stmt = $this->getConnection()
            ->update($this->tableName)
            ->set($data)
            ->where($where)
            ->execute();
        return($stmt->rowCount()>= 1)?true:false;
    }

    /**
     * Fix data for update operation
     * @param array $data
     * @return array
     */
    private function data(array $data)
    {
        foreach ($this->primaryKey as $key) {
            if (!empty($data[$key] )) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Get condition for update operation
     * @param array $data
     * @return array
     */
    private function where(array $data)
    {
        $where = array();
        foreach ($this->primaryKey as $key) {
            if (!empty($data[$key] )) {
                $where[$key] = $data[$key];
            }
        }
        return $where;
    }



}
