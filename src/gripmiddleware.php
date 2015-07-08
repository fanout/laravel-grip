<?php

/*  gripmiddleware.php
    ~~~~~~~~~
    This module implements the GRIP middleware class.
    :authors: Konstantin Bokarius.
    :copyright: (c) 2015 by Fanout, Inc.
    :license: MIT, see LICENSE for more details. */

namespace LaravelGrip;

use Closure;

class GripMiddleware
{
    public function handle($request, Closure $next)
    {
        Print "Before request\r\n";

        $response = $next($request);

        Print "After successful\r\n";

        return $response;
    }
}

?>
