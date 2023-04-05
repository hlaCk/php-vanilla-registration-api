<?php

namespace App\Helpers;

/**
 *
 */
class Response
{
    /**
     * @param int $code
     * @param string $message
     *
     * @return mixed
     */
    public static function make(int|string $code = 1, $message = '')
    {
        $code = trim($code);
        echo $code;
        if( request()->has('s-r') )
        {
            dd(__LINE__,$message);
        }
        die();
        return $message;
    }
}