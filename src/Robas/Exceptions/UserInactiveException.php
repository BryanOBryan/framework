<?php

namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class UserInactiveException extends HttpException
{

    public function __construct()
    {
        parent::__construct('User is inactive', 0, null);
    }
}