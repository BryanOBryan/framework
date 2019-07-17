<?php

namespace Kaliba\Http;
use Kaliba\Http\Contracts\ServerRequestInterface;
use Kaliba\Http\Contracts\StreamInterface;
use Kaliba\Http\Contracts\UriInterface;
use Kaliba\Http\Helpers\ParameterBag;
use Kaliba\Http\Helpers\ServerBag;
use Kaliba\Http\Helpers\HeaderBag;
use Kaliba\Http\Helpers\FileBag;
use Kaliba\Http\Helpers\RequestBody;
use Kaliba\Http\Helpers\IpAddress;
use InvalidArgumentException;
use RuntimeException;
use Closure;

/**
 * Request
 *
 * This class represents an HTTP request. It manages
 * the request method, URI, headers, cookies, and body
 * according to the PSR-7 standard.
 *
 * This class is adopted from Slim, Symphony, Laravel, and CakePHP 
 */
class Request extends Message implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected static $trustedProxies = array();

    /**
     * @var array
     */
    protected static $trustedHostPatterns = array();

    /**
     * @var array
     */
    protected static $trustedHosts = array();

    /**
     * Names for headers that can be trusted when
     * using trusted proxies.
     *
     * The FORWARDED header is the standard as of rfc7239.
     *
     * The other headers are non-standard, but widely used
     * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
     * @var array 
     */
    protected static $trustedHeaders = array(
        self::HEADER_FORWARDED => 'FORWARDED',
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    );
    
    /**
     * The original request method (ignoring override)
     *
     * @var string
     */
    protected $originalMethod;
       
    /**
     * Custom parameters.
     *
     * @var \Kaliba\Http\Helpers\ParameterBag
     */
    protected $attributes;

    /**
     * Query string parameters
     *
     * @var string|array
     */
    protected $query;
    
    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * @var \Kaliba\Http\Helpers\ServerBag
     */
    public $server;

    /**
     * Uploaded files ($_FILES).
     *
     * @var \Kaliba\Http\Helpers\FileBag
     */
    public $files;

    /**
     * Cookies ($_COOKIE).
     *
     * @var \Kaliba\Http\Helpers\ParameterBag
     */
    public $cookies;
	
    /**
     * Request Headers
     *
     * @var \Kaliba\Http\Helpers\HeaderBag
     */
    public $headers;
    
    /**
     * Data ($_GET OR $_POST).
     *
     * @var \Kaliba\Http\Helpers\ParameterBag
     */
    public $data;
    
    /**
     * The request method
     *
     * @var string
     */
    protected $method;

    /**
     * The request URI object
     *
     * @var \Kaliba\Http\Uri;
     */
    protected $uri;

    /**
     * The request URI target (path + query string)
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * The request body parsed (if possible) into a PHP array or object
     *
     * @var null|array|object
     */
    protected $bodyParsed = false;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     *
     * @var callable[]
     */
    protected $bodyParsers = [];

    /**
     * Valid request methods
     *
     * @var string[]
     */
    protected $validMethods = [
        self::METHOD_HEAD   =>  1,
        self::METHOD_GET    =>  1,
        self::METHOD_POST   =>  1,
        self::METHOD_PUT    =>  1,
        self::METHOD_OPTIONS=>  1,
        self::METHOD_PATCH  =>  1,
        self::METHOD_PURGE  =>  1,
        self::METHOD_DELETE =>  1,
        self::METHOD_TRACE =>   1
    ];

    private static $instance = null;
    
    /**
     * Creates a new HTTP request. 
     * The Request can either be an Incoming request(Server Request) or Outgoing request (Client request)
     * When no argument is passed to the constructor, an Instance of an Incoming request (Server request) is created from PHP Super global variables
     * ($_SERVER, $_COOKIE, $_POST, $_GET, $_FILES)
     *
     * @param UriInterface|string     $uri    The request URI object or URI String
     * @param string           $method        The request method (GET, POST, PUT, DELETE)
     * @param array            $headers       The request headers collection
     * @param StreamInterface  $body          The request body object
     * @param array            $cookies       The request cookies collection
     * @param array            $server        The server environment variables  
     * @param array            $files The request uploadedFiles collection
     * @return \Kaliba\Http\Request 
     */
    public static function instance($uri=null,$method=null, array $headers=[] ,Stream $body=null, array $cookies=[], array $server=[], array $files = [])
    {
        if(!static::$instance instanceof self){
            static::$instance = new static($uri ,$method,$headers, $body, $cookies, $server, $files);
        }
        return static::$instance;
    }
    
    /**
     * Creates a new HTTP request. 
     * The Request can either be an Incoming request(Server Request) or Outgoing request (Client request)
     * When no argument is passed to the constructor, an Instance of an Incoming request (Server request) is created from PHP Super global variables
     * ($_SERVER, $_COOKIE, $_POST, $_GET, $_FILES)
     *
     * @param UriInterface|string     $uri    The request URI object or URI String
     * @param string           $method        The request method (GET, POST, PUT, DELETE)
     * @param array            $headers       The request headers collection
     * @param StreamInterface  $body          The request body object
     * @param array            $cookies       The request cookies collection
     * @param array            $server        The server environment variables  
     * @param array            $files The request uploadedFiles collection
     * @return \Kaliba\Http\Request 
     */
    public function __construct($uri=null, $method=null, array $headers=[] ,Stream $body=null, array $cookies=[], array $server=[], array $files = [])
    {       
        $this->initialize( $uri ,$method,$headers, $body, $cookies, $server, $files);
        $this->setMediaTypeParsers();
    }
   
    /**
     * Initialize new HTTP request. 
     * When no argument is passed to the constructor, the new instance is created from PHP Super global variables
     * $_SERVER, $_COOKIE, $_POST, $_GET, $_FILES
     * @param UriInterface|string     $uri    The request URI object or URI String
     * @param string           $method        The request method (GET, POST, PUT, DELETE)
     * @param array            $headers       The request headers collection
     * @param StreamInterface  $body          The request body object
     * @param array            $cookies       The request cookies collection
     * @param array            $server        The server environment variables  
     * @param array            $files The request uploadedFiles collection
     */
    protected function initialize($uri, $method=null,  array $headers=[] ,Stream $body=null, array $cookies=[], array $server=[], array $files = [])
    {
        if(is_string($uri)){
            $this->uri = Uri::createFromString($uri);
        }
        elseif($uri instanceof UriInterface){
            $this->uri = $uri;
        }
        elseif (empty($uri)) {
            $this->uri = Uri::createFromServer($_SERVER);
        } 
        $this->cookies          =   !empty($cookies)    ?   new ParameterBag($cookies)              :   new ParameterBag($_COOKIE);
        $this->files            =   !empty($files)      ?   new FileBag($files)                     :   new FileBag($_FILES);
        $this->server           =   !empty($server)     ?   new ServerBag(self::mock($server))      :   new ServerBag($_SERVER);
        $this->body             =   !empty($body)       ?   $body                                   :   new RequestBody();
        $this->headers          =   !empty($headers)    ?   new HeaderBag($headers)                 :   new HeaderBag($this->server->getHeaders());
        $this->originalMethod   =   !empty($method)     ?   $this->filterMethod($method)            :   $this->filterMethod( $this->server->get('REQUEST_METHOD') ); 
        $this->attributes       =   new ParameterBag(array());
        $this->bodyParsed       =   ($this->isPost() && $this->acceptType('application/x-www-form-urlencoded') || $this->acceptType('multipart/form-data') ) ? : $_POST;
        $this->data             =   new ParameterBag($this->data());
        if ( $this->server->has('SERVER_PROTOCOL' ) ) {
            $this->protocolVersion = str_replace('HTTP/', '', $this->server->get('SERVER_PROTOCOL'));
        }
        if (!$this->hasHeader('Host') || $this->getHost() !== ''){
            $this->setHeader('Host', $this->getHost());
        }
    }

    /**
     * Factory method for chainability.
     * 
     * @param UriInterface|string     $uri    The request URI object or URI String
     * @param string           $method        The request method (GET, POST, PUT, DELETE)
     * @param array            $headers       The request headers collection
     * @param StreamInterface  $body          The request body object
     * @param array            $cookies       The request cookies collection
     * @param array            $server        The server environment variables  
     * @param array            $files The request uploadedFiles collection
     * @return \Kaliba\Http\Request 
     */
    public static function create($uri=null,$method=null, array $headers=[] ,Stream $body=null, array $cookies=[], array $server=[], array $files = [])
    {
        return new static( $uri, $method, $headers, $body, $cookies, $server, $files);
    }
    
    /**
     * This method is applied to the cloned object
     * after PHP performs an initial shallow-copy. This
     * method completes a deep-copy by creating new objects
     * for the cloned object's internal reference pointers.
     */
    public function __clone()
    {	
        $this->uri = clone $this->uri;
        $this->headers = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body = clone $this->body;        
        $this->server = clone $this->server;
        $this->data = clone $this->data;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
    }

    /**
     * initialize Content Media Parsers
     */
    protected function setMediaTypeParsers()
    {
        
        $this->registerMediaTypeParser('application/json', function ($input) {
            return json_decode($input, true);
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });
    }
    
    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = $this->originalMethod;
            $customMethod = $this->getHeaderLine('X-Http-Method-Override');

            if ($customMethod) {
                $this->method = $this->filterMethod($customMethod);
            } elseif ($this->originalMethod === 'POST') {
                $body = $this->getParsedBody();

                if (is_object($body) && property_exists($body, '_METHOD')) {
                    $this->method = $this->filterMethod((string)$body->_METHOD);
                } elseif (is_array($body) && isset($body['_METHOD'])) {
                    $this->method = $this->filterMethod((string)$body['_METHOD']);
                }

                if ($this->getBody()->eof()) {
                    $this->getBody()->rewind();
                }
            }
        }

        return $this->method;
    }
  
    /**
     * Get the original HTTP method (ignore override).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string
     */
    public function getOriginalMethod()
    {
        return $this->originalMethod;
    }
    
    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method = $this->filterMethod($method);
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (!isset($this->validMethods[$method])) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return $method;
    }

    /**
     * Does this request use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string|array $method HTTP method
     * @return bool
     */
    public function isMethod($method)
    {
        $methods = [];
        if(is_array($method)){
            $methods = $method;
        }elseif(is_string($method)){
            $methods[] = $method;
        }elseif(func_num_args() > 1){
            $methods = (array)  func_get_args();
        }
        foreach ($methods as $key) {
            if($this->getMethod() == $key){
                return true;
            }
        }           
        return false;
        
    }

    /**
     * Is this a GET request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Checks whether the method is safe or not.
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isMethodSafe()
    {
        return in_array($this->getMethod(), array(self::METHOD_GET, self::METHOD_HEAD));
    }

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isAjax()
    {
        return 'XMLHttpRequest' == $this->getHeader('X-Requested-With');
    }

    /**
     * Determine if the request is the result of an PJAX call.
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isPjax()
    {
        return $this->getHeader('X-PJAX') == true;
    }


    /**
     * @return bool
     */
    public function isNoCache()
    {
        return $this->headers->hasCacheControlDirective('no-cache') || 'no-cache' == $this->headers->get('Pragma');
    }

    /**
     * Checks whether the request is secure or not.
     *
     * This method can read the client protocol from the "X-Forwarded-Proto" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
     * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-proto" key.
     *
     * @return bool
     */
    public function isSecure()
    {
        if ($this->isFromTrustedProxy() && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && $proto = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO])) {
            return in_array(strtolower(current(explode(',', $proto))), array('https', 'on', 'ssl', '1'));
        }

        $https = $this->server->get('HTTPS');

        return !empty($https) && strtolower($https) =='on';
    }
       
    /*******************************************************************************
     * OTHER CONVINIENCE METHODS
     ******************************************************************************/
    
     /**
     * Find out which content types the client accepts or check if they accept a
     * particular type of content.
     *
     *
     * This method will order the returned content types by the preference values indicated
     * by the client.
     *
     * @param string|null $type The content type to check for. Leave null to get all types a client accepts.
     * @return mixed Either an array of all the types the client accepts or a boolean if they accept the
     *   provided type.
     */
    public function acceptType($type = null)
    {
        $raw = $this->parseAcceptWithQualifier($this->getHeader('accept'));
        $accept = [];
        foreach ($raw as $types) {
            $accept = array_merge($accept, $types);
        }
        if ($type === null) {
            return $accept;
        }
        return in_array($type, $accept);
    }
    
    /**
     * Get the languages accepted by the client, or check if a specific language is accepted.
     *
     * Get the list of accepted languages:
     *
     * ```
     * $this->request->acceptLanguage();
     * ```
     *
     * Check if a specific language is accepted:
     *
     * ```
     * $this->request->acceptLanguage('es-es');
     * ```
     *
     * @param string|null $language The language to test.
     * @return mixed If a $language is provided, a boolean. Otherwise the array of accepted languages.
     */
    public function acceptLanguage($language = null)
    {
        $raw = $this->parseAcceptWithQualifier($this->getheader('accept-language'));
        $accept = [];
        foreach ($raw as $languages) {
            foreach ($languages as &$lang) {
                if (strpos($lang, '_')) {
                    $lang = str_replace('_', '-', $lang);
                }
                $lang = strtolower($lang);
            }
            $accept = array_merge($accept, $languages);
        }
        if ($language === null) {
            return $accept;
        }
        return in_array(strtolower($language), $accept);
    }
     
    /**
     * Determines whether a request accepts JSON.
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function acceptsJson()
    {
        return $this->acceptType('application/json');
    }

    /**
     * Determines whether a request accepts HTML.
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function acceptsHtml()
    {
        return $this->acceptType('text/html');
    }
	
    /**
     * Determines whether a request accepts XML.
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function acceptsXml()
    {
        return $this->acceptType('application/xml');
    }
       
    /**
     * Returns the client IP addresses.
     *
     * In the returned array the most trusted IP address is first, and the
     * least trusted one last. The "real" client IP address is the last one,
     * but this is also the least trusted one. Trusted proxies are stripped.
     *
     * Use this method carefully; you should use getClientIp() instead.
     *
     * @return array The client IP addresses
     *
     * @see getClientIp()
     */
    public function getClientIps()
    {
        $clientIps = array();
        $ip = $this->server->get('REMOTE_ADDR');

        if (!$this->isFromTrustedProxy()) {
            return array($ip);
        }

        if (self::$trustedHeaders[self::HEADER_FORWARDED] && $this->headers->has(self::$trustedHeaders[self::HEADER_FORWARDED])) {
            $forwardedHeader = $this->headers->get(self::$trustedHeaders[self::HEADER_FORWARDED]);
            preg_match_all('{(for)=("?\[?)([a-z0-9\.:_\-/]*)}', $forwardedHeader, $matches);
            $clientIps = $matches[3];
        } elseif (self::$trustedHeaders[self::HEADER_CLIENT_IP] && $this->headers->has(self::$trustedHeaders[self::HEADER_CLIENT_IP])) {
            $clientIps = array_map('trim', explode(',', $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_IP])));
        }

        $clientIps[] = $ip; // Complete the IP chain with the IP the request actually came from
        $firstTrustedIp = null;

        foreach ($clientIps as $key => $clientIp) {
            // Remove port (unfortunately, it does happen)
            if (preg_match('{((?:\d+\.){3}\d+)\:\d+}', $clientIp, $match)) {
                $clientIps[$key] = $clientIp = $match[1];
            }

            if (!filter_var($clientIp, FILTER_VALIDATE_IP)) {
                unset($clientIps[$key]);

                continue;
            }

            if (IpAddress::checkIp($clientIp, self::$trustedProxies)) {
                unset($clientIps[$key]);

                // Fallback to this when the client IP falls into the range of trusted proxies
                if (null ===  $firstTrustedIp) {
                    $firstTrustedIp = $clientIp;
                }
            }
        }

        // Now the IP chain contains only untrusted proxies and the client IP
        return $clientIps ? array_reverse($clientIps) : array($firstTrustedIp);
    }

    /**
     * Returns the client IP address.
     *
     * This method can read the client IP address from the "X-Forwarded-For" header
     * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
     * header value is a comma+space separated list of IP addresses, the left-most
     * being the original client, and each successive proxy that passed the request
     * adding the IP address where it received the request from.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-For",
     * ("Client-Ip" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-ip" key.
     *
     * @return string The client IP address
     *
     * @see getClientIps()
     * @see http://en.wikipedia.org/wiki/X-Forwarded-For
     */
    public function getClientIp()
    {
        $ipAddresses = $this->getClientIps();

        return $ipAddresses[0];
    }    
             
    /**
     * Gets the Etags.
     *
     * @return array The entity tags
     */
    public function getETags()
    {
        return preg_split('/\s*,\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY);
    }
		   
    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        if ($this->uri === null) {
            return '/';
        }
        $basePath = $this->uri->getBasePath();
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path, '/');

        $query = $this->uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }
        $this->requestTarget = $path;

        return $this->requestTarget;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     * @throws InvalidArgumentException if the request target is invalid
     */
    public function withTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $clone->headers->set('Host', $uri->getHost());
            }
        } else {
            if ($this->uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeader('Host') === null)) {
                $clone->headers->set('Host', $uri->getHost());
            }
        }

        return $clone;
    }
        
    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies->all();
    }
    
    /**
     * Retrieve cookie.
     * Retrieves cookies sent by the client to the server.
     * 
     * @param string $name
     */
    public function getCookie($name)
    {
        return $this->cookies->get($name);
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies->add($cookies);

        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Kaliba\Http\Interfaces\UploadFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadFiles()
    {
        return $this->files->all();
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadFiles(array $uploadedFiles)
    {
        $clone = clone $this;
        $clone->files->add($uploadedFiles);

        return $clone;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->server->all();
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes->all();
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     * @throws RuntimeException if the request body media type parser returns an invalid value
     */
    public function getParsedBody()
    {
        if ($this->bodyParsed ) {
            if(is_array($this->bodyParsed)){
                return (array)  $this->bodyParsed;
            }else{
                return $this->bodyParsed;	
            }   
        }
        if (!$this->body) {
            return null;
        }
        $mediaType = $this->getMediaType();
        $body = (string)$this->getBody();
        if (isset($this->bodyParsers[$mediaType])) {
            $parsed = $this->bodyParsers[$mediaType]($body);
            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }
        return null;
    }
   
    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) 
        {
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    /**
     * Register media type parser.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string   $mediaType A HTTP media type (excluding content-type
     *     params).
     * @param callable $callable  A callable that returns parsed contents for
     *     media type.
     */
    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string)$mediaType] = $callable;
    }
    
    /**
     * Fetch request parameter value from body or query string (in that order).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $key The parameter key.
     * @return mixed The parameter value.
     */
    public function get($key)
    {
        $postParams = $this->getParsedBody();
        $getParams = $this->getQueryParams();
        $result = null;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return trim($result);
    }
    
    /**
     * Retrieve normalized uploaded file .
     * 
     * Note: This method is not part of the PSR-7 standard.
     * 
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadFiles().
     *
     * @return UploadFile  UploadedFile instances
     */
    public function file($name)
    {
        return $this->files->get($name);
    }
    
    /**
     * Fetch associative array of body or JSON  and query  string parameters.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return array
     */
    public function data()
    {
     
        $params = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        if ($postParams) {
            $params = array_merge($params, (array)$postParams);
        }

        return array_map('trim', $params);
    }   	
    
    /**
     * Check whether a submitted parameter is empty
     * @param string $key Parameter name
     * @return bool
     */
    public function isEmpty($key)
    {
        $value = $this->get($key);
        return empty($value)? true: false;
    }
    
    /**
     * Check whether a submitted param is set
     * @param string $key Parameter name
     * @return bool
     */
    public function contains($key)
    {
        $value = $this->get($key);
        return !empty($value)? true: false;
    }
    
    /**
     * Fetch parameter value from request body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) 
        {
            $result = $postParams[$key];
        } 
        elseif (is_object($postParams) && property_exists($postParams, $key)) 
        {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * Fetch parameter value from query string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    public function getQueryParam($key, $default = null)
    {
        $getParams = $this->getQueryParams();
        $result = $default;
        if (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if (is_array($this->query)) {
            return $this->query;
        }
        if ($this->uri === null) {
            return [];
        }
        parse_str($this->uri->getQuery(), $this->query); // <-- URL decodes data
        return $this->query;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    } 
	 
    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQueryString()
    {
        return $this->uri->getQuery();
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new InvalidArgumentException('Uri query must be a string');
        }
        $query = ltrim((string)$query, '?');
        $clone = clone $this;
        $clone->query = $this->filterQuery($query);

        return $clone;
    }

    /**
     * Filters the query string or fragment of a URI.
     *
     * @param string $query The raw uri query string.
     * @return string The percent-encoded query string.
     */
    protected function filterQuery($query)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }
   
    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->uri->getScheme();
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
       return $this->uri->getAuthority();
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->uri->getUserInfo();
    }

    /**
     * Retrieve the User Only .
     * @return string User.
     */
    public function getUser(){
        return $this->uri->getUser();
    }
    
    /**
     * Retrieve the Password Only .
     * @return string User.
     */
    public function getPassword(){
        return $this->uri->getPassword();
    }
    
    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->uri->getHost();
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->uri->getPort();
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->uri->getPath();
    }
    
    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->uri->getFragment();
    }

    /**
     * Return the fully qualified base URL.
     *
     * Note that this method never includes a trailing /
     *
     * This method is not part of PSR-7.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->uri->getBaseUrl();
    }
        	
    /**
     * Sets a list of trusted proxies.
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * @param array $proxies A list of trusted proxies
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
    }

    /**
     * Gets the list of trusted proxies.
     *
     * @return array An array of trusted proxies.
     */
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }

    /**
     * Sets a list of trusted host patterns.
     *
     * You should only list the hosts you manage using regexs.
     *
     * @param array $hostPatterns A list of trusted host patterns
     */
    public static function setTrustedHosts(array $hostPatterns)
    {
        self::$trustedHostPatterns = array_map(function ($hostPattern) {
            return sprintf('#%s#i', $hostPattern);
        }, $hostPatterns);
        // we need to reset trusted hosts on trusted host patterns change
        self::$trustedHosts = array();
    }

    /**
     * Gets the list of trusted host patterns.
     *
     * @return array An array of trusted host patterns.
     */
    public static function getTrustedHosts()
    {
        return self::$trustedHostPatterns;
    }

    /**
     * Sets the name for trusted headers.
     *
     * The following header keys are supported:
     *
     *  * Request::HEADER_CLIENT_IP:    defaults to X-Forwarded-For   (see getClientIp())
     *  * Request::HEADER_CLIENT_HOST:  defaults to X-Forwarded-Host  (see getHost())
     *  * Request::HEADER_CLIENT_PORT:  defaults to X-Forwarded-Port  (see getPort())
     *  * Request::HEADER_CLIENT_PROTO: defaults to X-Forwarded-Proto (see getScheme() and isSecure())
     *
     * Setting an empty value allows to disable the trusted header for the given key.
     *
     * @param string $key   The header key
     * @param string $value The header name
     *
     * @throws \InvalidArgumentException
     */
    public static function setTrustedHeaderName($key, $value)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }

        self::$trustedHeaders[$key] = $value;
    }

    /**
     * Gets the trusted proxy header name.
     *
     * @param string $key The header key
     *
     * @return string The header name
     *
     * @throws \InvalidArgumentException
     */
    public static function getTrustedHeaderName($key)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to get the trusted header name for key "%s".', $key));
        }

        return self::$trustedHeaders[$key];
    }
       
    /**
     * 
     * @param array $server $_SERVER
     */
    protected static function mock(array $server = array())
    {
        $custom =array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Kaliba',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time()
        );
        
        return array_replace($custom, $server);
    }
      
    /**
     * Checks whether the request is made from a trusted proxy
     * @return bool
     */
    protected function isFromTrustedProxy()
    {
        return self::$trustedProxies && IpAddress::checkIp($this->server->get('REMOTE_ADDR'), self::$trustedProxies);
    }
    
    
}
