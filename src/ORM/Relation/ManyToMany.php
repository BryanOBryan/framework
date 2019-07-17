<?php
namespace Kaliba\ORM\Relation;
use Kaliba\ORM\Contracts\Relation;
use Kaliba\ORM\Support\ManyIterator;
use Kaliba\ORM\Mapper;

class ManyToMany implements \IteratorAggregate, \ArrayAccess, \Countable, Relation
{
    /**
     *
     * @var mixed
     */
    private $results;
    
    /**
     *
     * @var string
     */
    private $localKey;

    /**
     *
     * @var string
     */
    private $foreignKey;

    /**
     *
     * @var mixed
     */
    private $otherInfo;

    /**
     *
     * @var Mapper
     */
    private $relatedMapper;

    /**
     *
     * @var Mapper
     */
    private $intermediateMapper;

    /**
     *
     * @var bool
     */
    private $autoTraverse = false;

    /**
     *
     * @var mixed
     */
    private $object;

    /**
     *
     * @var string
     */
    private $intermediateName;

    public function __construct(Mapper $intermediateMapper, Mapper $relatedMapper, $localKey, $foreignKey, $intermediateName = null)
    {
        $this->intermediateMapper = $intermediateMapper;
        $this->relatedMapper = $relatedMapper;
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        $this->autoTraverse = $intermediateName ? false : true;
        $this->intermediateName = $intermediateName ?: 'rel_' . $foreignKey;
        $this->intermediateMapper->addRelation($this->intermediateName, new One($this->relatedMapper, $foreignKey, $localKey));

    }

    public function getData($parentObject)
    {
        $this->object = $parentObject;
        return clone $this;
    }

    public function overwrite($parentObject, $data)
    {
        list($relatedField, $valueField, $mapper) = $this->getFieldInfo();
        $this->results = $data;
        $this->object = $parentObject;
        if (empty($parentObject->{$relatedField})) {
            return;
        }
        foreach ($data as $dt) {
            $this[] = $dt;
        }
    }

    public function count()
    {
        return count($this->getResults());
    }

    public function getIterator()
    {
        $iterator = $this->getResults()->getIterator();
        return new ManyIterator($iterator, $this->autoTraverse ? $this->intermediateName : null);
    }

    public function item($index)
    {
        return iterator_to_array($this->getIterator(), false)[$index];
    }

    public function offsetExists($name)
    {
        $items = $this->getResults()->filter([$this->foreignKey => $name]);

        return $items->getIterator()->valid();
    }

    public function offsetGet($name)
    {
        $items = $this->getResults()->filter([$this->foreignKey => $name]);

        return $items->getIterator()->current()->{$this->intermediateName};
    }

    public function offsetSet($name, $value)
    {
        list($relatedField, $valueField, $mapper) = $this->getFieldInfo();
        if ($this->autoTraverse) {
            $this->setAutotraverse($value, $relatedField, $valueField);
        }
        else if ($this->updateInterMapper($value, $relatedField, $valueField)) {
            $record = $value;
            $record->{$this->foreignKey} = $value->{$this->intermediateName}->{$this->localKey};
            $record->{$valueField} = $this->object->{$relatedField};
            if($this->isWritable()){
                $this->intermediateMapper[] = $record;
            }
        }
    }

    public function offsetUnset($id)
    {

    }

    public function destroy()
    {
        $this->getResults()->destroy();
    }

    private function getFieldInfo()
    {
        if ($this->otherInfo == null) {
            $propertyReader = function($name) {
                return $this->$name;
            };
            $reader = $propertyReader->bindTo($this->intermediateMapper, $this->intermediateMapper);
            foreach ($reader('relations') as $relation) {
                $propertyReader = $propertyReader->bindTo($relation, $relation);
                if ($propertyReader('foreignKey') != $this->foreignKey) {
                    $relation = $relation->getData($this->object);
                    $this->otherInfo = [$propertyReader('localKey'),  $propertyReader('foreignKey'), $propertyReader('mapper')];
                }
            }
        }
        return $this->otherInfo;
    }

    private function getResults()
    {
        list ($relatedField, $valueField, $relatedMapper) = $this->getFieldInfo();

        $x = $this->intermediateMapper->filter([$valueField => $this->object->{$relatedField}]);
        return $x;
    }

    private function updateInterMapper($record, $relatedField, $valueField)
    {
        return !(isset($record->{$this->foreignKey}) && isset($record->{$this->intermediateName}) &&
            $record->{$this->foreignKey} == $record->{$this->intermediateName}->{$this->localKey} &&
            $record->{$valueField} == $this->object->{$relatedField});
    }

    private function setAutotraverse($value, $relatedField, $valueField)
    {
        $record = new \stdClass;
        $record->{$this->foreignKey} =  $value->{$this->localKey};
        $record->{$valueField} = $this->object->{$relatedField};
        $this->intermediateMapper[] = $record;
    }

   
}
