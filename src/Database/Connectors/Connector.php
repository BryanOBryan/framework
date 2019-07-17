<?php

namespace Kaliba\Database\Connectors;
use Exception;
use PDO;

/**
 * Represents a PDO driver containing all specificities for
 * a database engine including its SQL dialect
 *
 */
abstract class Connector
{
    
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    /**
     *
     * @var array
     */
    protected $status = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Deadlock found when trying to get lock',
        'Error writing data to the connection',
    ];
    
   
    /**
     * Get the PDO options based on the configuration.
     * @return array
     */
    public function getOptions(array $config)
    {
        $options = isset($config['options'])? $config['options'] :[];

        return array_diff_key($this->options, $options) + $options;
    }
    
    /**
     * Get the default PDO connection options.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->options;
    }
    
    /**
     * Set the default PDO connection options.
     *
     * @param  array  $options
     * @return void
     */
    public function setDefaultOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Establishes a connection to the database server
     *
     * @param string $dsn A Driver-specific PDO-DSN
     * @param array $config configuration to be used for creating connection
     * @param array $options PDO connection options
     * @return bool true on success
     */
    public function createConnection($dsn, array $config, array $options)
    {       
        $username = $config['username'];
        $password = $config['password'];
        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (Exception $e) {
            return $this->recreateConnection(
                $e, $dsn, $username, $password, $options
            );
        }
    }
    
    /**
     * Handle a exception that occurred during connect execution.
     *
     * @param  \Exception  $e
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function recreateConnection(Exception $e, $dsn, $username, $password, $options)
    {
        if ($this->lostConnection($e)) {
            return new PDO($dsn, $username, $password, $options);
        }
        throw $e;
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function lostConnection(Exception $e)
    {
        $message = $e->getMessage();
        return $this->contains($message, $this->status); 
    }
    
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    protected function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
 
  
}
