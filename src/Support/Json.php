<?php

namespace Kaliba\Support;
use RuntimeException;

/**
 * Json Object
 * A wrapper of PHP json
 * This Object can encode and decode JsonObject, StdObject, stdClass, Array
 *
 * @author Brian Simpokolwe
 */
class Json
{   
    /**
     *
     * @var StdObject | Object | Array
     */
    protected $data;
    
    /**
     * Data
     * @param Json | StdObject | Object | Array $data
     */
    public function __construct($data) 
    {
        $this->data = $data;
    }
    
    /**
     * Wrapper function for php json_encode. Encodes to Json
     * @param int $options
     * @return string
	 * @throws RuntimeException
     */
    public function encode($options = 0)
    {
        $encoded = json_encode($this->data, $options);
        if ($encoded === false) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }
        return $encoded;
    }
    
    /**
     * Wrapper function for php json_decode. Encodes to Json
     * @param int $options
     * @return string
     * @throws RuntimeException
     */
    public function decode($assoc = false)
    {
        $decoded = json_decode($this->data, $assoc);
        if ($decoded === false) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }
        return $decoded;
    }
}
