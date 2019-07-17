<?php

namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class PasswordResetTimeout extends HttpException
{

    public function __construct()
    {
        parent::__construct('Reset code has expired', 0, null);
    }
}