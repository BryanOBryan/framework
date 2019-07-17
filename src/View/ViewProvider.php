<?php

namespace Kaliba\View;
use eftec\bladeone\BladeOne;
use Kaliba\Support\ServiceProvider;

class ViewProvider extends ServiceProvider
{
    public function register()
    {
        $config = $this->app->config('view');
        $blade = new BladeOne($config['path'], $config['compiled']);
        $viewer = new Viewer($blade);
        $this->app->set('view', $viewer);

    }
}