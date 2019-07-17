<?php

namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class UserBlockedException extends HttpException
{

    public function __construct()
    {
        parent::__construct('User is blocked', 0, null);
    }
}