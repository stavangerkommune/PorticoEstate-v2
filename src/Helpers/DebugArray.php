<?php

namespace App\Helpers;

class DebugArray
{
    public static function debug($array): void
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }
 
}
