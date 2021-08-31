<?php

namespace Fanout\LaravelGrip\Grip;

use Fanout\Grip\Data\Item;
use Fanout\Grip\Engine\Publisher;
use GuzzleHttp\Promise\PromiseInterface;

class PrefixedPublisher extends Publisher {
    private string $prefix;

    public function __construct( $config = [], string $prefix = '' ) {
        parent::__construct( $config );
        $this->prefix = $prefix;
    }

    public function publish( string $channel, Item $item ): PromiseInterface {
        return parent::publish( $this->prefix . $channel, $item );
    }
}
