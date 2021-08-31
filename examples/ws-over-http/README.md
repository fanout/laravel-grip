## WebSocket-over-HTTP Echo / Broadcast Example

The following example shows how to set up laravel-grip for use in a Laravel
project with the WebSocket-over-HTTP protocol.

The example assumes a new Laravel project, but adding to an existing project
should possible in a similar fashion.

Assume a brand new installation of Laravel in a directory called `~/sites/grip-demo`.
This can be created using the `laravel` command described at
`https://laravel.com/docs/8.x/installation`.

```
cd ~/sites
laravel new grip-demo
```

### Add laravel-grip

```
cd ~/sites/grip-demo
composer require fanout/laravel-grip 
```

Add `config/grip.php`:
```php
return [
    'grip' => 'http://localhost:5561/'
];
```

Add the following to `routes/api.php`:
```php
use Fanout\LaravelGrip\Http\Middleware\Facades\GripPublisher;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripWebSocket;

/* ... */

Route::post('/ws/websocket/{channel}', function( Request $req, $channel ) {
    // Require WebSocket
    if( !GripWebSocket::is_valid() ) {
        return response( '[not a websocket request]' . PHP_EOL, 400 );
    }

    if( GripWebSocket::is_opening() ) {
        // Open the WebSocket and subscribe to a channel:
        GripWebSocket::accept();
        GripWebSocket::subscribe( $channel );
    }

    while( GripWebSocket::can_recv() ) {
        $message = GripWebSocket::recv();

        if ($message === null) {
            // If return value is undefined then connection is closed
            GripWebSocket::close();
            break;
        }

        // Echo the message
        GripWebSocket::send( $message );
    }

    return response( null, 204 );
});

Route::get('/ws/broadcast/{channel}/{message}', function( $channel, $message ) {
    ob_start();
    echo 'Channel: ' . $channel . PHP_EOL;
    echo 'Message: ' . $message . PHP_EOL;
    GripPublisher::publish_websocket_message( $channel, $message . PHP_EOL )
        ->then(function() {
            echo 'Publish Successful!' . PHP_EOL;
        })
        ->otherwise(function($e) {
            echo 'Publish Fail!' . PHP_EOL;
            echo json_encode($e) . PHP_EOL;
        })
        ->wait();
    return ob_get_clean();
});
```

### Run Demo

Set up Pushpin with a route:
```
* localhost:3000
```

In one Terminal window, run Pushpin
```
% pushpin
```

In another Terminal window, start Laravel:
```
% php artisan serve --port 3000
```

In another Terminal window, hit the *websocket* endpoint with wscat
```
% wscat --connect ws://localhost:7999/api/ws/websocket/test
```

You should see a prompt where you may enter a message.  This application acts as an
echo server, and any text you enter will be repeated back to you.

Now, in yet another Terminal window, hit the *broadcast* endpoint with curl:
```
% curl -i http://localhost:7999/api/ws/broadcast/test/Hello 
```

You should see:
```
HTTP/1.1 200 OK
Host: localhost:7999
Date: Sun, 29 Aug 2021 13:21:16 GMT
X-Powered-By: PHP/8.0.10
Content-Type: text/html; charset=UTF-8
Cache-Control: no-cache, private
Date: Sun, 29 Aug 2021 13:21:16 GMT
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Access-Control-Allow-Origin: *
Transfer-Encoding: chunked
Connection: Transfer-Encoding

Channel: test
Message: Hello
Publish Successful!
```

In the *websocket* window, you should now see the `Hello` message appear at the end,
as an incoming message only.
