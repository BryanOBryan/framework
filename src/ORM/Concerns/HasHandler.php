<?php

namespace Kaliba\ORM\Concerns;
use Kaliba\ORM\DataSource\Database;
use Kaliba\ORM\Mapper;

trait HasHandler
{
    /**
     *
     * @var Mapper
     */
    protected static $handlers = [];

    /**
     * Set Object Relation mapper
     * @param string $name
     * @param Mapper $mapper
     */
    public static function setHandler($name, Mapper $mapper)
    {
       static::$handlers[$name] = $mapper;
    }

    /**
     * Get Object Relation Mapper
     * @param string $name
     * @return Mapper
     */
    public static function getHandler($name)
    {
        return static::$handlers[$name];
    }

}