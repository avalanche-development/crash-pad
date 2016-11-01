<?php

namespace AvalancheDevelopment\CrashPad;

use AvalancheDevelopment\Peel\HttpErrorInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ErrorHandler
{

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

        $response = $response->withStatus($body['statusCode']);
        $response->getBody()->write(json_encode($body));
        return $response;
    }
}
