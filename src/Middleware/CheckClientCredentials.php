<?php

namespace Richard\HyperfPassport\Middleware;

use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Token;

class CheckClientCredentials extends CheckCredentials
{

    /**
     * Validate token credentials.
     *
     * @param Token $token
     * @return void
     *
     * @throws PassportException
     */
    protected function validateCredentials(Token $token): void
    {
        if (!$token) {
            throw new PassportException('This action is unauthorized.');
        }
    }

    /**
     * Validate token credentials.
     *
     * @param Token $token
     * @param array $scopes
     * @return void
     *
     * @throws PassportException
     */
    protected function validateScopes(Token $token, array $scopes): void
    {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                $exception = new PassportException('Invalid scope(s) provided.');
                $exception->setScopes($scopes);
                throw $exception;
            }
        }
    }

}
