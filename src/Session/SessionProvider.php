<?php

namespace Kaliba\Session;
use Kaliba\Support\ServiceProvider;

class SessionProvider extends ServiceProvider
{

    /**
     * @return mixed|void
     */
    public function register()
    {
        $this->configure();
        $this->boot();

    }

    protected function boot()
    {
        $factory = new SessionHandlerFactory($this->app);
        $handler = $factory->getDriver();
        session_set_save_handler($handler);
        session_start();
    }

    protected function configure()
    {
        $config = $this->app->config('session');
        ini_set('session.cookie_secure', $config['secure']);
        ini_set('session.cookie_httponly', $config['httponly']);
        ini_set('session.cookie_domain', $config['domain']);
        ini_set('session.cookie_lifetime', $config['lifetime']);

    }
}