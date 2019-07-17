<?php

namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class PasswordResetInvalid extends HttpException
{

    public function __construct()
    {
        parent::__construct('Reset code not defined', 0, null);
    }

}