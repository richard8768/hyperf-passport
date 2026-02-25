<?php

namespace Richard\HyperfPassport\Controller;

use Closure;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use Richard\HyperfPassport\Exception\PassportException;

trait HandlesOAuthErrors
{

    use ConvertsPsrResponses;

    /**
     * Perform the given callback with exception handling.
     *
     * @param Closure $callback
     *
     * @return mixed
     */
    protected function withErrorHandling(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (LeagueException $e) {
            $exception = new PassportException($e->getMessage());
            $exception->setStatusCode($e->getCode());
            throw $exception;
        }
    }

}
