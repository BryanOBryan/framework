<?php

namespace Kaliba\Routing;
use Kaliba\Foundation\Application;
use Kaliba\Http\Exception\HttpException;
use Kaliba\Http\Request;
use Kaliba\Http\Response;
use Kaliba\Routing\Conversion;
use Kaliba\Routing\Dispatcher;
use Kaliba\Routing\Middleware;
use Kaliba\Routing\Router;

class Kernel
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param  Request  $request
     * @return Response
     * @throws HttpException
     */
    public function handle( Request $request )
    {
        $conversion = $this->app->make(Conversion::class);
        $router = $this->app->make(Router::class);
        $middleware = $this->app->make(Middleware::class);
        $router->addRule($conversion);
        $route = $router->getRoute(trim($request->getPath(), '/'));
        $dispatcher = new Dispatcher($request,$route,$middleware);
        return $dispatcher->dispatch();
    }

}