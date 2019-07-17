<?php

namespace Kaliba\Collection;

class Collection  implements \ArrayAccess, \JsonSerializable, \Countable, \IteratorAggregate
{
    /**
     *
     * @var array
     */
    protected $array = array();

    /**
     * Collection constructor.
     * @param array $array items in the collection (optional)
     */
    public function __construct(array $array = []) 
    {
        if(!empty($array)){
            $this->array = $array;
        }
    }

    /**
     * adds a new element to the collection
     * @param mixed $element element to be added to the collection
     * @param int|string $key optional item key
     */
    public function add($element, $key=null) {
        $array = [];
        array_push($array, $element);
        foreach ($array as $element) {
            if(!isset($key)) { 
                $this->array[] = $element;
            }
            elseif(!$this->has($element)) {
                $this->array[$key] = $element;    
            }
        }
        return $this;
       
    }
    
    /**
     * adds a new element to the collection
     * @param int|string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->add($value, $key);
        return $this;
    }
    
    /**
     * gets an element from the collection
     * @param mixed $key
     * @param mixed $default 
     * @return string|int|object
     */
    public function get($key, $default=null)
    {
        if(empty($key)){
            return $this->first();
        }
        elseif ($this->has($key)) {
            return $this->array[$key];
        }    
        return $default;
    }
    
    /**
     * get all array and return an array
     * @return array
     */
    public function toArray()
    {
        return $this->array;
    }

    /**
     * get all array and return an array
     * @return array
     */
    public function all()
    {
        return $this->array;
    }

    /**
     * removes an element from the collection
     * @param $key mixed
     * @return self
     */
    public function remove($key)
    {
        if($this->has($key)){
            unset($this->array[$key]);
        }else{
            foreach($this->array as $name=>$value){
                if($key == $value){
                    unset($this->array[$name]);
                }
            }
        }
        return $this;
    }
    
    /**
     * returns the length of the collection
     * @return int
     */
    public function count()
    {
        return count($this->array);
    }
    
    /**
     * returns the length of the collection
     * @return int
     */
    public function length()
    {
        return sizeof($this->array);
    }

    /**
     * returns the keys in the collection
     * @return array
     */
    public function keys()
    {
        return array_keys($this->array);
    }  
    
    /**
     * checks if an element or key exists in the collection
     * @param mixed $key
     * @return boolean
     */
    public function has($key)
    {
       return isset($this->array[$key])?true:false;
    }
    
    /**
    * gets the first element in the collection
    *
    */
    public function first()
    {
        if(!empty($this->array)){
            return reset($this->array);
        }
       
    }
    
    /**
    * gets the next element in the collection
    *
    */
    public function next()
    {
        if(!empty($this->array)){
            return next($this->array);
        }
    }
    
    /**
    * gets the previous element in the collection
    *
    */
    public function previous()
    {
        if(!empty($this->array)){
            return prev($this->array);
        }
    }
    
    /**
    * gets the current element in the collection
    *
    */
    public function current()
    {
        if(!empty($this->array)){
            return current($this->array);
        }
    }
    
    /**
    * gets the last element in the collection
    *
     */
    public function last()
    {
        if(!empty($this->array)){
            return end($this->array);
        }
    }
	
    /**
     * Add an element to collection
     *
     * @param array $items Key-value array of data to append to this collection
     */
    public function replace(array $items)
    {
        $this->clear();
        $this->add($items);
        return $this;
    }
	
    /**
     * Remove all array from collection
     */
    public function clear()
    {
        $this->array = [];
        return $this;
    }

    /**
     * Sort the collection in ascending order
     * @return \self
     */
    public function ascend()
    {
        array_multisort($this->array, SORT_ASC);
        return $this;
    }

    /**
     * Sort the collection in descending order
     * @return \self
     */
    public function descend()
    {
        array_multisort($this->array, SORT_DESC);
        return $this;
    }

    /**
     * Slice the collection from a specific position and retrieve the sliced members
     * @param int $offset
     * @param int $length
     * @return \self
     */
    public function slice($offset, $length)
    {
        $members = array_slice($this->array, $offset, $length);
        $self = new self($members);
        return $self;

    }

    /**
     * checks if an element or key exists in the collection
     * @param type $element
     * @return boolean
     */
    public function offsetExists($key){
        return $this->has($key);
    }

    /**
     * gets an element from the collection
     * @param mixed
     * @param mixed  $default The default value if the parameter key does not exist
     * @return string|int|object
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * adds a new element to the collection
     * @param int|string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->add($value, $key);
    }

    /**
     * removes an element from the collection
     * @param string|int|object
     * @return string|int|object
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * @return CollectionIterator|\Traversable
     */
    public function getIterator()
    {
        return new CollectionIterator($this);
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
    
}
