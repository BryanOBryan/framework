<?php

namespace Kaliba\Http\Helpers;

/**
 * ServerBag is a container for HTTP headers from the $_SERVER variable.
 * This class is derived from Symphony Http Foundation
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ServerBag extends ParameterBag
{
    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];
    
    /**
     * Gets the HTTP headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = array();
       
        foreach ($this->parameters as $key => $value) {
            // capitalize key
            $key = strtoupper($key);
            // check if key is prefixed by HTTP
            if (strpos($key, 'HTTP_') === 0) {
                // remove the HTTP prefix from the key
                $headerkey = substr($key, 5);
                $headers[$headerkey] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (isset(static::$special[$key])) {
                $headers[$key] = $value;
            }
        }
        
        return $headers;
    }
}
