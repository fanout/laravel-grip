<?php

namespace Fanout\LaravelGrip\Http\Middleware\Facades;

use Fanout\Grip\Data\GripInstruct;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool is_handled()
 * @method static void set_is_handled( $value = true )
 * @method static bool is_proxied()
 * @method static void set_is_proxied( $value = true )
 * @method static bool is_signed()
 * @method static void set_is_signed( $value = true )
 * @method static bool is_grip_proxy_required()
 * @method static void set_is_grip_proxy_required( $value = true )
 * @method static bool has_instruct()
 * @method static GripInstruct get_instruct()
 * @method static GripInstruct start_instruct()
 * @method static WebSocketContext|null get_ws_context()
 * @method static void set_ws_context( WebSocketContext $ws_context )
 */
class Grip extends Facade {
    protected static function getFacadeAccessor() {
        return 'fanout-gripcontext';
    }
}
