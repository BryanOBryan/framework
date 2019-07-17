<?php
namespace Kaliba\Routing;
use Kaliba\Http\AjaxAction;
use Kaliba\Http\GetAction;
use Kaliba\Http\PostAction;
use Kaliba\Http\Redirect;
use Kaliba\Http\Request;
use Kaliba\Http\Middleware;
use Kaliba\Http\Response;
use Kaliba\Routing\Route;
use Kaliba\View\Viewable;


class Processor extends Middleware
{
    /**
     *
     * @var mixed
     */
    private $controller;

    /**
     * Processor constructor.
     * @param \Kaliba\Routing\Route $route
     */
    public function __construct(Route $route)
    {        
        $this->controller = $route->getController();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request) 
    {
        $response = null;
        if($request->isGet() ){
            $response = $this->get($request);
        }
        elseif($request->isPost()){
            $response = $this->post($request);
        }
        elseif($request->isAjax()){
            $response = $this->ajax($request);
        }
        return $this->respond($response);

    }

    /**
     * @param Request $request
     * @param mixed
     */
    private function get(Request $request)
    {
        if($this->controller instanceof GetAction){
            return $this->controller->get($request);
        }
    }

    /**
     * @param Request $request
     * @param mixed $controller
     * @param Viewable $view
     */
    private function ajax(Request $request)
    {
        if($this->controller instanceof AjaxAction){
            return $this->controller->ajax($request);
        }
    }

    /**
     * @param Request $request
     * @param mixed $controller
     * @param Viewable $view
     */
    private function post(Request $request)
    {
        if($this->controller instanceof PostAction){
            return $this->controller->post($request);
        }
    }

    /**
     * @param $response
     * @return Response|mixed
     */
    private function respond($response)
    {
        if($response instanceof Redirect){
            return $response->send();
        }
        elseif($this->controller instanceof Viewable){
            $response = $this->controller->view();
            if($response instanceof Redirect){
                return $response->send();
            }
        }
        return $response;
    }

}
