<?php

namespace Kaliba\Http\Exception;
use Kaliba\Http\Helpers\CodeList;

class HttpException extends \Exception
{
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if(func_num_args() == 1){
            if(is_numeric($message)){
                $code = $message;
                $codelist = new CodeList();
                $message = $codelist->getPhrase($code);
            }
        }
        parent::__construct($message, $code, $previous);
    }
}
