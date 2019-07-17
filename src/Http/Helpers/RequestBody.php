<?php

namespace Kaliba\Http\Helpers;
use Kaliba\Http\Stream;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 * This class is derived from Slim Http Package
 */
class RequestBody extends Stream
{
    /**
     * Create a new RequestBody.
     */
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);              
        parent::__construct($stream);
    }
}
