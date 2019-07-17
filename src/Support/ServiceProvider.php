<?php
namespace Kaliba\Support;
use Kaliba\Foundation\Application;

abstract class ServiceProvider
{
    /**
     *
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed
     */
    abstract public function register();
}
