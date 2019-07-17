<?php
namespace Kaliba\Http;

interface GetAction
{
    /**
     * Perform action on GET REQUEST
     * @param Request $request
     * @return mixed
     */
    public function get(Request $request);
}