<?php

namespace AvalancheDevelopment\CrashPad;

use AvalancheDevelopment\Peel\HttpErrorInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ErrorHandler implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param \Exception $exception
     * @return Response
     */
    public function __invoke(Request $request, Response $response, \Exception $exception)
    {
        $body = [
            'statusCode' => 500,
            'error' => 'Internal Server Error',
            'message' => $exception->getMessage(),
        ];

        if ($exception instanceof HttpErrorInterface) {
            $body['statusCode'] = $exception->getStatusCode();
            $body['error'] = $exception->getStatusMessage();
        }

        $this->logger->notice("ErrorHandler: {$body['statusCode']} {$body['message']}");
        $this->logger->debug($exception->getTraceAsString());

        $bodyStream = $this->getStream($body);

        $response = $response->withStatus($body['statusCode']);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response = $response->withBody($bodyStream);

        return $response;
    }

    /**
     * @param array $body
     * @return StreamInterface
     */
    protected function getStream(array $body)
    {
        $bodyString = json_encode($body);
        return Psr7\stream_for($bodyString);
    }
}
