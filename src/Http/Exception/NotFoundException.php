<?php
namespace Kaliba\Http\Exception;

class NotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404);
    }
}