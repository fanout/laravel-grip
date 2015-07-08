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
        $request->grip_proxied = false;
        $request->grip_wscontext = null;
        $grip_signed = false;
        $grip_proxies = array();
        if (\Config::has('app.grip_proxies'))
            $grip_proxies = \Config::get('app.grip_proxies');
        if (array_key_exists('grip-sig', \Request::header()))
            foreach ($grip_proxies as $entry)
                if (\GripControl\GripControl::validate_sig(
                        \Request::header('grip-sig'), $entry['key']))
                {
                    $grip_signed = true;
                    break;
                }
        $content_type = null;
        if (array_key_exists('content-type', \Request::header()))
        {
            $content_type = \Request::header('content-type');
            $at = strpos($content_type, ';');
            if ($at !== false)
                $content_type = substr($content_type, 0, $at);
        }
        $accept_types = null;
        if (array_key_exists('accept', \Request::header()))
        {
            $accept_types = \Request::header('accept');
            $tmp = explode(',', $accept_types);
            $accept_types = array();
            foreach ($tmp as $accept_type)
                $accept_types[] = trim($accept_type);
        }
        $wscontext = null;
        if ($request->isMethod('post') && ($content_type ==
                'application/websocket-events' ||
                (!is_null($accept_types) && in_array(
                'application/websocket-events', $accept_types))))
        {
        }

        $response = $next($request);

        return $response;
    }
}

?>
