<?php

namespace Fanout\LaravelGrip\Tests\Unit;

use Fanout\LaravelGrip\Errors\GripInstructAlreadyStartedError;
use Fanout\LaravelGrip\Errors\GripInstructNotAvailableError;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripInstruct;
use Fanout\LaravelGrip\Http\Middleware\Facades\Grip;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripWebSocket;
use Fanout\LaravelGrip\Http\Middleware\GripMiddleware;
use Fanout\LaravelGrip\Tests\TestCase;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Ramsey\Uuid\Uuid;

class GripMiddlewareTest extends TestCase {

    const SAMPLE_KEY = 'sample_key';

    function config_grip( $params = null ) {
        $clients = $params['clients'] ?? null;
        if( $clients === null ) {
            $grip = 'https://api.fanout.io/realm/realm?iss=realm';

            $use_sample_key = $params['use_sample_key'] ?? false;
            if($use_sample_key) {
                $key = self::SAMPLE_KEY;
            } else {
                $key = $params['use_key'] ?? false;
            }
            if( !empty( $key ) ) {
                $grip .= '&key=base64:' . base64_encode( $key );
            }
            $clients = [ $grip ];
        }

        $grip_proxy_required = $params['grip_proxy_required'] ?? false;

        Config::set( 'grip', [
            'grip' => $clients,
            'prefix' => '',
            'grip_proxy_required' => $grip_proxy_required,
        ] );
    }

    function create_request( $params = null ): Request {

        $grip_sig = $params['grip_sig'] ?? false;
        $is_websocket = $params['is_websocket'] ?? false;
        $has_connection_id = $params['has_connection_id'] ?? false;
        $body = $params['body'] ?? null;

        $server = [];
        if($grip_sig) {
            $exp = time() + 60 * 60; // 1 hour ago from now
            if ($grip_sig === 'expired') {
                $exp = time() - 60 * 60; // 1 hour ago
            }
            $sig = JWT::encode([
                'iss' => 'realm',
                'exp' => $exp,
            ], self::SAMPLE_KEY );
            $server['HTTP_GRIP_SIG'] = $sig;
        }
        if($is_websocket) {
            $_SERVER[ 'HTTP_CONTENT_TYPE' ] = 'application/websocket-events';
            $_SERVER[ 'REQUEST_METHOD' ] = 'POST';
        } else {
            unset( $_SERVER[ 'HTTP_CONTENT_TYPE' ] );
            unset( $_SERVER[ 'REQUEST_METHOD' ] );
        }
        if($has_connection_id) {
            $uuid = Uuid::uuid4();
            $_SERVER[ 'HTTP_CONNECTION_ID' ] = $uuid->toString();
        } else {
            unset( $_SERVER[ 'HTTP_CONNECTION_ID' ] );
        }
        return new Request( [], [], [], [], [], $server, $body );
    }

    /** @test */
    function grip_middleware_throws_500_if_not_configured() {
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();

        $response = $grip_middleware->handle( $req, function() {});

        $this->assertEquals( 500, $response->getStatusCode() );
        $this->assertEquals( "No GRIP configuration provided.\n", $response->content() );
    }

    /** @test */
    function grip_middleware_is_installed() {

        $this->config_grip();
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( Grip::is_handled() );
            return response( null, 200 );
        });

    }

    /** @test */
    function grip_middleware_detects_when_not_proxied() {

        $this->config_grip();
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( Grip::is_proxied() );
            return response( null, 200 );
        });

    }

    /** @test */
    function grip_middleware_assumes_no_proxy_when_no_clients() {
        $this->config_grip(['clients' => []]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( Grip::is_proxied() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_proxy_requires_no_sig() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( Grip::is_proxied() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_proxy_requires_and_has_sig() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( Grip::is_proxied() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_no_proxy_when_requires_and_has_expired_sig() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => 'expired']);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( Grip::is_proxied() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_no_proxy_when_requires_and_has_invalid_sig() {
        $this->config_grip(['use_key' => 'foo']);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( Grip::is_proxied() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_not_signed_when_requires_no_sig() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( Grip::is_signed() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_detects_signed_when_requires_and_has_sig() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( Grip::is_signed() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_throws_501_when_requires_proxy_but_not_proxied() {
        $this->config_grip(['grip_proxy_required' => true]);
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();

        /** @var Response $response */
        $response = $grip_middleware->handle( $req, function() {} );

        $this->assertEquals( 501, $response->getStatusCode() );
        $this->assertEquals( "Not Implemented.\n", $response->content() );
    }

    /** @test */
    function grip_middleware_allows_start_grip_instruct_when_proxied() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $grip_instruct = Grip::start_instruct();
            $this->assertNotNull( $grip_instruct );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_does_not_allow_start_grip_instruct_when_not_proxied() {
        $this->config_grip();
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->expectException( GripInstructNotAvailableError::class );
            Grip::start_instruct();
        });
    }

    /** @test */
    function grip_middleware_does_not_allow_start_grip_instruct_multiple_times() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->expectException( GripInstructAlreadyStartedError::class );
            Grip::start_instruct();
            Grip::start_instruct();
        });
    }

    /** @test */
    function grip_middleware_allows_grip_instruct_facade_when_proxied() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( GripInstruct::is_valid() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_does_not_allow_grip_instruct_facade_when_not_proxied() {
        $this->config_grip();
        $req = $this->create_request();

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( GripInstruct::is_valid() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_allows_grip_instruct_facade_multiple_times() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            GripInstruct::is_valid();
            $this->assertTrue( GripInstruct::is_valid() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_does_not_prevent_grip_instruct_facade_after_start_instruct() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            Grip::start_instruct();
            $this->assertTrue( GripInstruct::is_valid() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_prevents_start_instruct_after_grip_instruct_facade() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            GripInstruct::is_valid();

            $this->expectException( GripInstructAlreadyStartedError::class );
            Grip::start_instruct();
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_adds_headers_simple() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            GripInstruct::add_channel('foo');
            return response( null, 200 );
        });
        $this->assertEquals( 'foo', $response->headers->get( 'Grip-Channel' ) );
    }

    /** @test */
    function grip_middleware_doesnt_add_headers_unless_instruct_used() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response( null, 200 );
        });
        $this->assertEmpty( $response->headers->get( 'Grip-Channel' ) );
    }

    /** @test */
    function grip_middleware_handle_304_status() {
        $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            GripInstruct::add_channel('foo');
            return response( null, 304 );
        });
        $this->assertEquals( 200, $response->getStatusCode() );
        $this->assertEquals( '304', $response->headers->get( 'Grip-Status' ) );
    }

    /** @test */
    function grip_middleware_throws_400_when_is_websocket_but_no_connection_id() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response(null, 200);
        });
        $this->assertEquals( 400, $response->getStatusCode() );
        $this->assertEquals( 'WebSocket event missing connection-id header.' . PHP_EOL, $response->content() );
    }

    /** @test */
    function grip_middleware_makes_ws_context_when_is_websocket_and_connection_id_present() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertTrue( GripWebSocket::is_valid() );
            return response(null, 200);
        });
    }

    /** @test */
    function grip_middleware_makes_ws_context_and_decodes_itwhen_is_websocket_and_connection_id_present_with_valid_event() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => 'true', 'body' => "TEXT 5\r\nHello\r\n"]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $data = GripWebSocket::recv();
            $this->assertEquals( 'Hello', $data );
            return response(null, 200);
        });
    }

    /** @test */
    function grip_middleware_throws_400_when_is_websocket_and_connection_id_but_event_is_malformed() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => 'true', 'body' => "TEXT 5\r\n"]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {

        });
        $this->assertEquals( 400, $response->getStatusCode() );
        $this->assertEquals( 'Error parsing WebSocket events.' . PHP_EOL, $response->content() );
    }

    /** @test */
    function grip_middleware_makes_no_ws_context_when_not_websocket() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $grip_middleware->handle( $req, function() {
            $this->assertFalse( GripWebSocket::is_valid() );
            return response( null, 200 );
        });
    }

    /** @test */
    function grip_middleware_outputs_no_ws_headers_when_not_websocket() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response( null, 200 );
        });
        $this->assertEquals( '', $response->headers->get( 'Content-Type' ) );
    }

    /** @test */
    function grip_middleware_outputs_ws_headers_when_is_websocket_and_connection_id_present() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            GripWebSocket::accept();
            return response(null, 200);
        });
        $this->assertEquals( 'application/websocket-events', $response->headers->get( 'Content-Type' ) );
        $this->assertEquals( 'grip', $response->headers->get( 'Sec-WebSocket-Extensions' ) );
    }

    /** @test */
    function grip_middleware_does_not_output_ws_headers_when_is_websocket_and_connection_id_present_but_code_not_200() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response(null, 500);
        });
        $this->assertEquals( '', $response->headers->get( 'Content-Type' ) );
    }

    /** @test */
    function grip_middleware_outputs_ws_events_when_is_websocket_and_connection_id_present_and_events_are_sent() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            GripWebSocket::send( 'foo' );
            return response(null, 200);
        });

        $this->assertEquals( "TEXT 5\r\nm:foo\r\n", $response->getContent() );
    }

    /** @test */
    function grip_middleware_outputs_changes_204_to_200_when_is_websocket_and_connection_id_present_and_events_are_sent() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            GripWebSocket::send( 'foo' );
            return response(null, 204);
        });

        $this->assertEquals( 200, $response->getStatusCode() );
    }

    /** @test */
    function grip_middleware_outputs_no_ws_events_when_is_websocket_and_connection_id_present_and_events_are_not_sent() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response(null, 200);
        });

        $this->assertEmpty( $response->getContent() );
    }

    /** @test */
    function grip_middleware_keeps_204_when_is_websocket_and_connection_id_present_and_events_are_not_sent_and_code_is_204() {
        $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);

        $grip_middleware = new GripMiddleware();
        $response = $grip_middleware->handle( $req, function() {
            return response(null, 204);
        });

        $this->assertEquals( 204, $response->getStatusCode() );
    }
}
