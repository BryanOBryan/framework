<?php
namespace Kaliba\ORM;
use Kaliba\ORM\Contracts\DataSource;
use Kaliba\ORM\Contracts\Relation;
use Kaliba\ORM\Support\Iterator;
use Kaliba\ORM\Support\MultiPk;
use Kaliba\ORM\Support\Entity;
use Kaliba\ORM\Support\Visibility;
use Kaliba\ORM\Relation\OneToOne;
use Kaliba\ORM\Relation\OneToMany;
use Kaliba\ORM\Relation\ManyToMany;
use Kaliba\Support\Inflector;

class Mapper implements  \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     *
     * @var DataSourse
     */
    private $dataSource;
    
    /**
     *
     * @var array
     */
    private $relations=[];
    
    /**
     *
     * @var array
     */
    private $filters=[];
    
    /**
     *
     * @var string
     */
    private $orderBy;
    
    /**
     *
     * @var int
     */
    private $limit;
    
    /**
     *
     * @var int
     */
    private $offset;

    /**
     * @var Entity
     */
    private $entity;

    /**
     *
     * @var string
     */
    private $resultClass = \stdClass::class;

    /**
     * Mapper constructor.
     * @param \Kaliba\ORM\DataSource $dataSource
     * @param string|null $resultClass
     */
    public function __construct(DataSource $dataSource, string $resultClass=null)
    {
        $this->dataSource = $dataSource;
        if(!empty($resultClass)){
            $this->resultClass = $resultClass;
        }
        $this->entity = new Entity($this, $this->resultClass);
    }

    /** Add a relation to the mapper
     * @param $name
     * @param \Kaliba\ORM\Relation $relation
     * @return $this
     */
    public function addRelation($name, Relation $relation)
    {
        $this->relations[$name] = $relation;
        return $this;
    }

    /** Count total number of records
     * @param null $group
     * @return int
     */
    public function count($group = null)
    {
        if($group== null){
            $group = $this->dataSource->getPrimaryKey();
        }
        return $this->dataSource->aggregate('count', $group, $group, $this->filters);        
    }

    /**
     * Filter records
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function where($key, $value=null)
    {
        if(is_array($key)){
            $this->filter($key);
        }else{
            $this->filters[$key] = $value; 
        }    
        return $this;
    }

    /**
     * Filter records
     * @param array $filter
     * @return $this
     */
    public function filter($filter)
    {
        if(!empty($filter)){
            $this->filters = $filter;
        }
        return $this;
    }

    /** orderBy records
     * @param string $column
     * @return $this
     */
    public function orderBy($column)
    {
        if(!empty($column)){
            $this->orderBy = $column;
        }
        return $this;
    }

    /**
     * Limit number of records to be returned
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        if(!empty($limit)){
            $this->limit = $limit;
        }
        return $this;
    }

    /**
     * Set cursor position to a specific offset
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        if(!empty($offset)){
            $this->offset = $offset;
        }
        return $this;
    }

    /** Get a single record from the records
     * @param int $index
     * @return mixed|null
     */
    public function item($index)
    {
        $array = $this->fetch();
        return isset($array[$index]) ? $array[$index] : null;
    }

    /**
     * Add new record to the datasource
     * @param mixed $object
     * @throws \Exception
     */
    public function save($object)
    {
        return $this->set(null, $object);
    }

    /** Get a single record from the data source
     * @param string|int $key
     * @return MultiPk|mixed
     */
    public function get($key) 
    {
        if (count($this->dataSource->getPrimaryKey()) > 1) {
            return new MultiPk($this, $key, $this->dataSource->getPrimaryKey());
        }
        if (!empty($this->filters)) {
            $primaryKey = $this->dataSource->getPrimaryKey();
            $data = $this->dataSource->fetchWhere(array_merge($this->filters, [$primaryKey[0] => $key]));
            return $this->entity->create(isset($data[0]) ? $data[0] : null, $this->relations);
        }
        return $this->entity->create($this->dataSource->fetch($key), $this->relations);
    }

    /**
     * Add data into the datasource
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        if ($value instanceof Relation) return;

        if(is_array($value)) $value = $this->convert($value);

        $visibility = new Visibility($value);
        $object = $visibility->getProperties();
        $object = $this->processFilters($object);
        $primaryKey = $this->dataSource->getPrimaryKey();

        if ($key !== null) $object->{$primaryKey[0]} = $key;

        $object = $this->entity->wrap($object, $this->relations);
        return $this->dataSource->save($object);
    }

    /**
     * Delete a record from the data source
     * @param  mixed
     * @return bool
     */
    public function delete($id) 
    {
        return $this->dataSource->delete($id);
    }

    /**
     * Check whether  record exists in the datasource
     * @param string|int $id
     * @return bool|MultiPk
     */
    public function exists($id) 
    {
        if (count($this->dataSource->getPrimaryKey()) > 1) {
            return new MultiPk($this, $offset, $this->dataSource->getPrimaryKey());
        }
        if (!empty($this->filters)) {
            $data = $this->dataSource->fetchWhere(array_merge($this->filters, [$this->dataSource->getPrimaryKey()[0] => $offset]));
            return isset($data[0]);
        }
        return (bool) $this->dataSource->fetch($offset);
    }

    /**
     * Get all records
     * @return array
     */
    public function fetch()
    {
        $this->wrapFilter();
        foreach ($this->filters as $name => &$filter) {
            if (isset($this->relations[$name])) {
                $this->relations[$name]->overwrite($filter, $filter[$name]);
            }
        }
        $results = $this->dataSource->fetchWhere($this->filters, $this->orderBy, $this->limit, $this->offset);
        foreach ($results as &$result) {
            $result = $this->entity->create($result, $this->relations);
        }
        return $results;
    }

    /**
     * Execute an aggregate function in the datasource
     * @param string $function
     * @param string  $field
     * @param string $group
     * @return mixed
     */
    public function aggregate($function, $field, $group = null) 
    {
        return $this->dataSource->aggregate($function, $field, $group, $this->filters);
    }

    /**
     * Delete multiple records from the datasource
     * @return bool
     */
    public function destroy() 
    {
        return $this->dataSource->deleteWhere($this->filters, $this->orderBy, $this->limit, $this->offset);
    }

    /**
     * Get datasource name
     * @return mixed
     */
    public function getName()
    {
        return $this->dataSource->getName();
    }

    /**
     * Get the primary key
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->dataSource->getPrimaryKey();
    }

    /**
     * Get traversable iterator
     * @return Iterator|\Traversable
     */
    public function getIterator() 
    {
        $results = $this->fetch();
        return new Iterator($results, $this->dataSource->getPrimaryKey());
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return bool|MultiPk
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * @param mixed $id
     */
    public function offsetUnset($id)
    {
        return $this->delete($id);
    }

    /**
     * @param mixed $offset
     * @return MultiPk|mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * gets the first element in the collection
     *
     */
    public function first()
    {
        $array = $this->fetch();
        if(!empty($array)){
            return reset($array);
        }
    }

    /**
     * gets the next element in the collection
     *
     */
    public function next()
    {
        $array = $this->fetch();
        if(!empty($array)){
            return next($array);
        }
    }

    /**
     * gets the last element in the collection
     *
     */
    public function last()
    {
        $array = $this->fetch();
        if(!empty($array)){
            return end($array);
        }
    }

    /**
     * Define One-to-One relation
     * @param Mapper $mapper
     * @param string $foreignKey
     * @param string $localKey
     * @return Mapper
     */
    public function hasOne(Mapper $mapper, $foreignKey, $localKey)
    {
        $relation = new OneToOne($mapper, $foreignKey, $localKey);
        $name = Inflector::singularize($mapper->getName());
        $this->addRelation($name, $relation);

    }

    /**
     * Define One-to-Many relation
     * @param Mapper $mapper
     * @param string $foreignKey
     * @param string $localKey
     * @return Mapper
     */
    public function hasMany(Mapper $mapper, $foreignKey, $localKey)
    {
        $relation = new OneToMany($mapper, $localKey, $foreignKey);
        $name = Inflector::pluralize($mapper->getName());
        $this->addRelation($name, $relation);

    }

    /**
     * Define Many-to-Many relation
     * @param Mapper $linkMapper
     * @param Mapper $relatedMapper
     * @param string $localKey
     * @param string $foreignKey
     * @return Mapper
     */
    public function belongsToMany(Mapper $linkMapper, Mapper $relatedMapper, $localKey, $foreignKey)
    {
        $relation = new ManyToMany($linkMapper, $relatedMapper, $localKey, $foreignKey);
        $name = Inflector::pluralize($linkMapper->getName());
        $this->addRelation($name, $relation);
        
    }

    /**
     * Write the filters back into the object being stored
     * @param mixed $value
     * @return mixed
     */
    private function processFilters($value) 
    {
        foreach ($this->filters as $key => $filterValue) {
            if (empty($value->$key) && !is_array($filterValue)) {
                $value->$key = $filterValue;
            }
        }
        return $value;
    }

    /**
     * Allow filter(['user' => $user]) where $user is an object instead of
     * filter(['userId' => $user->id])
     */
    private function wrapFilter()
    {
        foreach ($this->filters as $name => $value) {
            if (isset($this->relations[$name])) {
                $filter = $this->relations[$name]->getFilter($value);
                $this->filters = array_merge($this->filters, $filter);
                unset($this->filters[$name]);
            }
        }
    }

    /** Convert array to a simple object
     * @param array $array
     * @return \stdClass
     */
    private function convert(array $array)
    {
        $object = new \stdClass();
        foreach($array as $key => $value){
            $object->{$key} = $value;
        }
        return $object;
    }


}
