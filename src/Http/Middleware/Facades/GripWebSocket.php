<?php

namespace Fanout\LaravelGrip\Http\Middleware\Facades;

use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Illuminate\Support\Facades\Facade;
use Psr\Http\Message\StreamInterface;

/**
 * @method static bool is_opening()
 * @method static bool is_accepted()
 * @method static bool is_closed()
 * @method static int get_close_code()
 * @method static bool can_recv()
 * @method static void accept()
 * @method static void disconnect()
 * @method static void close( int $close_code = 0 )
 * @method static void send( StreamInterface|string $data )
 * @method static void send_binary( StreamInterface $data )
 * @method static void send_control( StreamInterface|string $data )
 * @method static void subscribe( string $channel )
 * @method static void unsubscribe( string $channel )
 * @method static void detach()
 * @method static string|null recv()
 * @method static StreamInterface|string|null recv_raw()
 * @method static WebSocketEvent[] get_outgoing_events()
 * @method static array to_headers()
 * @method static bool is_ws_over_http()
 * @method static void set_input( StreamInterface|string|null $value )
 * @method static WebSocketContext from_req( string $prefix = '' )
 */
class GripWebSocket extends Facade {
    protected static function getFacadeAccessor() {
        return 'fanout-gripwebsocketcontext';
    }

    public static function is_valid(): bool {
        return self::getFacadeRoot() !== null;
    }
}
