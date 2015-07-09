<?php

/*  laravelgrip.php
    ~~~~~~~~~
    This module implements the general GRIP functions.
    :authors: Konstantin Bokarius.
    :copyright: (c) 2015 by Fanout, Inc.
    :license: MIT, see LICENSE for more details. */

namespace LaravelGrip;

use Closure;

function verify_is_websocket()
{
    if (is_null(\Request::instance()->grip_wscontext))
        throw new NonWebSocketRequestException(
                'This endpoint only allows WebSocket requests.');
}

?>
