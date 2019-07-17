<?php
namespace Kaliba\ORM\Support;
use Kaliba\ORM\Support\DateInjector;
use Closure;

class Visibility 
{
    /**
     *
     * @var Closure
     */
    private $readClosure;
    
    /**
     *
     * @var Closure
     */
    private $writeClosure;

    public function __construct($object) 
    {
        if ($object instanceof \stdclass) {
            $this->readClosure = function() use ($object) { 
                return $object;
            };
            $this->writeClosure = function ($field, $value) use ($object) {
                $object->$field = $value;
            };
        }
        else {
            $visOverride = $this;
            $this->readClosure = function() use ($visOverride) {
                return (object) array_filter(get_object_vars($this), [$visOverride, 'checkDataType']);
            };
            $this->readClosure = $this->readClosure->bindTo($object, $object);

            $this->writeClosure = function ($field, $value) { 
                $this->$field = $value;
            };
            $this->writeClosure = $this->writeClosure->bindTo($object, $object);
        }

    }

    public function checkDataType($value) 
    {
        return is_scalar($value) || is_null($value) || (is_object($value) && $value instanceof \DateTime);
    }

    public function getProperties() 
    {
        $closure = $this->readClosure;
        return $closure();
    }

    public function write($data) 
    {
        $closure = $this->writeClosure;
        if ($data != null) {
            foreach ($data as $key => $value) {
                $date = $this->date($value);
                $closure($key,  $date);
            }
        }
    }

    private function date($obj) 
    {
        $injector = new DateInjector;
        return $injector->replaceDates($obj);
    }
}
