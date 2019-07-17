<?php
namespace Kaliba\Error;
use Kaliba\Support\ServiceProvider;
use Kaliba\Error\Handler;

class ErrorHandlerProvider extends  ServiceProvider
{

    public function register() 
    {
        $config = $this->app->config('error');
        error_reporting($config['level']);
        $handler = new Handler($config);
        set_exception_handler([$handler, 'handle']);

    }
}
