<?php

namespace Kaliba\Http\Contracts;


interface HttpClientInterface
{
    
    const HTTP_METHOD = 'method';
    const HTTP_HEADER = 'header';
    const HTTP_CONTENT = 'content';
    const HTTP_PROXY = 'proxy';
    const HTTP_TIMEOUT = 'timeout';
    const HTTP_PROTOCAL_VERSION = 'protocal_version';
    const HTTP_MAX_REDIRECTS = 'max_redirects';
    const HTTP_IGNORE_ERRORS = 'ignore_errors';    
    const SSL_PEER_NAME = 'peer_name';
    const SSL_VERIFY_PEER = 'ssl_verify_peer';
    const SSL_VERIFY_HOST = 'ssl_verify_host';
    const SSL_ALLOW_SELF_SIGNED = 'ssl_allow_self_signed';
    const SSL_CA_FILE = 'ssl_ca_file';
    const SSL_LOCAL_CERT = 'ssl_local_cert';
    const SSL_PASS_PHRASE = 'ssl_pass_phrase';

    
    /**
     * Set SSL CA FILE
     * @param string $file path to the file
     */
    public function sslcafile($file);
	
    /**
     * Add payload to send together 
     *
     * @param array $data Data to send together with the request.
     */
    public function payload(array $data); 
    
    /**
     * Do request.
     *
     * @param string $url The url or path you want to request.
     * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function request($method, $url,  array $data = [], array $options=[]);
    
    /**
     * Do a GET request.
     *
     * @param string $url The url or path you want to request.
     * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function get($url,  array $data = [], array $options=[]);  

    /**
     * Do a POST request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options  Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function post($url, array $data = [], array $options=[]);

    /**
     * Do a PUT request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function put($url, array $data = [], array $options=[]);

    /**
     * Do a PATCH request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function patch($url, array $data = [], array $options=[]);

    /**
     * Do a DELETE request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function delete($url, array $data = [], array $options=[]);

    /**
     * Do a HEAD request.
     *
     * @param string $url The url or path you want to request.
	 * @param array $data Data to send together with the request.
     * @param mixed $options Additional request options.
     * @return \Kaliba\Http\Response
     */
    public function head($url, array $data = [], array $options=[]);

    /**
     * Local error handler to capture errors triggered during
     * stream connection.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @return void
     */
    public function connectionErrorHandler($code, $message);

 
    
}
