<?php

namespace AvalancheDevelopment\CrashPad;

use AvalancheDevelopment\Peel\HttpError\NotFound;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface as Stream;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class ErrorHandlerTest extends PHPUnit_Framework_TestCase
{

    public function testInstanceOfLoggerAwareInterface()
    {
        $handler = new ErrorHandler;

        $this->assertInstanceOf(LoggerAwareInterface::class, $handler);
    }

    public function testInstanceHasLogger()
    {
        $handler = new ErrorHandler;

        $this->assertAttributeInstanceOf(LoggerInterface::class, 'logger', $handler);
    }

    public function testInvokeHandlesNormalExceptionAsServerError()
    {
        $mockBody = [
            'statusCode' => 500,
            'error' => 'Internal Server Error',
            'message' => 'some exception',
        ];

        $mockBodyStream = $this->createMock(Stream::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockRequest = $this->createMock(Request::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withBody')
            ->with($mockBodyStream)
            ->will($this->returnSelf());

        $exception = new \Exception('some exception');

        $reflectedErrorHandler = new ReflectionClass(ErrorHandler::class);
        $reflectedLogger = $reflectedErrorHandler->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $handler = $this->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStream',
            ])
            ->getMock();
        $handler->expects($this->once())
            ->method('getStream')
            ->with($mockBody)
            ->willReturn($mockBodyStream);

        $reflectedLogger->setValue($handler, $mockLogger);

        $result = $handler($mockRequest, $mockResponse, $exception);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeHandlesHttpErrorAsCustomError()
    {
        $mockBody = [
            'statusCode' => 404,
            'error' => 'Not Found',
            'message' => 'some not found exception',
        ];

        $mockBodyStream = $this->createMock(Stream::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockRequest = $this->createMock(Request::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withBody')
            ->with($mockBodyStream)
            ->will($this->returnSelf());

        $exception = new NotFound('some not found exception');

        $reflectedErrorHandler = new ReflectionClass(ErrorHandler::class);
        $reflectedLogger = $reflectedErrorHandler->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $handler = $this->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStream',
            ])
            ->getMock();
        $handler->expects($this->once())
            ->method('getStream')
            ->with($mockBody)
            ->willReturn($mockBodyStream);

        $reflectedLogger->setValue($handler, $mockLogger);

        $result = $handler($mockRequest, $mockResponse, $exception);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokeLogsExceptions()
    {
        $exception = new \Exception('some message');

        $reflectedHandler = new \ReflectionClass(ErrorHandler::class);
        $reflectedLogger = $reflectedHandler->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('notice')
            ->with("ErrorHandler: 500 {$exception->getMessage()}");
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($exception->getTraceAsString());

        $mockRequest = $this->createMock(Request::class);
        $mockBody = $this->createMock(Stream::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('withStatus')
            ->will($this->returnSelf());
        $mockResponse->method('withHeader')
            ->will($this->returnSelf());
        $mockResponse->method('getBody')
            ->willReturn($mockBody);

        $handler = new ErrorHandler;
        $reflectedLogger->setValue($handler, $mockLogger);
        $handler($mockRequest, $mockResponse, $exception);
    }
}
