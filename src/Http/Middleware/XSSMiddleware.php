<?php

namespace Kaliba\Http\Middleware;
use Kaliba\Http\Middleware;
use Kaliba\Http\Request;
use Kaliba\Http\Response;
use Kaliba\Security\XSSFilter;

class XSSMiddleware extends Middleware
{

    /**
     * Handle Incoming Request
     * @param Request $request
     * @return Response|void
     */
    public function handle(Request $request) 
    {
        if($request->data() != null){
            $filter = new XSSFilter();
            $data = $request->data();
            $clean = $filter->clean($data);
            $request = $request->withParsedBody($clean);
        }
        $this->next($request);
    }

}
