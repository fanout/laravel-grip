## laravel-grip

GRIP library for [Laravel](https://laravel.com/), provided as a Laravel package.

Minimum supported version of Laravel is 7.0, but it may work with older versions.

Supported GRIP servers include:

* [Pushpin](http://pushpin.org/)
* [Fastly Fanout](https://docs.fastly.com/products/fanout)

This library also supports legacy services hosted by [Fanout](https://fanout.io/) Cloud.

Authors: Katsuyuki Omuro <komuro@fastly.com>, Madeline Boby <maddie.boby@fastly.com>

### Introduction

[GRIP](https://pushpin.org/docs/protocols/grip/) is a protocol that enables a web service to
delegate realtime push behavior to a proxy component, using HTTP and headers.

`laravel-grip` parses the `Grip-Sig` header in any requests to detect if they came
through a GRIP proxy, and provides your route handler with tools to handle such requests.
This includes access to information about whether the current request is proxied or is signed,
as well as  methods to issue any hold instructions to the GRIP proxy.

Additionally, `laravel-grip` also handles
[WebSocket-Over-HTTP processing](https://pushpin.org/docs/protocols/websocket-over-http/) so
that WebSocket connections managed by the GRIP proxy can be controlled by your route handlers.

### Installation

Install the library.

```sh
composer require fanout/laravel-grip
```

This brings in the library, as well as installs the middleware into your Laravel application's stack
by using the providers mechanism of Composer.

#### Configuration

`laravel-grip` can be configured by adding a file called `./config/grip.php` to your Laravel
application.  It should look like this:

```php
<?php

return [
    'grip' => /* string, array, or array of arrays */,
    'prefix' => /* string. defaults to the empty string */,
    'grip_proxy_required' => /* boolean, defaults to false */,
];
```

Available options:
| Key | Value |
| --- | --- |
| `grip` | A definition of GRIP proxies used to publish messages. See below for details. |
| `prefix` | An optional string that will be prepended to the name of channels being published to. This can be used for namespacing. Defaults to `''`. |
| `grip_proxy_required` | A boolean value representing whether all incoming requests should require that they be called behind a GRIP proxy.  If this is true and a GRIP proxy is not detected, then a `501 Not Implemented` error will be issued. Defaults to `false`. |

The `grip` parameter may be provided as any of the following:

1. An object with the following fields:

| Key           | Value                                                                           |
|---------------|---------------------------------------------------------------------------------|
| `control_uri` | The Control URI of the GRIP client.                                             |
| `control_iss` | (optional) The Control ISS, if required by the GRIP client.                     |
| `key`         | (optional) The key to use with the Control ISS, if required by the GRIP client. |
| `verify_iss`  | (optional) The ISS to use when validating a GRIP signature.                     |
| `verify_key`  | (optional) The key to use when validating a GRIP signature.                     |

2. An array of such objects.

3. A GRIP URI, which is a string that encodes the above as a single string.

### Handling a route

The middleware will automatically be installed before all of your routes.

When your route runs, you will have access to the following facades:
`Grip`, `GripInstruct`, `GripPublisher`, and `GripWebSocket`.

While `Grip` will be available in all requests, the others will be available only when
applicable based on configuration and the current request.

The `Grip` facade provides the following functions:

| Key | Description |
| --- | --- |
| `Grip::is_proxied` | A boolean value indicating whether the current request has been called via a GRIP proxy. |
| `Grip::is_signed` | A boolean value indicating whether the current request is a signed request called via a GRIP proxy. |

When the current request is proxied, then the `GripInstruct` facade is available and provides
the same functions as `GripInstruct` in `fanout/grip`.

When the current requiest is called over WebSocket-over-HTTP, then the `GripWebSocket` facade
is available and provides the same functions as `WebSocketContext` in `fanout/grip`.

To publish messages, use the `GripPublisher` facade.  It provides the same functions as `Publisher`
in `fanout/grip`. Use it to publish messages using the endpoints and prefix specified in the
`./config/grip.php` file.

### Examples

This repository contains examples to illustrate the use of `laravel-grip` found in the `examples`
directory.  For details on each example, please read the `README.md` files in the corresponding
directories.


## Testing

Run tests using the following command:

```
./vendor/bin/phpunit
```
