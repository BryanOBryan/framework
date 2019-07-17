<?php


namespace Kaliba\Robas\Exceptions;
use Kaliba\Http\Exception\HttpException;

class PasswordIncorrectException extends HttpException
{

    public function __construct()
    {
        parent::__construct('Incorrect password', 0, null);
    }
}