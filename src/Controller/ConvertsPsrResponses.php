<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpMessage\Server\Response;
use Psr\Http\Message\ResponseInterface;

trait ConvertsPsrResponses {

    /**
     * Convert a PSR7 response to  Response.
     *
     * @param  ResponseInterface  $psrResponse
     * @return Response
     */
    public function convertResponse($psrResponse) {
        $headers = $psrResponse->getHeaders();
        $content = $psrResponse->getBody();
        $statusCode = $psrResponse->getStatusCode();
        if (null !== $content && !\is_string($content) && !is_numeric($content) && !\is_callable([$content, '__toString'])) {
            $exception = new \Richard\HyperfPassport\Exception\PassportException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', \gettype($content)));
            $exception->setStatusCode(400);
            throw $exception;
        }
        $response = new Response();
        return $response->withHeaders($headers)->withStatus($statusCode)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream($content));
    }

}
