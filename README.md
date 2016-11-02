crash-pad
==============

Error handler that utilizes [peel](https://github.com/avalanche-development/peel) exceptions to standardize responses.

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install crash-pad.

```bash
$ composer require avalanche-development/crash-pad
```

crash-pad requires PHP 5.6 or newer.

## Usage

This handler works best with [peel](https://github.com/avalanche-development/peel). It can work without, but without the HttpErrorInterface exceptions all of the responses are going to default as 500 Server Errors.

Depending on the framework you're using, this can be hooked up in a few different ways. In Slim you'd attach it to the `Slim\Container`. I'm going to be biased and show this in Talus.

```php
$talus = new Talus([...]);
$talus->setErrorHandler(new AvalancheDevelopment\CrashPad\ErrorHandler);
```

This will listen for exceptions that jump out of the call stack and return appropriate responses. For example, if you have some middleware like so...

```php
function someMiddleware($request, $response, $next) {
    $body = (string) $request->getBody();
    $body = json_decode($value);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new AvalancheDevelopment\Peel\HttpError\BadRequest('Invalid JSON');
    }
    // etc
}
```

The error handler will detect the `AvalancheDevelopment\Peel\HttpErrorInterface` exception and return a response like so...

```json
{
  "statusCode": 400,
  "error": "Bad Request",
  "message": "Invalid JSON"
}
```

All responses will include these three fields, plus the appropriate headers. Any exceptions that do not implement the `HttpErrorEnterface` will respond the default 500.

## Development

This library is in active development. Some of the error responses may include metadata moving forward.

### Tests

To execute the test suite, you'll need phpunit (and to install package with dev dependencies).

```bash
$ phpunit
```

## License

crash-pad is licensed under the MIT license. See [License File](LICENSE.md) for more information.
