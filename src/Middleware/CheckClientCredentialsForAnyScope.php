<?php

namespace Richard\HyperfPassport\Middleware;

class CheckClientCredentialsForAnyScope extends CheckCredentials {

    /**
     * Validate token credentials.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    protected function validateCredentials($token) {
        if (!$token) {
            $exception = new \Richard\HyperfPassport\Exception\PassportException('This action is unauthorized.');
            throw $exception;
        }
    }

    /**
     * Validate token credentials.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    protected function validateScopes($token, $scopes) {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return;
            }
        }
        $exception = new \Richard\HyperfPassport\Exception\PassportException('Invalid scope(s) provided.');
        $exception->setScopes($scopes);
        throw $exception;
    }

}
