<?php
namespace Kaliba\ORM;
use Kaliba\Collection\Collection;
use Kaliba\Database\Connections\Connection;
use Kaliba\ORM\Concerns\HasAttributes;
use Kaliba\ORM\Concerns\HasHandler;
use Kaliba\ORM\Concerns\HasRelations;
use Kaliba\ORM\Concerns\HasResolver;
use Kaliba\ORM\DataSource\Database;
use Kaliba\Support\Inflector;
use BadMethodCallException;
use DateTime;

class Model implements \ArrayAccess, \JsonSerializable
{
    use HasResolver,
        HasAttributes,
        HasRelations,
        HasHandler;
    
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $tableName;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes=[])
    {
        $this->fill($attributes);
        $this->initialize();
        $this->loadRelations();
    }

    /**
     * Create a new instance of the model.
     *
     * @param  array  $attributes
     * @return static
     */
    public function instance(array $attributes = [])
    {
        $model = new static($attributes);
        return $model;
    }

    /**
     * Get Mapper
     * @return Mapper
     */
    public function getMapper()
    {
        return static::getHandler($this->getTable());
    }

    /**
     * Get class name and namespace
     * @return string
     */
    public function getClass()
    {
        return get_class($this);
    }

    /**
     * Set the table associated with the model.
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->tableName = $table;
        return $this;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if(empty($this->tableName)){
            $class = $this->getClass();
            $array = explode("\\", $class);
            $table = end($array);
            return Inflector::tableize($table);
        }
        return $this->tableName;
    }

    /**
     * Set Primary Key Value
     * @param mixed $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->setAttribute($this->getPrimaryKey(), $key);
        return $this;
    }

    /**
     * Get the key for the model.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute(
            $this->getPrimaryKey()
        );
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
     *
     * @param  string  $key
     * @return $this
     */
    public function setPrimaryKey($key)
    {
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        $array = explode("\\", $this->getClass()) ;
        $base = end($array);
        return Inflector::delimit($base).'_'.$this->primaryKey;
    }

    /**
     * Get the current connection name for the model.
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name with the model.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;
        return $this;
    }

    /**
     * Get the Connection instance for the model.
     * @return Connection
     */
    public function getConnection()
    {
        return static::resolve($this->getConnectionName());
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
     * @param  Model
     * @return bool
     */
    public function matches(Model $model)
    {
        return ! is_null($model) &&
            $this->getKey() === $model->getKey() &&
            $this->getTable() === $model->getTable() &&
            $this->getConnectionName() === $model->getConnectionName();
    }

    /**
     * Check if Model is new
     * @param Model $model
     * @return bool
     */
    public function isNew()
    {
        return empty($this->getKey())?true:false;
    }

    /**
     * Save the model into the datasource
     * @param array $record
     * @return bool
     */
    public function save(array $record=[])
    {
        if(!empty($record)){
            $this->fill($record);
        }
        if($this->getAttributes() == null){
            return false;
        }else{
            $this->updateTimestamps();
            return $this->getMapper()->save($this->getAttributes());
        }
    }    

    /**
     * Self Load model from the datasource
     */
    public function load($id)
    {
        $attributes = static::find($id)->getAttributes();
        $this->fill($attributes);
    }
	
    /**
     * Delete this model from the data source
     */
    public function delete($id = null)
    { 
        if(!empty($id)){
            return $this->getMapper()->delete($id); 
        }
        return $this->getMapper()->delete( $this->getKey() );       
    }

    /**
     * Get QueryBuilder instance for creating queries
     * @return \Kaliba\Database\Query\QueryBuilder
     */
    public function query()
    {
        return $this->getConnection()->newQuery();
    }

    /**
     * Create a mapper instance
     * @param string $table
     * @param string $primaryKey
     * @param string $resultClass
     * @return Mapper
     */
    protected function mapper($table, $primaryKey, $resultClass = null)
    {
        $connection = $this->getConnection();
        $datasource = new Database($connection, $table, $primaryKey);
        return new Mapper($datasource, $resultClass);
    }

    /**
     *  Set the mapper to handle object relation mapping
     */
    private function initialize()
    {
        static::setHandler($this->getTable(),
            $this->mapper(
                $this->getTable(),
                $this->getPrimaryKey(),
                $this->getClass()
            )
        );
        $this->setKey(null);

    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = new DateTime();
        if($this->isNew()){
            $this->setAttribute(static::CREATED_AT, $time);
            $this->setAttribute(static::UPDATED_AT, $time);
        }else{
            $this->setAttribute(static::UPDATED_AT, $time);
        }
    }

    /**
     * Forward a method call to the given object.
     *
     * @param  mixed  $object
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    protected function call($object, $method, $parameters)
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (BadMethodCallException $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (! preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }
            if ($matches['class'] != get_class($object) ||
                $matches['method'] != $method) {
                throw $e;
            }
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, $method
            ));
        }
    }

    /**
     * Select records from the database
     * @param string $columns
     * @return \Kaliba\Database\Query\SelectExpression
     */
    public static function select($columns='*')
    {
        $model = new static();
        return $model->query()->select($model->getTable())->columns($columns);
    }

    /**
     * Save the model into the datasource
     * @param array $record
     * @return bool
     */
    public static function create(array $record)
    {
        return (new static())->save($record);
    }

    /**
     * Retrieve a record by the ID
     * @param string|int $id
     * @return static
     */
    public static function find($id)
    {
        return (new static())->getMapper()->get($id);
    }

    /**
     * Retrieve all records from the data source
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public static function all($limit=null, $offset=null)
    {
        $model = new static();
        $records = $model->getMapper()->limit($limit)->offset($offset)->fetch();
        return static::collect($records);
    }

    /**
     * Filter records from the data source
     * @param mixed $key
     * @param mixed $value
     * @return Mapper
     */
    public static function where($key, $value=null)
    {
        $model = new static();
        if(is_array($key) && empty($value)){
            return $model->getMapper()->filter($key);
        }
        return $model->getMapper()->where($key, $value);

    }

    /**
     * Delete a single record or multiple records from the data source
     * @param array|string|int $key
     * @return bool
     */
    public static function destroy($key, $value=null)
    {
        $model = new static();      
        if(!is_array($key) && empty($value)){
            return $model->getMapper()->delete($key);
        }       
        return self::where($key, $value)->destroy();
        
    }
    
    /**
     * @param array $records
     * @return Collection
     */
    protected static function collect(array $records)
    {
        $collection = new Collection($records);
        return $collection;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return !is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
    
    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \RunTimeException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(json_last_error_msg());
        }

        return $json;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->call($this, $method, $parameters);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
    
    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
 

}