<?php

namespace Fanout\LaravelGrip\Http\Middleware;


use Closure;
use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Fanout\Grip\Errors\ConnectionIdMissingError;
use Fanout\Grip\Errors\WebSocketDecodeEventError;
use Fanout\LaravelGrip\Http\Middleware\Facades\Grip;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripInstruct;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GripMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle( Request $request, Closure $next): Response {

        if( Grip::is_handled() ) {
            Log::debug('Already ran for this request, returning true');
            return $next($request);
        }

        Grip::set_is_handled();

        if( !GripPublisher::is_valid() ) {
            Log::error( 'ERROR - No GRIP configuration is provided.' );
            return response("No GRIP configuration provided.\n", 500);
        }

        $this->setup_grip( $request );

        if( Grip::is_proxied() ) {
            Log::debug('Request is proxied');
        } else {
            Log::debug('Request is not proxied');
        }
        if( Grip::is_signed() ) {
            Log::debug('Request is signed');
        } else {
            Log::debug('Request is not signed');
        }

        if( Grip::is_grip_proxy_required() && !Grip::is_proxied() ) {
            // If we require a GRIP proxy but we detect there is
            // not one, we needs to fail now
            Log::error('ERROR - grip_proxy_required is true, but request not proxied.');
            return response('Not Implemented.' . PHP_EOL, 501);
        }

        $ws_context = $this->setup_ws_context( $request );
        if( $ws_context === 'connection-id-missing' ) {
            return response('WebSocket event missing connection-id header.' . PHP_EOL, 400);
        }
        if( $ws_context === 'websocket-decode-error' ) {
            return response( 'Error parsing WebSocket events.' . PHP_EOL, 400 );
        }

        $response = $next($request);

        if( $ws_context !== null) {
            $this->apply_ws_context( $ws_context, $response );
        } else {
            $this->apply_grip_instruct( $response );
        }

        return $response;
    }

    function setup_grip(Request $request ) {

        try {
            Log::debug('ServeGrip#pre_middleware - start');

            $grip_sig = $request->header( 'grip-sig' );
            if( empty($grip_sig) ) {
                return;
            }
            Log::debug( 'grip_sig header exists.' );

            $clients = GripPublisher::get_clients();
            if( empty($clients) ) {
                Log::warning( 'no publisher clients configured.' );
                return;
            }

            // If every client needs signing, then we mark as requires_signed;
            $requires_signed = true;
            foreach( $clients as $client ) {
                if( empty($client->get_verify_key()) ) {
                    $requires_signed = false;
                    break;
                }
            }

            // If all publishers have keys, then only consider this signed if
            // the grip sig has been signed by one of them
            $is_signed = false;
            if( $requires_signed ) {
                Log::debug( 'requires validating grip signature' );
                foreach( $clients as $client ) {
                    // At this point, all clients have a verify key
                    Log::debug('validating: ' . $grip_sig . ' with ' . $client->get_verify_key() );
                    if( JwtAuth::validate_signature( $grip_sig, $client->get_verify_key(), $client->get_verify_iss() ) ) {
                        Log::debug('validated' );
                        $is_signed = true;
                        break;
                    }
                    Log::debug('not validated' );
                }
                if (!$is_signed) {
                    Log::debug( 'could not validate grip signature' );
                    // If we need to be signed but we got here without a signature,
                    // we don't even consider this proxied.
                    return;
                }
            }

            Grip::set_is_signed( $is_signed );
            Grip::set_is_proxied();

        } finally {
            Log::debug('ServeGrip#pre_middleware - end');
        }
    }

    function setup_ws_context( Request $request ) {
        if( !WebSocketContext::is_ws_over_http() ) {
            Log::debug("is_ws_over_http false");
            return null;
        }
        Log::debug("is_ws_over_http true");

        try {
            WebSocketContext::set_input($request->getContent());
            $ws_context = WebSocketContext::from_req();
        } catch( ConnectionIdMissingError $ex ) {
            Log::error( 'ERROR - connection-id header needed' );
            return 'connection-id-missing';
        } catch( WebSocketDecodeEventError $ex ) {
            Log::error( 'ERROR - error parsing websocket events' );
            return 'websocket-decode-error';
        }

        Grip::set_ws_context( $ws_context );
        return $ws_context;
    }

    function apply_grip_instruct( Response $response ) {
        if( !Grip::has_instruct() ) {
            return;
        }

        if( $response->getStatusCode() === 304 ) {
            // Code 304 only allows certain headers.
            // Some web servers strictly enforce this.
            // In that case we won't be able to use
            // Grip- headers to talk to the proxy.
            // Switch to code 200 and use Grip-Status
            // to specify intended status.
            Log::debug('Using gripInstruct setStatus header to handle 304');
            $response->setStatusCode( 200 );
            GripInstruct::set_status( 304 );
        }

        // We can safely use Response#header() as the header values are always strings.
        foreach( GripInstruct::build_headers() as $header_name => $header_value ) {
            $response->header( $header_name, $header_value );
        }
    }

    function apply_ws_context( WebSocketContext $ws_context, Response $response ) {
        if( $response->getStatusCode() === 200 || $response->getStatusCode() === 204 ) {
            // We can safely use Response#header() as the header values are always strings.
            foreach( $ws_context->to_headers() as $header_name => $header_value ) {
                $response->header( $header_name, $header_value );
            }

            // Add outgoing events to response
            $out_events = $ws_context->get_outgoing_events();
            $out_events_encoded = strval( WebSocketEvent::encode_events( $out_events ) );
            if( !empty( $out_events_encoded ) ) {
                $response_content = $response->getContent();
                $response_content .= $out_events_encoded;
                $response->setContent($response_content);
                $response->setStatusCode(200);
            }
        }
    }

}
