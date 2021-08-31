## HTTP Publish Example

The following example shows how to set up laravel-grip for use in a Laravel
project.

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
use Fanout\LaravelGrip\Http\Middleware\Facades\GripInstruct;
use Fanout\LaravelGrip\Http\Middleware\Facades\GripPublisher;

/* ... */

Route::get('/http-stream/stream/{channel}', function( $channel ) {
    GripInstruct::add_channel( $channel );
    GripInstruct::set_hold_stream();
    return '[open stream]' . PHP_EOL;
});

Route::get('/http-stream/publish/{channel}/{message}', function( $channel, $message ) {
    ob_start();
    echo 'Channel: ' . $channel . PHP_EOL;
    echo 'Message: ' . $message . PHP_EOL;
    GripPublisher::publish_http_stream( $channel, $message . PHP_EOL )
        ->then(function() {
            echo 'Publish Successful!' . PHP_EOL;
        })
        ->otherwise(function( $error ) {
            echo 'Publish Fail!' . PHP_EOL;
            echo json_encode( $error ) . PHP_EOL;
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

In another Terminal window, hit the *stream* endpoint with curl
```
% curl -i http://localhost:7999/api/http-stream/stream/test
```

You should see:
```
% curl -i http://localhost:7999/api/http-stream/stream/test
HTTP/1.1 200 OK
Host: localhost:7999
Date: Sun, 29 Aug 2021 13:18:07 GMT
X-Powered-By: PHP/8.0.10
Content-Type: text/html; charset=UTF-8
Cache-Control: no-cache, private
Date: Sun, 29 Aug 2021 13:18:07 GMT
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Access-Control-Allow-Origin: *
Transfer-Encoding: chunked
Connection: Transfer-Encoding

[open stream]
```

Now, in yet another Terminal window, hit the *publish* endpoint with curl:
```
% curl -i http://localhost:7999/api/http-stream/publish/test/Hello 
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

In the *stream* window, you should now see the following at the end of the output:
```
Hello
```
