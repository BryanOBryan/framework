<?php


namespace Kaliba\View;

class ViewBag implements \ArrayAccess
{
    /**
     * @var array
     */
    private  $data= [];

    /**
     * ViewBag constructor.
     * @param array $data
     */
    public function __construct( array $data = [] )
    {
        $this->add($data);
    }

    /**
     * adds a new element to the collection
     * @param int|string $key
     * @param mixed $value
     */
    public function add($key, $value=null)
    {
        if(is_array($key) && empty($value)){
            foreach($key as $item => $val){
                $this->data[$item]= $val;
            }
        }else{
            $this->data[$key] = $value;
        }
    }

    /**
     * removes an element from the collection
     * @param $key mixed
     * @return void
     */
    public function remove($key)
    {
        if($this->has($key)){
            unset($this->data[$key]);
        }else{
            foreach($this->data as $name=>$value){
                if($key == $value){
                    unset($this->data[$name]);
                }
            }
        }
    }

    /**
     * checks if an element or key exists in the collection
     * @param mixed $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->data[$key])?true:false;
    }

    /**
     * gets an element from the collection
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * returns the length of the collection
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Remove all array from collection
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * get all array and return an array
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * checks if an element or key exists in the collection
     * @param type $element
     * @return boolean
     */
    public function offsetExists($key)
    {
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
        $this->add($key, $value);
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

}