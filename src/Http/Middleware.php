<?php

namespace Kaliba\Http;
use Kaliba\Http\Contracts\MiddlewareInterface;

abstract class Middleware implements MiddlewareInterface
{
    /**
     * Resources or application routes to ignore
     * @var array
     */
    protected $ignore = [];
    
    /**
     *
     * @var MiddlewareInterface
     */
    protected $next;

    /**
     * Set the next middleware 
     * @param MiddlewareInterface $next
     */
    public function set($next)
    {
        $this->next = $next;
    }
    
    /**
     * Fetch the next middleware to execute the incoming request
     * @return Response
     */
    protected function next(Request $request)
    {
        return $this->next->handle($request); 
    }

    /**
     * Check if request needs to be guarded or ignored
     * @param Request $request
     * @return bool
     */
    protected function guard(Request $request)
    {
        $path = $request->getPath();
        if(!in_array($path, $this->ignore)){
            return true;
        }else{
            return false;
        }
    }



}
