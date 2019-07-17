<?php

namespace Kaliba\ORM\Support;

class Iterator implements \Iterator 
{
    /**
     *
     * @var array
     */
    private $array;
    
    /**
     *
     * @var string
     */
    private $primaryKey;
    
    /**
     *
     * @var int
     */
    private $iterator = 0;

    public function __construct(array $array, $primaryKey) 
    {
        $this->array = $array;
        $this->primaryKey = $primaryKey;
    }

    public function current() 
    {
        return $this->array[$this->iterator];
    }

    public function key() 
    {
        if (count($this->primaryKey) == 1) {
            $primaryKey = end($this->primaryKey);
            return $this->array[$this->iterator]->$primaryKey;
        }
        else {
            $current = $this->current();
            return array_map(function($primaryKeyName) use ($current) {
                return $current->$primaryKeyName;
            }, $this->primaryKey);
        }
    }

    public function next() 
    {
        ++$this->iterator;
    }

    public function valid() 
    {
        return isset($this->array[$this->iterator]);
    }

    public function rewind() 
    {
        $this->iterator = 0;
    }
}
