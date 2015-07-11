<?php

/*  laravelgrip.php
    ~~~~~~~~~
    This module implements the general GRIP functions.
    :authors: Konstantin Bokarius.
    :copyright: (c) 2015 by Fanout, Inc.
    :license: MIT, see LICENSE for more details. */

namespace LaravelGrip;

use Closure;

$grip_pubcontrol = null;

function verify_is_websocket()
{
    if (is_null(\Request::instance()->grip_wscontext))
        throw new NonWebSocketRequestException(
                'This endpoint only allows WebSocket requests.');
}

function get_wscontext()
{
    if (!is_null(\Request::instance()->grip_wscontext))
        return \Request::instance()->grip_wscontext;
    return null;
}

function set_hold_longpoll($channels, $timeout=null)
{
    \Request::instance()->grip_hold = 'response';
    \Request::instance()->grip_channels = convert_channels($channels);
    \Request::instance()->grip_timeout = $timeout;
}

function set_hold_stream($channels)
{
    \Request::instance()->grip_hold = 'stream';
    \Request::instance()->grip_channels = convert_channels($channels);
}

function is_grip_proxied()
{
    if (\Request::instance()->grip_proxied)
        return true;
    return false;
}

function convert_channels($channels)
{
    if ($channels instanceof \GripControl\Channel || is_string($channels))
        $channels = array($channels);
    $out = array();
    foreach ($channels as $channel)
    {
        if (is_string($channel))
            $channel = new \GripControl\Channel($channel);
        $out[] = $channel;
    }
    return $out;
}

function get_prefix()
{
    if (\Config::has('app.grip_prefix'))
        return \Config::get('app.grip_prefix');
    return '';
}

function publish($channel, $formats, $id=null, $prev_id=null)
{
    $pub = get_pubcontrol();
    $pub->publish(get_prefix() . $channel,
            new \PubControl\Item($formats, $id, $prev_id));
}

function publish_async($channel, $formats, $id=null, $prev_id=null,
        $callback=null)
{
    $pub = get_pubcontrol();
    $pub->publish_async(get_prefix() . $channel,
            new \PubControl\Item($formats, $id, $prev_id), $callback);
}

function get_pubcontrol()
{
    global $grip_pubcontrol;
    if (is_null($grip_pubcontrol))
    {
        $pc = new \GripControl\GripPubControl();
        if (\Config::has('app.grip_proxies'))
        {
            $pc->apply_grip_config(\Config::get('app.grip_proxies'));
        }
        if (\Config::has('app.publish_servers'))
        {
            $pc->apply_config(\Config::get('app.publish_servers'));
        }
        register_shutdown_function(array($pc, 'finish'));
        $grip_pubcontrol = $pc;
    }
    return $grip_pubcontrol;
}

?>
