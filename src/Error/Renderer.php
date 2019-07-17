<?php

namespace Kaliba\Error;

final class Renderer 
{  
	
    /**
     * @var Exception
     */
    private $exception;
	
    /*
     *  Constructor*
     * @param Exception $exception Exception Instance
     */
    public function __construct($exception)
    {
        $this->exception = $exception instanceof PHP7Error ? $exception->getError() : $exception;
    }

    /**
     * Convert the Exception to renderable html format
     * @param mixed $exception
     * @return string
     */
    private function convertToHtml($exception)
    {

        $html= '<h1>Error details</h1>';
        $html .= sprintf('<div class=details><div><strong>Type   :   </strong> %s</div>', get_class($exception));
        if (($code = $exception->getCode())) {
            $html .= sprintf('<div><strong>Code :   </strong> %s</div>', $code);
        }
        if (($file = $exception->getFile())) {
            $html .= sprintf('<div><strong>File :   </strong> %s</div>', $file);
        }
        if (($line = $exception->getLine())) {
            $html .= sprintf('<div><strong>Line :   </strong> %s</div></div>', $line);
        }
        if (($trace = $exception->getTraceAsString())) {
            $html .= '<h1>Trace</h1>';
            $html .= sprintf('<div class=trace><pre>%s</pre></div>', htmlentities($trace));
        }
        return $html;
    }
    
    /**
     * Render the Exception
     */
    public function output() 
    {
        $message = $this->exception->getMessage();

        $content = $this->convertToHtml($this->exception);
        while ($exception = $this->exception->getPrevious()) {
            $content .= '<h3>Previous exception</h3>';
            $content .= $this->convertToHtml($this->exception);
        }
        ob_start('ob_gzhandler');
        extract(['message'=>$message, 'content'=>$content]);
        include(__DIR__."/resource/index.php" );
        $output = ob_get_clean();
        return $output;

    }
    

    
}
