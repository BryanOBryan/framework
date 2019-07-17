<?php

namespace Kaliba\Robas\Middleware;
use Kaliba\Http\Middleware;
use Kaliba\Http\Request;
use Kaliba\Http\Response;
use Kaliba\Robas\Auth;
use Kaliba\Robas\Exceptions\AuthorizationException;

class Authorize extends Middleware
{
    /**
     * Handles an incoming request
     * @param Request $request
     * @return Response|void
     * @throws AuthorizationException
     */
    public function handle(Request $request)
    {     
        if(Auth::check() && $this->guard($request)){
            if(!Auth::permit($request->getPath())){ 
                throw new AuthorizationException;
            }
        }
        return $this->next($request);
    }
    
}
