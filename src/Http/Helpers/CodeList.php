<?php

namespace Kaliba\Http\Helpers;

final class CodeList
{
    /**
     * Status codes and reason phrases
     *
     * @var array
     */
    private $codeList = array(
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',        
        451 => 'Unavailable For Legal Reasons',        
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Authentication Required',
        // Framework Error 6xx
        600 => 'Invalid CSRF Token',
        601 => 'Posted Data Too Large',
        
    );
    
   /**
     * Get code phrase
     *
     * @param int $code a 3 digit Http code
     *
     * @return string
     */
    public function getPhrase($code)
    {
        if($this->exists($code)){
            return $this->codeList[$code];
        }        
    }
     
    /**
     * Get list of codes and their corresponding phrases
     * @return array
     */
    public function getCodes()
    {
        return $this->codeList;
    }	

    /**
     * Check if code exists
     *
     * @param int $code a 3 digit Http code
     *
     * @return bool
     */
    public function exists($code)
    {
        return isset($this->codeList[$code])?true:false;
    }
    
    
}
