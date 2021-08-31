<?php

namespace Fanout\LaravelGrip\Tests\Unit;

use Fanout\LaravelGrip\Http\Middleware\Facades\GripPublisher;
use Fanout\LaravelGrip\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ResolvePublisherTest extends TestCase {

    /** @test */
    function publisher_is_not_valid_with_no_config() {
        $this->assertFalse( GripPublisher::is_valid() );
    }

    /** @test */
    function publisher_is_valid_with_config() {
        Config::set( 'grip.grip', 'https://api.fanout.io/realm/realm?iss=realm&key=base64:geag121321=' );

        $this->assertTrue( GripPublisher::is_valid() );
    }

}
