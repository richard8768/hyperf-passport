<?php

namespace Richard\HyperfPassport\Middleware;

use Richard\HyperfPassport\Auth\AuthorizationException;
use Richard\HyperfPassport\Exception\MissingScopeException;

class CheckClientCredentials extends CheckCredentials {

    /**
     * Validate token credentials.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @return void
     *
     * @throws \Richard\HyperfPassport\Auth\AuthorizationException
     */
    protected function validateCredentials($token) {
        if (!$token) {
            throw new AuthorizationException;
        }
    }

    /**
     * Validate token credentials.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\MissingScopeException
     */
    protected function validateScopes($token, $scopes) {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }

}
