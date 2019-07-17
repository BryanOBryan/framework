<?php

namespace Kaliba\Logging;

/**
 * File Storage stream for Logging. Writes logs to different files
 * based on the level of log it is.
 *
 */
class FileLogger extends Logger
{
    /**
     *
     * @var string
     */
    protected $storage = 'logs';
    
    /**
     *
     * @var int
     */
    protected $rotate = 10;
    
    /**
     *
     * @var int
     */
    protected $size = 10485760;
   
    /**
     * Sets protected options  provided
     *
     * @param array $options Configuration array
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->storage = $options['storage']?? null;            
    }
    
    /**
     * Log storage path
     * @param string $storage
     */
    public function storage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Implements writing to log files.
     *
     * @param string $level The severity level of the message being written.
     *    See Cake\Log\Log::$_levels for list of possible levels.
     * @param string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return bool success of write.
     */
    public function log($level, $message, array $context = [])
    {
        $format = $this->format($message, $context);
        $output = date('Y-m-d H:i:s') . ' ' . ucfirst($level) . ' : ' . $format . "\n";
        $filename = $this->getFilename($level);
        if (!empty($this->size)) {
            $this->rotateFile($filename);
        }
        if (!empty($this->storage) && !file_exists($this->storage)){
            mkdir($this->storage, 0775, true) ;
        }  
        $storagename = $this->storage .DIRECTORY_SEPARATOR. $filename;
        
        return file_put_contents($storagename, $output, FILE_APPEND);
        
        
    }

    /**
     * Get filename
     *
     * @param string $level The level of log.
     * @return string File name
     */
    protected function getFilename($level)
    {
        $debugTypes = [LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];
        $errorTypes = [LogLevel::ERROR, LogLevel::WARNING];

        if (in_array($level, $errorTypes)) {
            $filename = 'error.log';
        } elseif (in_array($level, $debugTypes)) {
            $filename = 'debug.log';
        } else {
            $filename = strtolower($level) . '.log';
        }

        return $filename;
    }

    /**
     * Rotate log file if size specified in config is reached.
     * Also if `rotate` count is reached oldest file is removed.
     *
     * @param string $filename Log file name
     * @return mixed True if rotated successfully or false in case of error.
     *   Void if file doesn't need to be rotated.
     */
    protected function rotateFile($filename)
    {
        $filestorage = $this->storage . $filename;
        clearstatcache(true, $filestorage);

        if (!file_exists($filestorage) ||filesize($filestorage) < $this->size) {
            return;
        }

        $rotate = $this->rotate;
        if ($rotate === 0) {
            $result = unlink($filestorage);
        } else {
            $result = rename($filestorage, $filestorage . '.' . time());
        }

        $files = glob($filestorage . '.*');
        if ($files) {
            $filesToDelete = count($files) - $rotate;
            while ($filesToDelete > 0) {
                unlink(array_shift($files));
                $filesToDelete--;
            }
        }

        return $result;
    }
    
    
}
