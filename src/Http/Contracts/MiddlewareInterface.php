<?php


namespace Kaliba\Http\Contracts;


use Kaliba\Http\Request;
use Kaliba\Http\Response;

interface MiddlewareInterface
{

    /**
     * Handles an incoming request.
     *
     * @param  Request $request Request Instance
     * @return Response
     */
    public function handle(Request $request);
}