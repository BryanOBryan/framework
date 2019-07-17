<?php
namespace Kaliba\Logging;

/**
 * Describes log levels
 */
class LogLevel
{

    /**
     * Detailed debug information
     */
    const DEBUG = 'DEBUG';

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 'INFO';

    /**
     * Uncommon events
     */
    const NOTICE = 'NOTICE';

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 'WARNING';

    /**
     * Runtime errors
     */
    const ERROR = 'ERROR';

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 'CRITICAL';

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 'ALERT';

    /**
     * Urgent alert.
     */
    const EMERGENCY = 'EMERGENCY';
    
    public static function getLevels(){
        return array(
            self::ALERT,
            self::CRITICAL,
            self::DEBUG,
            self::EMERGENCY,
            self::ERROR,
            self::INFO,
            self::NOTICE,
            self::WARNING
        );
    }

}
