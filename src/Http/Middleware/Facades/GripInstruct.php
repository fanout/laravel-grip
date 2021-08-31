<?php

namespace Fanout\LaravelGrip\Http\Middleware\Facades;

use Fanout\Grip\Data\GripInstruct as GripGripInstruct;
use Fanout\LaravelGrip\Errors\GripInstructNotAvailableError;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void add_channel( $channel )
 * @method static void set_status( int $status )
 * @method static void set_hold_long_poll( $timeout_secs = null )
 * @method static void set_hold_stream()
 * @method static void set_keep_alive( $data, int $timeout_secs )
 * @method static void set_next_link( ?string $value, int $timeout_secs = 0 )
 * @method static string build_keep_alive_header()
 * @method static array build_headers()
 */
class GripInstruct extends Facade {
    protected static function getFacadeAccessor() {
        return 'fanout-gripinstruct';
    }

    public static function is_valid(): bool {
        try {
            $facade_root = self::getFacadeRoot();
        } catch( GripInstructNotAvailableError $ex ) {
            return false;
        }
        return $facade_root !== null;
    }

    public static function set_meta( array $new_meta ) {
        /** @var GripGripInstruct $grip_instruct */
        $grip_instruct = self::getFacadeRoot();
        $grip_instruct->meta = $new_meta;
    }
}
