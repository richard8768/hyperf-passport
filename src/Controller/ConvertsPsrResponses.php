<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Richard\HyperfPassport\Exception\PassportException;
use function gettype;
use function is_callable;
use function is_string;

trait ConvertsPsrResponses
{

    /**
     * Convert a PSR7 response to  Response.
     *
     * @param ResponseInterface $psrResponse
     * @return Response
     */
    public function convertResponse($psrResponse): Response
    {
        $headers = $psrResponse->getHeaders();
        $content = $psrResponse->getBody();
        $statusCode = $psrResponse->getStatusCode();
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString'])) {
            $exception = new PassportException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
            $exception->setStatusCode(400);
            throw $exception;
        }
        $response = new Response();
        return $response->withHeaders($headers)->withStatus($statusCode)->withBody(new SwooleStream($content));
    }

}
