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
        $request->grip_wscontext = $wscontext;
        try
        {
            $response = $next($request);
        }
        catch (NonWebSocketRequestException $e) {
            return new \Symfony\Component\HttpFoundation\Response(
                    $e->getMessage(), 400);
        }
        if (!is_null($request->grip_wscontext) &&
                $response->getStatusCode() == 200)
        {
            $wscontext = $request->grip_wscontext;
            $meta_remove = array();
            foreach ($wscontext->orig_meta as $key => $value) {
                $found = false;
                foreach ($wscontext->meta as $nkey => $nvalue)
                    if (strtolower($nkey) == $key)
                    {
                        $found = true;
                        break;
                    }
                if (!$found)
                    $meta_remove[] = $key;
            }
            $meta_remove = array_unique($meta_remove);
            $meta_set = array();
            foreach ($wscontext->meta as $key => $value) {
                $lname = strtolower($key);
                $need_set = true;
                foreach ($wscontext->orig_meta as $okey => $ovalue) {
                    if ($lname == $okey && $value == $ovalue)
                    {
                        $need_set = false;
                        break;
                    }
                }
                if ($need_set)
                    $meta_set[$lname] = $value;
            }
            $events = array();
            if ($wscontext->accepted)
                $events[] = new \GripControl\WebSocketEvent('OPEN');
            $events = array_merge($events, $wscontext->out_events);
            if ($wscontext->closed)
                $events[] = new \GripControl\WebSocketEvent('CLOSE',
                        pack("n", $wscontext->out_close_code));
            $response->setContent(
                    \GripControl\GripControl::encode_websocket_events($events));
            $response->header('Content-Type', 'application/websocket-events');
            $response->header('Sec-WebSocket-Extensions', 'grip');
            foreach ($meta_remove as $key)
                $response->header('Set-Meta-' . $key, '');
            foreach ($meta_set as $key => $value)
                $response->header('Set-Meta-' . $key, $value);
        }
        elseif (!is_null($request->grip_hold))
        {
            if (!$request->grip_proxied &&
                    \Config::has('app.grip_proxy_required') &&
                    \Config::get('app.grip_proxy_required'))
                return new \Symfony\Component\HttpFoundation\Response(
                        'Not implemented.\n', 501);
            $channels = $request->grip_channels;
            $prefix = LaravelGrip\get_prefix();
            if ($prefix != '')
                foreach ($channels as $channel)
                    $channel->name += $prefix;
            
        }
        return $response;
    }
}

?>
