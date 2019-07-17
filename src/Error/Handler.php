<?php

namespace Kaliba\Error;
use Kaliba\Logging\FileLogger;


final class Handler
{   
    /**
     *
     * @var bool
     */
    private $log = true;
    
    /**
     *
     * @var bool
     */
    private $display = true;
    
    /**
     *
     * @var string
     */
    private $logpath = null;

    /**
     * Set configurations
     *
     * @param array $options Associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function __construct(array $options)
    {	 
        $this->log = $options['log'];
        $this->display = $options['display']; 
        $this->logpath = $options['logpath'];
    }
    
    /**
     * 
     * @param \Exception $exception
     */
    public function handle($exception)
    {
        if ($exception instanceof Error) {
            $exception = new PHP7Error($exception);
        }
        $this->log($exception);
        $this->display($exception);
    }
    
    /**
     * 
     * @param \Exception $exception
     */
    private function log($exception)
    {
        if ($this->log==true) { 
            $message = $this->getMessage($exception);
            $logger = new FileLogger([]);
            $logger->storage($this->logpath);
            return $logger->error($message);
        }
    }
    
    /**
     * 
     * @param \Exception $exception
     */
    private function display($exception)
    {       
        try {
            $renderer = new Renderer($exception);
            $response = $renderer->output();
            $this->clearOutput();
            $this->sendResponse($response);
        } catch (\Exception $e) {
            $message = sprintf("[%s] %s\n%s", get_class($e), $e->getMessage(), $e->getTraceAsString());
            trigger_error($message, E_USER_ERROR);
        }
    }
    
    /**
     * 
     * @param Exception $exception
     */
    private function getMessage($exception)
    {
        $message = sprintf("[%s] %s",get_class($exception), $exception->getMessage() );
        if ($this->display==true && method_exists($exception, 'getAttributes')) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $message .= "\nException Attributes: " . var_export($exception->getAttributes(), true);
            }
        }
        if($this->trace == true){
            $message .= "\nStack Trace:\n" . $exception->getTraceAsString() . "\n\n";
        }
        
        return $message;
    }
    
    /**
     * Clear output buffers so error pages display properly.
     *
     * Easily stubbed in testing.
     *
     * @return void
     */
    private function clearOutput()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Method that can be easily stubbed in testing.
     *
     * @param string|\Kaliba\Http\Response $response Either the message or response object.
     * @return void
     */
    private function sendResponse($response)
    {
        if (is_string($response)) {
            echo $response;
            return;
        }
        $response->send();
    }
}
