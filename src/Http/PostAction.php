<?php
namespace Kaliba\Http;

interface PostAction
{
    /**
     * Perform action on POST REQUEST
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request);
}