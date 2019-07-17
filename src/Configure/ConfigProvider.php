<?php


namespace Kaliba\Configure;
use Kaliba\Support\ServiceProvider;

class ConfigProvider extends ServiceProvider
{

    public function register()
    {
        $config = new Config($this->app->configPath());
        $this->app->set('config', $config);
    }

}