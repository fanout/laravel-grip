<?php

/*  websocketcontext.php
    ~~~~~~~~~
    This module implements the WebSocketContext class.
    :authors: Konstantin Bokarius.
    :copyright: (c) 2015 by Fanout, Inc.
    :license: MIT, see LICENSE for more details. */

namespace LaravelGrip;

class WebSocketContext
{
    public function __construct($id, $meta, $in_events)
    {
        $this->id = $id;
        $this->in_events = $in_events;
        $this->read_index = 0;
        $this->accepted = false;
        $this->close_code = null;
        $this->closed = false;
        $this->out_close_code = null;
        $this->out_events = array();
        $this->orig_meta = $meta;
        $this->meta = $meta;
    }

    public function is_opening()
    {
        return (!is_null($this->in_events && count($this->in_events) > 0 &&
                $this->in_events[0]->type == 'OPEN'));
    }

    public function accept()
    {
        $this->accepted = true;
    }

    public function close($code=null)
    {
        $this->closed = true;
        if (!is_null($code))
            $this->out_close_code = $code;
        else
            $this->out_close_code = 0;
    }

    public function can_recv()
    {
        $event_types = array('TEXT', 'BINARY', 'CLOSE', 'DISCONNECT');
        foreach ($this->in_events as $event)
        {
            if (in_array($event->type, $event_types))
                return true;
        }
        return false;
    }

    public function recv()
    {
        $event = null;
        $event_types = array('TEXT', 'BINARY', 'CLOSE', 'DISCONNECT');
        while (is_null($recv_event) &&
                $this->read_index < count($this->in_events))
        {
            if (in_array($this->in_events[$this->read_index]->type, $event_types))
                $event = $this->in_events[$this->read_index];
            elseif ($event->type == 'PING')
                $this->out_events[] = new \GripControl\WebSocketEvent('PONG');
            $this->read_index += 1;
        }
        if (is_null($event)) {
            throw new \RuntimeException('read from empty buffer');
        }
        if ($event->type == 'TEXT' || $event->type == 'BINARY') {
            if (is_null($event->content))
                return '';
            return $event->content;
        }
        elseif ($event->type == 'CLOSE')
        {
            if (!is_null($event->content) && strlen($event->content) == 2)
            {
                $this->close_code = unpack("n", $event->content)[0];
            }
            return null;
        }
        else {
            throw new \RuntimeException('client disconnected unexpectedly');
        }
    }

    public function send($message)
    {
        $this->out_events[] = new \GripControl\WebSocketEvent('TEXT',
                'm:' . message);
    }

    public function send_binary($message)
    {
        $this->out_events[] = new \GripControl\WebSocketEvent('BINARY',
                'm:' . message);
    }

    public function send_control($message)
    {
        $this->out_events[] = new \GripControl\WebSocketEvent('TEXT',
                'c:' . message);
    }

    public function subscribe($channel)
    {
        $args = array();
        $args['channel'] = self::get_prefix() . $channel;
        $this->send_control(\GripControl\GripControl::websocket_control_message(
            'subscribe', $args));
    }

    public function unsubscribe($channel)
    {
        $args = array();
        $args['channel'] = self::get_prefix() . $channel;
        $this->send_control(\GripControl\GripControl::websocket_control_message(
            'unsubscribe', $args));
    }

    public function detach($channel)
    {
        $args = array();
        $args['channel'] = self::get_prefix() . $channel;
        $this->send_control(\GripControl\GripControl::websocket_control_message(
            'detach'));
    }

    private static function get_prefix()
    {
        if (Config::has('grip_prefix'))
            return Config::get('grip_prefix');
        return '';
    }
}
?>
