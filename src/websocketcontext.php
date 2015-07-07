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
                $this->in_events[0]->type == 'OPEN');
    }

    public function accept()
    {
        $this->accepted = true;
    }

    public function close($code=null)
    {
        $this->closed = true;
        if (!is_null($code))
            $this->out_close_code = $code
        else
            $this->out_close_code = 0
    }

    public function can_recv()
    {
        $event_types = array('TEXT', 'BINARY', 'CLOSE', 'DISCONNECT');
        foreach ($this->in_events as $event)
        {
            if (in_array($event->type, $event_types))
                return true;
        }
        return false
    }

    public function recv()
    {
        $event = null
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
            if ($event->type == 'TEXT' && is_null($event->content))
                return '';
            elseif ($event->type == 'BINARY' && is_null($event->content))
                return ''.ENCODE AS BINARY;
            return $event->content;
        }
    }
}

?>
