<?php
namespace Kaliba\Database;
use Kaliba\Database\Connection;
use Kaliba\Database\Connections\MysqlConnection;
use Kaliba\Database\Connections\PostgresConnection;
use Kaliba\Database\Connections\SqliteConnection;
use Kaliba\Database\Connections\SqlserverConnection;
use Kaliba\Database\Connectors\MysqlConnector;
use Kaliba\Database\Connectors\PostgresConnector;
use Kaliba\Database\Connectors\SqliteConnector;
use Kaliba\Database\Connectors\SqlserverConnector;
use Kaliba\Database\Contracts\ConnectionInterface;
use Kaliba\Database\Contracts\ConnectorInterface;
use InvalidArgumentException;

class ConnectionFactory
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array   $config
     * @return ConnectionInterface
     */
    public function make(array $config)
    {
        $driver =  (string)$config['driver'];
        $pdo = $this->createConnector($driver)->connect($config);
        return $this->createConnection($driver, $pdo, $config['database'], $config['prefix']);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param  string $driver
     * @return ConnectorInterface
     *
     * @throws InvalidArgumentException
     */
    protected function createConnector($driver)
    {
        switch ($driver) {
            case 'mysql':
                return new MysqlConnector();
            case 'postgres':
                return new PostgresConnector();
            case 'sqlite':
                return new SqliteConnector();
            case 'sqlserver':
                return new SqlserverConnector();
        }
        throw new InvalidArgumentException("Unsupported driver {$driver}");
    }

    /**
     * Create a new connection instance.
     * @param  string   $driver
     * @param  \PDO    $pdo
     * @param  string   $database
     * @param  string   $prefix
     * @return ConnectionInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $pdo, $database, $prefix = '')
    {
        switch ($driver) {
            case 'mysql':
                return new MysqlConnection($pdo, $database, $prefix);
            case 'postgres':
                return new PostgresConnection($pdo, $database, $prefix);
            case 'sqlite':
                return new SqliteConnection($pdo, $database, $prefix);
            case 'sqlserver':
                return new SqlserverConnection($pdo, $database, $prefix);
        }
        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }

    
}
