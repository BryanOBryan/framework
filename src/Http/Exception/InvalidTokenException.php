<?php


namespace Kaliba\Http\Exception;

class InvalidTokenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(600);
    }
}