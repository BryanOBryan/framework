<?php
namespace Kaliba\Logging;

class LoggerFactory
{
    /**
     * List of supported database drivers
     * @var array
     */
    private $drivers = [
        'file'      =>  FileLogger::class,
        'system'    =>  SystemLogger::class,
        'default'   =>  FileLogger::class
    ];

    /**
     * Create Logger instance based on the configuration.
     * @param array $config an array of configurations
     * @return LoggerInterface
     */
    public function create(array $config)
    {
        $driver =  (string)$config['driver'];
        if (empty($driver)) {
            throw new \InvalidArgumentException('A driver must be specified.');
        }      
        if(isset($this->drivers[$driver])){
            $driver = $this->drivers[$driver];
            $logger = new $driver($config);
            return $logger;
        }
        throw new \InvalidArgumentException("Unsupported driver {$driver}"); 
    }
 
    
    
}
