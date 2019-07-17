<?php


namespace Kaliba\Session;
use InvalidArgumentException;
use Kaliba\Filesystem\FileManager;
use Kaliba\Foundation\Application;
use Kaliba\Security\Encrypter;

class SessionHandlerFactory
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * SessionHandlerFactory constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param null $name
     * @return \SessionHandlerInterface
     */
    public function getDriver($name=null)
    {
        if(empty($name)){
            $name = $this->getDefaultDriver();
        }
        return $this->createDriver($name);
    }

    /**
     * @param $name
     * @return DatabaseSessionHandler|FileSessionHandler
     */
    protected function createDriver($name)
    {
        switch ($name){
            case 'file':
                return $this->createFileHandler();
            case 'database':
                return $this->createDatabaseHandler();
        }
        throw new InvalidArgumentException("Unsupported driver {$name}");
    }

    /**
     * @return DatabaseSessionHandler
     */
    protected function createDatabaseHandler()
    {
        $handler = new DatabaseSessionHandler(
            $this->getConnection(),
            $this->app->config('session.table'),
            $this->app->config('session.lifetime')
        );
        return $handler;
    }

    /**
     * @return FileSessionHandler
     */
    protected function createFileHandler()
    {
        $handler = new FileSessionHandler(
            new FileManager(),
            $this->app->config('session.storage'),
            $this->app->config('session.lifetime')
        );
        return $handler;
    }

    /**
     * Get the database connection for the database driver.
     *
     * @return \Kaliba\Database\Connections\Connection
     */
    protected function getConnection()
    {
        $connection = $this->app->config('session.connection');
        return $this->app->get('database')->getConnection($connection);
    }

    /**
     * Get the default session driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app->config('session.driver');
    }


}