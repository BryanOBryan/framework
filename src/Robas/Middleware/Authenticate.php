<?php
namespace Kaliba\Robas\Middleware;

use Kaliba\Http\Middleware;
use Kaliba\Http\Request;
use Kaliba\Http\Redirect;
use Kaliba\Robas\Auth;

class Authenticate extends Middleware
{
    /**
     * Redirect URL when user is not authenticated.
     *
     * @var string
     */
    protected $redirect = 'index';

    /**
     * Handles an incoming request
     * @param Request $request
     * @return Response|void
     */
    public function handle( Request $request )
    {
        if($request->isGet() && $this->guard($request)) {
            if(!Auth::check()){
                return redirect($this->redirect);
            }
        }
        return $this->next($request);
    }

}