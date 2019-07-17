<?php

namespace Kaliba\Http\Middleware;
use Kaliba\Http\Exception\LargePostDataException;
use Kaliba\Http\Middleware;
use Kaliba\Http\HttpException;
use Kaliba\Http\Request;
use Kaliba\Http\Response;

class POSTMiddleware extends Middleware
{

    /**
     * Handle incoming Request
     * @param Request $request
     * @return Response|void
     * @throws HttpException
     */
    public function handle(Request $request)
    { 
        if ($request->isPost() && $request->getContentLength() > $this->getMaxSize()) {
            throw new LargePostDataException();
        }
        $this->next($request);
    }

    /**
     * Get Maximum Post Size set in the ini file
     * @return float|int
     */
    private function getMaxSize()
    {
        if (is_numeric($postMaxSize = ini_get('post_max_size'))) {
            return (int) $postMaxSize;
        }
        $metric = strtoupper(substr($postMaxSize, -1));
        switch ($metric) {
            case 'K':
                return (int) $postMaxSize * 1024;
            case 'M':
                return (int) $postMaxSize * 1048576;
            case 'G':
                return (int) $postMaxSize * 1073741824;
            default:
                return (int) $postMaxSize;
        }
    }
}
