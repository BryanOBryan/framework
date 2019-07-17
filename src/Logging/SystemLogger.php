<?php

namespace Kaliba\Logging;

/**
 * Syslog stream for Logging. Writes logs to the system logger
 */
class SystemLogger extends Logger
{
    
    /**
     *
     * @var string
     */
    protected $format = '%s: %s';
    
    /**
     *
     * @var int
     */
    protected $flag = LOG_ODELAY;
    
    /**
     *
     * @var int
     */
    protected $facility = LOG_USER;
    
    /**
     *
     * @var string
     */
    protected $prefix;

    /**
     * Used to map the string names back to their LOG_* constants
     *
     * @var array
     */
    protected $levelMap = [
        'EMERGENCY' => LOG_EMERG,
        'ALERT' => LOG_ALERT,
        'CRITICAL' => LOG_CRIT,
        'ERROR' => LOG_ERR,
        'WARNING' => LOG_WARNING,
        'NOTICE' => LOG_NOTICE,
        'INFO' => LOG_INFO,
        'DEBUG' => LOG_DEBUG
    ];
    
    /**
     * Whether the logger connection is open or not
     *
     * @var bool
     */
    protected $open = false;

    /**
     * Sets protected options based on config provided
     *
     * @param array $options Configuration array
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }
    
    /**
     * Writes a message to syslog
     *
     * Map the $level back to a LOG_ constant value, split multi-line messages into multiple
     * log messages, pass all messages through the format defined in the configuration
     *
     * @param string $level The severity level of log you are making.
     * @param string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return bool success of write.
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->open) {
            $this->open($this->options['prefix'], $this->flag, $this->facility);
            $this->open = true;
        }

        $priority = LOG_DEBUG;
        if (isset($this->levelMap[$level])) {
            $priority = $this->levelMap[$level];
        }

        $messages = explode("\n", $this->format($message, $context));
        foreach ($messages as $message) {
            $message = sprintf($this->format, $level, $message);
            $this->write($priority, $message);
        }

        return true;
    }

    /**
     * Extracts the call to openlog() in order to run unit tests on it. This function
     * will initialize the connection to the system logger
     *
     * @param string $ident the prefix to add to all messages logged
     * @param int $options the options flags to be used for logged messages
     * @param int $facility the stream or facility to log to
     * @return void
     */
    protected function open($ident, $options, $facility)
    {
        openlog($ident, $options, $facility);
    }

    /**
     * Extracts the call to syslog() in order to run unit tests on it. This function
     * will perform the actual write in the system logger
     *
     * @param int $priority Message priority.
     * @param string $message Message to log.
     * @return bool
     */
    protected function write($priority, $message)
    {
        return syslog($priority, $message);
    }

    /**
     * Closes the logger connection
     */
    public function __destruct()
    {
        closelog();
    }
}
