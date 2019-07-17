<?php
namespace Kaliba\ORM\Relation;
use Kaliba\ORM\Contracts\Relation;
use Kaliba\ORM\Mapper;

class OneToOne implements Relation
{
    /**
     *
     * @var Mapper
     */
    private $mapper;
    
    /**
     *
     * @var string
     */
    private $foreignKey;

    /**
     *
     * @var string
     */
    private $localKey;

    /**
     *
     * @var mixed
     */
    private $data;


    /**
     * One constructor.
     * @param Mapper $mapper
     * @param string $foreignKey
     * @param string $localKey
     * @param array $filter
     */
    public function __construct(Mapper $mapper, $foreignKey, $localKey, array $filter = [])
    {
        if ($filter) {
            $mapper->filter($filter);
        }
        $this->mapper = $mapper;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getData($parentObject)
	{
	    $foreignKey = $this->foreignKey;
	    $foreignKeyValue = $parentObject->$foreignKey;
		return $this->mapper->get($foreignKeyValue);
	}

	public function overwrite($parentObject, $data)
	{
	    $this->mapper->save($data);
        if (!isset($parentObject->{$this->foreignKey}) || $parentObject->{$this->foreignKey} != $data->{$this->localKey}) {
            $parentObject->{$this->foreignKey} = $data->{$this->localKey};
            return true;
        }

	}

	public function getFilter($object)
	{
		return [$this->foreignKey => $object->{$this->localKey}];
	}



}
