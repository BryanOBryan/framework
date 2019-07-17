<?php

namespace Kaliba\Http;

class Redirector
{
    /**
     * Create a new redirect response to a named route.
     *
     * @param  string  $location
     * @param  array   $parameters
     * @param  int     $status
     * @param  array   $headers
     * @return \Kaliba\Http\Redirect
     */
    public function to($location, $parameters=[], $status = 302, $headers = [])
    {
        $url = Uri::makeUrl(str_replace('.','/', $location), $parameters);

        return $this->createRedirect($url->getPath(), $status, $headers);
    }

    /**
     * Create a new redirect response.
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \Kaliba\Http\Redirect
     */
    protected function createRedirect($path, $status = 302, $headers)
    {
        return new Redirect($path, $status, $headers);
    }

}