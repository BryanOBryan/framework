<?php

namespace Kaliba\Database;
use Kaliba\Database\Contracts\ConnectionInterface;
use Kaliba\Database\Contracts\ResolverInterface;
use InvalidArgumentException;

class DatabaseManager implements ResolverInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * DatabaseManager constructor.
     * @param array $config
     */
    public function __construct( array $config )
    {
        $this->config = $config;
    }

    /**
     * Get a database connection instance.
     *
     * @param string $name
     * @return ConnectionInterface
     */
    public function getConnection( $name = null )
    {
        if(empty($name)){
            $name = $this->getDefaultConnection();
        }
        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (empty($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->config['default'];
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection( $name )
    {
        $this->config['default'] = $name;
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return ConnectionInterface
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);
        $factory = new ConnectionFactory();
        return $factory->make($config);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $configurations = $this->config['connections'];

        if (empty($configurations[$name])) {
            throw new InvalidArgumentException("Database [$name] not configured.");
        }else{
            return $configurations[$name];
        }

    }

}