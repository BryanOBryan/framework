<?php
namespace Kaliba\ORM\Support;
use Kaliba\ORM\Mapper;

class Entity 
{
    /**
     *
     * @var string
     */
    private $className;

    /**
     *
     * @var Mapper
     */
    private $parent;

    /**
     * Entity constructor.
     * @param Mapper $parent
     * @param string $className
     */
    public function __construct(Mapper $parent, $className = null)
    {
        $this->parent = $parent;
        $this->className = $className;
    }

    public function create($data = [], $relations = [])
    {
        $object = (is_callable($this->className)) ? call_user_func($this->className) : new $this->className;
        $writer = new Visibility($object);
        $writer->write($data);
        return $this->wrap($object, $relations);
    }

    public function wrap($object, array $relations)
    {
        //see if any relations need overwriting
        foreach ($relations as $name => $relation) {
            $this->addRelationData($object, $name, $relation);
        }
        return $object;
    }

    private function addRelationData($object, $name, $relation)
    {
        if (isset($object->$name) && !($object->$name instanceof Relation) ) {
           $relation->overwrite($object, $object->$name);
        }
        $object->$name = $relation->getData($object);
    }
}
