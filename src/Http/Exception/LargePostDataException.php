<?php


namespace Kaliba\Http\Exception;

class LargePostDataException extends HttpException
{
    public function __construct()
    {
        parent::__construct(601);
    }
}