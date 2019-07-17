<?php

namespace Kaliba\Routing;
use Kaliba\Http\Request;
use Kaliba\Http\Response;


class Dispatcher
{
    /**
     *
     * @var Route
     */
    private $route;
    
    /**
     *
     * @var Request
     */
    private $request;
    
    /**
     *
     * @var Middleware
     */
    private $middleware;

    /**
     * Dispatcher constructor.
     * @param Request $request
     * @param Route $route
     * @param Middleware $middleware
     */
    public function __construct(Request $request, Route $route, Middleware $middleware)
    {
        $this->request = $request;
        $this->route = $route;
        $this->middleware = $middleware;
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     * @return Response|string
     */
    public function dispatch()
    {    
        $routeName = $this->route->getName();
        $processor = new Processor($this->route);             
        $stack = new Stack($this->request);       
        $stack->add($processor);
        $stack->bulk($this->middleware->route($routeName));
        $stack->bulk($this->middleware->universal());
        $response = $stack->run();
        if($response instanceof Response){
            return $this->respond($response);
        }else{
            return $response;
        }

    }

    /**
     * Prepares response
     *
     * @param Response $response
     * @return Response
     */
    private function respond($response)
    {
        if ($this->isEmpty($response)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }
        $size = $response->getBody()->getSize();
        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string) $size);
        }
        return $response->send();
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * @see https://tools.ietf.org/html/rfc7231
     *
     * @param Response $response
     * @return bool
     */
    private function isEmpty($response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }


}
