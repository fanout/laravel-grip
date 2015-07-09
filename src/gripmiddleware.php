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
            $cid = null;
            if (array_key_exists('connection-id', \Request::header()))
                $cid = \Request::header('content-type');
            $meta = array();
            foreach (\Request::header() as $key => $value)
                if (strpos($key, 'meta') === 0)
                    $meta[substr($key, 5)] = $value[0];
            $events = null;
            try
            {
                $events = \GripControl\GripControl::decode_websocket_events(
                        $request->getContent());
            }
            catch (\RuntimeException $e) {
                return new \Symfony\Component\HttpFoundation\Response(
                        "Error parsing WebSocket events.\n", 400);
            }
            $wscontext = new WebSocketContext($cid, $meta, $events);
        }
        $request->grip_proxied = $grip_signed;
        $request->ws_context = $wscontext;
        try
        {
            $response = $next($request);
        }
        catch (NonWebSocketRequestException $e) {
            return new \Symfony\Component\HttpFoundation\Response(
                    $e->getMessage(), 400);
        }

        #foreach ($meta as $key => $value)
        #    Print $key . ':' . $value . '<br>';

        return $response;
    }
}

function verify_is_websocket()
{
    if (is_null(\Request::instance()->ws_context))
        throw new NonWebSocketRequestException(
                'This endpoint only allows WebSocket requests.');
}

?>
