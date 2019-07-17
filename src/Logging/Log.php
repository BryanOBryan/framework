<?php

namespace Kaliba\Logging;
use InvalidArgumentException;

/**
 * Logs messages to configured Log adapters.  
 * 
 * 
 * ### Writing to the log
 *
 * You log to the logs using Log::log(). 
 *
 * ### Logging Levels
 *
 * By default Kaliba Log supports all the log levels defined in
 * RFC 5424. When logging messages you can either use the named methods,
 * or the correct constants with `log()`:
 *
 * ```
 * Log::error('Something horrible happened');
 * Log::log(LOG_ERR, 'Something horrible happened');
 * ```
 *
 * ### Logging scopes
 *
 * When logging messages and configuring log adapters, you can specify
 * 'scopes' that the logger will handle.  You can think of scopes as subsystems
 * in your application that may require different logging setups.  For
 * example in an e-commerce application you may want to handle logged errors
 * in the cart and ordering subsystems differently than the rest of the
 * application.  By using scopes you can control logging for each part
 * of your application and also use standard log levels.
 */
class Log 
{    
    /**
     *
     * @var LoggerInterface
     */
    private static $logger;
    /**
     * Log levels as detailed in RFC 5424
     * http://tools.ietf.org/html/rfc5424
     *
     * @var array
     */
    protected static $levelMap = [
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
     * Fetch the engine attached to a specific configuration name.
     *
     * If the Log engine & configuration are missing an error will be
     * triggered.
     *
     * @param string $name The configuration name you want an engine for.
     * @param array $config The configurations for the adapter to be used.
     * @return LoggerInterface
     */
    public static function register(LoggerInterface $logger)
    {      
        static::$logger = $logger;
         
    }
   
    /**
     * Gets log levels
     *
     * Call this method to obtain current
     * level configuration.
     *
     * @return array active log levels
     */
    public static function levels()
    {
        return LogLevel::getLevels();
    }

    /**
     * Writes the given message and type to all of the configured log adapters.
     * Configured adapters are passed both the $level and $message variables. $level
     * is one of the following strings/values.
     *
     * ### Levels:
     *
     * - `LOG_EMERG`    => 'emergency',
     * - `LOG_ALERT`    => 'alert',
     * - `LOG_CRIT`     => 'critical',
     * - `LOG_ERR`      => 'error',
     * - `LOG_WARNING`  => 'warning',
     * - `LOG_NOTICE`   => 'notice',
     * - `LOG_INFO`     => 'info',
     * - `LOG_DEBUG`    => 'debug',
     *
     * ### Basic usage
     *
     * Write a 'warning' message to the logs:
     *
     * ```
     * Log::log('warning', 'Stuff is broken here');
     * ```
     *
     * ### Using scopes
     *
     * When writing a log message you can define one or many scopes for the message.
     * This allows you to handle messages differently based on application section/feature.
     *
     * ```
     * Log::log('warning', 'Payment failed', ['scope' => 'payment']);
     * ```
     *
     * When configuring loggers you can configure the scopes a particular logger will handle.
     * When using scopes, you must ensure that the level of the message, and the scope of the message
     * intersect with the defined levels & scopes for a logger.
     *
     * ### Unhandled log messages
     *
     * If no configured logger can handle a log message (because of level or scope restrictions)
     * then the logged message will be ignored and silently dropped. You can check if this has happened
     * by inspecting the return of log(). If false the message was not handled.
     *
     * @param int|string $level The severity level of the message being written.
     *    The value must be an integer or string matching a known level.
     * @param mixed $message Message content to log
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     * @throws \InvalidArgumentException If invalid level is passed.
     */
    public static function log($level, $message, $context = [])
    {
        if (is_int($level) && in_array($level, static::$levelMap)) {
            $level = array_search($level, static::$levelMap);
        }

        if (!in_array($level, static::levels())) {
            throw new InvalidArgumentException(sprintf('Invalid log level "%s"', $level));
        }

        $logged = false;
        if (isset($context[0])) {
            $context = ['scope' => $context];
        }
        $context += ['scope' => []];
        $logger = static::$logger;

        if ($logger instanceof Logger) {
            $levels = $logger->getLevels();
            $scopes = $logger->getScopes();
        }
        if ($scopes === null) {
            $scopes = [];
        }

        $correctLevel = empty($levels) || in_array($level, $levels);
        $inScope = $scopes === false && empty($context['scope']) || $scopes === [] || is_array($scopes) && array_intersect($context['scope'], $scopes);
        if ($correctLevel && $inScope) {
            $logger->log($level, $message, $context);
            $logged = true;
        }
        return $logged;
    }

    /**
     * Convenience method to log emergency messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function emergency($message, $context = [])
    {
        return static::log('EMERGENCY', $message, $context);
    }

    /**
     * Convenience method to log alert messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function alert($message, $context = [])
    {
        return static::log('ALERT', $message, $context);
    }

    /**
     * Convenience method to log critical messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function critical($message, $context = [])
    {
        return static::log('CRITICAL', $message, $context);
    }

    /**
     * Convenience method to log error messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function error($message, $context = [])
    {
        return static::log('ERROR', $message, $context);
    }

    /**
     * Convenience method to log warning messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function warning($message, $context = [])
    {
        return static::log('WARNING', $message, $context);
    }

    /**
     * Convenience method to log notice messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function notice($message, $context = [])
    {
        return static::log('NOTICE', $message, $context);
    }

    /**
     * Convenience method to log debug messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function debug($message, $context = [])
    {
        return static::log('DEBUG', $message, $context);
    }

    /**
     * Convenience method to log info messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     * @return bool Success
     */
    public static function info($message, $context = [])
    {
        return static::log('INFO', $message, $context);
    }
}
