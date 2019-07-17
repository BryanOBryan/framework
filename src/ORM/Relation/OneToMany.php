<?php 
namespace Kaliba\ORM\Relation;
use Kaliba\ORM\Contracts\Relation;
use Kaliba\ORM\Mapper;

class OneToMany implements Relation
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
     * Many constructor.
     * @param Mapper $mapper
     * @param string $foreignKey
     * @param string $localKey
     * @param array $filter
     */
    public function __construct(Mapper $mapper, $foreignKey, $localKey, array $filter = [])
    {
        if($filter) {
            $mapper->filter($filter);
        }
        $this->mapper = $mapper;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getData($parentObject)
    {
        $foreignKey = $this->foreignKey;
        $localKey = $this->localKey;
        $foreignKeyValue = $parentObject->{$foreignKey};
        if (!isset($foreignKeyValue)) {
            $mapper = $this->mapper;
        } else {
            $mapper = $this->mapper->where($localKey, $foreignKeyValue);
        }
        return $mapper;
    }

    public function overwrite($key, $mapper)
    {
        $foreignKey = $this->foreignKey;
        $localKey = $this->localKey;
        if (!isset($key->{$foreignKey})) {
            return false;
        }
        foreach ($mapper as $k => $val) {
            if (!empty($val->{$localKey}) && $val->{$localKey} == $key->{$foreignKey}) {
                continue;
            }
            $val->{$localKey} = $key->{$foreignKey};

            $this->mapper->save($val);

        }
    }

}