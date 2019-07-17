<?php

namespace Kaliba\Http\Middleware;
use Kaliba\Http\Exception\InvalidTokenException;
use Kaliba\Http\Middleware;
use Kaliba\Http\Request;
use Kaliba\Security\Hash;
use Kaliba\Support\Session;


class CSRFMiddleware extends Middleware
{    
    /**
     * Token name
     * @var string
     */
    protected $tokenName = 'csrf_token';

    /**
     * @var Session
     */
    protected $session;

    public function __construct()
    {
        $this->session = Session::instance();
    }

    /**
     * Handle an incoming request
     * @param Request $request
     * @return \Kaliba\Http\Response|void
     * @throws HttpException
     */
    public function handle(Request $request) 
    {
        if ($request->isGet()){
            $hash = Hash::unique();
            $this->session->set($this->tokenName, $hash);
        }
        if($request->isPost() && $this->guard($request)){
            $formToken = $request->get($this->tokenName);
            $csrfToken =  $this->session->get($this->tokenName);
            if(!$csrfToken == $formToken){
                throw new InvalidTokenException();
            }
        }
        $this->next($request);
    }

}