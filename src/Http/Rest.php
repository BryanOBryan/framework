<?php

namespace Kaliba\Http;
use Kaliba\Http\Contracts\HttpClientInterface;
use Kaliba\Http\Request;
use Kaliba\Http\Response;
use Kaliba\Http\Helpers\ParameterBag;
use Kaliba\Support\ConfigTrait;
use Exception;


class Rest implements HttpClientInterface
{
    use ConfigTrait;


    /**
     * options/content for the HTTP stream context.
     *
     * @var Kaliba\Http\Helpers\ParameterBag
     */
    protected $httpContext;
        
    /**
     * options/content for the SSL stream context.
     *
     * @var Kaliba\Http\Helpers\ParameterBag
     */
    protected $sslContext;
    
    /**
     * Connection error list.
     *
     * @var array
     */
    protected $connectionErrors = [];
      
    /**
     *
     * @var string
     */
    protected $sslcafile;
    
    /**
     *
     * @var array
     */
    protected $payload = [];

    /**
     * Constructor
     *
     * @param array $config Additional request options.
     * 
     * ALLOWED CONFIG PARAMS 
     *  timeout,
     *  max_redirects,
     *  proxy,
     *  sslcafile,
     *  ssl_verify_host,
     *  ssl_verif_peer,
     *  ssl_peer_name,
     *  ssl_local_cert,
     *  ssl_pass_phrase
     * 
     * @return void
     */
    public function __construct(array $config= []) {
        $this->config($config);
        $this->connectionErrors = [];
        $this->httpContext = new ParameterBag();
        $this->sslContext = new ParameterBag();
       
    }
	
    /**
     * Set SSL CA FILE
     * @param string $file path to the file
     */
    public function sslcafile($file)
    {
        $this->sslcafile = $file;
    }
	
    /**
     * Add payload to send together 
     *
     * @param array $data Data to send together with the request.
     */
    public function payload(array $data)
    {
        $this->payload = $data;
    }  
    
    /**
     * Do request.
     *
     * @param string $url The url or path you want to request.
     * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function request($method, $url,  array $data = [], array $options=[])
    {
        if(empty($data)){
            $data = $this->payload;
        }
        $uri = Uri::createFrom($url, $data); 
        $request = Request::create($uri, $method); 
        $response = $this->send($request, $options);
        return $response;
    }  
	
    /**
     * Do a GET request.
     *
     * @param string $url The url or path you want to request.
     * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function get($url,  array $data = [], array $options=[])
    {
        return $this->request('GET', $url, $data, $options);
    }   

    /**
     * Do a POST request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function post($url, array $data = [], array $options=[])
    {
        return $this->request('POST', $url, $data, $options);
        
    }

    /**
     * Do a PUT request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function put($url, array $data = [], array $options=[])
    {
        return $this->request('PUT', $url, $data, $options);
    }

    /**
     * Do a PATCH request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function patch($url, array $data = [], array $options=[])
    {		
        return $this->request('PATCH', $url, $data, $options);
    }

    /**
     * Do a DELETE request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function delete($url, array $data = [], array $options=[])
    {
        return $this->request('DELETE', $url, $data, $options);
        
    }

    /**
     * Do a HEAD request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function head($url, array $data = [], array $options=[])
    {
        return $this->request('HEAD', $url, $data, $options);
        
    }

    /**
     * Send a request and get a response back.
     * @param \Kaliba\Http\Request $request The request being sent.
     * @param array $options Additional request options.
     * @return array Array of populated Response objects
     */
    private function send(Request $request, array $options=[])
    {
        $this->config($options);
        $this->buildHttpContext($request);
        if($request->isSecure()){
            $this->buildSslContext($request);  
        } 
		
        $curl = curl_init();
        set_error_handler([$this, 'connectionErrorHandler']);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
		
        switch ($request->getMethod()) {
    
            case "GET":
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case "HEAD":
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getData());
                break;
            case "PUT":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$request->getMethod());
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getData());
            case "PATCH":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$request->getMethod());
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getData());
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
                break;
            default:
                throw new Exception('Method "' . $request->getMethod() . '" not supported!');
        }
        curl_setopt_array($curl, $this->getContext());
        curl_setopt($curl, CURLOPT_URL, $request->getUri());
        $response = curl_exec($curl);
        return $this->parseResponse($response);
        
        
    }
    
    /**
     * Build the stream context out of the request object.
     *
     * @param \Kaliba\Http\Request $request The request to build context from.
     * @return void
     */
    private function buildHttpContext(Request $request)
    {
        $this->httpContext->set( self::HTTP_CONTENT,  $request->getBody() );
        $this->httpContext->set( self::HTTP_HEADER,   $request->getHeadersAsString());
        $this->httpContext->set( self::HTTP_METHOD,   $request->getMethod());
        $this->httpContext->set( self::HTTP_PROTOCAL_VERSION, $request->getProtocolVersion() );
        $this->httpContext->set( self::HTTP_IGNORE_ERRORS, true);
        if ($this->configHas(self::HTTP_TIMEOUT) ){
            $this->httpContext->set(self::HTTP_TIMEOUT, $this->configRead(self::HTTP_TIMEOUT));
        }
        if ($this->configHas(self::HTTP_MAX_REDIRECTS)) {
            $this->httpContext->set( self::HTTP_MAX_REDIRECTS, $this->configRead(self::HTTP_MAX_REDIRECTS) );
        }
        if ($this->configHas(self::HTTP_PROXY)) {
            $this->httpContext->set( self::HTTP_PROXY, $this->configRead(self::HTTP_PROXY) );
        }       
    }
    
    /**
     * Build SSL options for the request.
     *
     * @param \Kaliba\Http\Request $request The request being sent.
     * @return void
     */
    private function buildSslContext(Request $request)
    {
        if (!$this->configHas(self::SSL_CA_FILE)){
            $this->configWrite(self::SSL_CA_FILE, $this->sslcafile);            
        }
        if ($this->configHas(self::SSL_VERIFY_HOST)) {
            $this->sslContext->set(self::SSL_PEER_NAME, $request->getHost());
        }
        foreach ($this->config as $key => $value) {
            $prefix = substr($key,0, 4);
            $name = substr($key,4);
            if ($prefix === 'ssl_' && $this->configHas($name)) {
                $this->sslContext->set($name, $value);
            }
        }
    }
	
    /**
     * Get the context options
     *
     * Useful for debugging and testing context creation.
     *
     * @return array
     */
    private function getContext()
    {
        return array_merge($this->httpContext->toArray(), $this->sslContext->toArray());
    }
	
    /**
     * Parse the response
     * @param $response
     */
    private function parseResponse($response)
    {
        $response_parts = explode("\r\n\r\n", $response);
        $header =  array_shift($response_parts);
        $content = array_shift($response_parts);
        
        $headerlines = preg_split("/(\r|\n)+/",$header, -1, PREG_SPLIT_NO_EMPTY);
        $headers = array();
        foreach ($headerlines as $line){          
            list($name, $value) = explode(':', $line, 2); 
            $headers[trim($name)]  = trim($value);            
        }
        return new Response($content, 200, $headers);
       
    } 

    /**
     * Local error handler to capture errors triggered during
     * stream connection.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @return void
     */
    public function connectionErrorHandler($code, $message)
    {
        $this->connectionErrors[] = $message;
    }

 
    
}
