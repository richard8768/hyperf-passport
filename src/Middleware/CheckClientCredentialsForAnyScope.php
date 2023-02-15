<?php

namespace Richard\HyperfPassport\Middleware;

use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Token;

class CheckClientCredentialsForAnyScope extends CheckCredentials
{

    /**
     * Validate token credentials.
     *
     * @param Token $token
     * @return void
     *
     * @throws PassportException
     */
    protected function validateCredentials($token)
    {
        if (!$token) {
            $exception = new PassportException('This action is unauthorized.');
            throw $exception;
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
    protected function validateScopes($token, $scopes)
    {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return;
            }
        }
        $exception = new PassportException('Invalid scope(s) provided.');
        $exception->setScopes($scopes);
        throw $exception;
    }

}
