<?php

namespace Fanout\LaravelGrip\Http\Middleware\Facades;

use Fanout\Grip\Data\Item;
use Fanout\Grip\Engine\Publisher;
use Fanout\Grip\Engine\PublisherClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void apply_config( $config )
 * @method static void add_client( PublisherClient $client )
 * @method static PromiseInterface publish( string $channel, Item $item )
 * @method static PromiseInterface publish_formats( string $channel, $formats, ?string $id = null, ?string $prev_id = null )
 * @method static PromiseInterface publish_http_response( string $channel, $data, ?string $id = null, ?string $prev_id = null )
 * @method static PromiseInterface publish_http_stream( string $channel, $data, ?string $id = null, ?string $prev_id = null )
 * @method static PromiseInterface publish_websocket_message( string $channel, $data, ?string $id = null, ?string $prev_id = null )
 *
 */
class GripPublisher extends Facade {
    protected static function getFacadeAccessor() {
        return 'fanout-grippublisher';
    }

    public static function is_valid(): bool {
        return self::getFacadeRoot() !== null;
    }

    public static function get_clients(): array {
        /** @var Publisher $publisher */
        $publisher = self::getFacadeRoot();
        return $publisher->clients;
    }
}
