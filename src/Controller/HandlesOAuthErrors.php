<?php

namespace Richard\HyperfPassport\Controller;

use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;

trait HandlesOAuthErrors {

    use ConvertsPsrResponses;

    /**
     * Perform the given callback with exception handling.
     *
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    protected function withErrorHandling($callback) {
        try {
            return $callback();
        } catch (LeagueException $e) {
            $exception = new \Richard\HyperfPassport\Exception\PassportException($e->getMessage());
            $exception->setStatusCode($e->getCode());
            throw $exception;
        }
    }

}
