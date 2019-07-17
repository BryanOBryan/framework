<?php

namespace Kaliba\Configure;
use Kaliba\Contracts\Configure\ParserInterface;
use InvalidArgumentException;

class ParserFactory
{
    /**
     * List of supported drivers
     * @var array
     */
    private $drivers = [
        'php'   =>  Parsers\PhpParser::class,
        'ini'   =>  Parsers\IniParser::class,
        'json'  =>  Parsers\JsonParser::class,
    ];

    /**
     * @param string $name Parser driver to be created. (ini, php, json)
     * @param string $source File path of the configuration file
     * @return ParserInterface 
     */
    public function create($name, $source=null) {
        if(isset($this->drivers[$name])){
            $driver = $this->drivers[$name];
            $instance = new $driver($source);
            return $instance;
        }
    }

}
