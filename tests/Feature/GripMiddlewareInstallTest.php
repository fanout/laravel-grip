<?php

namespace Fanout\LaravelGrip\Tests\Feature;

use Fanout\LaravelGrip\Http\Middleware\Facades\Grip;
use Fanout\LaravelGrip\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class GripMiddlewareInstallTest extends TestCase {
    function setup_route() {
        Route::get('/', function () {
            return '';
        });
    }

    /** @test */
    function grip_middleware_is_installed() {

        $this->setup_route();

        $this->get( '/' );
        $this->assertTrue( Grip::is_handled() );

    }
}
