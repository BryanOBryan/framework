<?php
namespace Kaliba\View;

interface Viewable
{
    /**
     * Display html page or plain text 
     * @return mixed
     */
    public function view();
}