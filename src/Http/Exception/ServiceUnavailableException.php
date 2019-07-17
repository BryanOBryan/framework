<?php

namespace Kaliba\Http\Exception;

class ServiceUnavailableException extends HttpException
{

    public function __construct()
    {
        parent::__construct(503);
    }
}