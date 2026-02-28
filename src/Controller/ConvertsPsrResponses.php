<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

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
     */
    public function convertResponse($psrResponse): Response
    {
        $headers = $psrResponse->getHeaders();
        $content = $psrResponse->getBody();
        $statusCode = $psrResponse->getStatusCode();
        if ($content !== null && ! is_string($content) && ! is_numeric($content) && ! is_callable([$content, '__toString'])) {
            $exception = new PassportException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
            $exception->setStatusCode(400);
            throw $exception;
        }
        $response = new Response();
        return $response->withHeaders($headers)->withStatus($statusCode)->withBody(new SwooleStream($content));
    }
}
