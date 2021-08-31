<?php


namespace Fanout\LaravelGrip;


use Fanout\LaravelGrip\Grip\PrefixedPublisher;
use Fanout\LaravelGrip\Http\Middleware\Facades\Grip;
use Fanout\LaravelGrip\Http\Middleware\GripMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class LaravelGripServiceProvider extends ServiceProvider {
    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'grip');
        $this->app->scoped('fanout-gripcontext', function() {
            $grip_proxy_required = Config::get('grip.grip_proxy_required');
            $serve_grip = new GripContext();
            $serve_grip->set_is_grip_proxy_required( $grip_proxy_required );
            return $serve_grip;
        });
        $this->app->scoped( 'fanout-grippublisher', function() {
            $grip_config = Config::get('grip.grip');
            $grip_prefix = Config::get('grip.prefix');
            return $grip_config !== null ? new PrefixedPublisher( $grip_config, $grip_prefix ) : null;
        });
        $this->app->scoped( 'fanout-gripinstruct', function() {
            return Grip::get_instruct();
        });
        $this->app->scoped( 'fanout-gripwebsocketcontext', function() {
            return Grip::get_ws_context();
        });
    }
    public function boot() {
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(GripMiddleware::class);
    }
}
