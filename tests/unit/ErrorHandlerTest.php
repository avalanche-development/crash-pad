<?php

namespace AvalancheDevelopment\CrashPad;

use AvalancheDevelopment\Peel\HttpError\NotFound;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface as Stream;

class ErrorHandlerTest extends PHPUnit_Framework_TestCase
{

    public function testInvokeHandlesNormalExceptionAsServerError()
    {
        $mockRequest = $this->createMock(Request::class);

        $mockBody = $this->createMock(Stream::class);
        $mockBody->expects($this->once())
            ->method('write')
            ->with(json_encode([
                'statusCode' => 500,
                'error' => 'Internal Server Error',
                'message' => 'some exception',
            ]));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('Content-type', 'application/json')
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockBody);

        $exception = new \Exception('some exception');

        $handler = new ErrorHandler;
        $result = $handler($mockRequest, $mockResponse, $exception);
        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeHandlesHttpErrorAsCustomError()
    {
        $mockRequest = $this->createMock(Request::class);

        $mockBody = $this->createMock(Stream::class);
        $mockBody->expects($this->once())
            ->method('write')
            ->with(json_encode([
                'statusCode' => 404,
                'error' => 'Not Found',
                'message' => 'some not found exception',
            ]));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('Content-type', 'application/json')
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockBody);

        $exception = new NotFound('some not found exception');

        $handler = new ErrorHandler;
        $result = $handler($mockRequest, $mockResponse, $exception);
        $this->assertSame($mockResponse, $result);
    }
}
