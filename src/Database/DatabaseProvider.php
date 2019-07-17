<?php
namespace Kaliba\Database;

use Kaliba\ORM\Model;
use Kaliba\Support\ServiceProvider;
use Kaliba\Database\DatabaseManager;
use Kaliba\ORM\Datasource\Database;


class DatabaseProvider extends ServiceProvider
{

    public function register()
    {
        $config = $this->app->config('database');
        $manager = new DatabaseManager($config);
        Model::register($manager);
        $this->app->set('database', $manager);
    }

}
