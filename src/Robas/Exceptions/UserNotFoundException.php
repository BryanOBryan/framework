<?php

namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class UserNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct('User not found', 0, null);
    }
}