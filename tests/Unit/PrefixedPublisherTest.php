<?php

namespace Fanout\LaravelGrip\Tests\Unit;

use Fanout\Grip\Data\Item;
use Fanout\Grip\Engine\PublisherClient;
use Fanout\LaravelGrip\Grip\PrefixedPublisher;
use Fanout\LaravelGrip\Tests\TestCase;
use GuzzleHttp\Promise\FulfilledPromise;

class PrefixedPublisherTest extends TestCase {

    /** @test */
    function it_uses_prefix() {

        $item = new Item([]);

        $mock_publisher_client = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_publisher_client->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'prefixchan', $item );

        $publisher = new PrefixedPublisher([], 'prefix');
        $publisher->add_client($mock_publisher_client);

        $publisher->publish( 'chan', $item )
            ->wait();

    }

}
