<?php
namespace Kaliba\Http;

interface AjaxAction
{
    /**
     * Perform action on AJAX REQUEST
     * @param Request $request
     * @return mixed
     */
    public function ajax(Request $request);
}