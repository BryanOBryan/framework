<?php


namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class AuthenticationException extends  HttpException
{

    public function __construct()
    {
        parent::__construct(511);
    }
}