<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Middleware;

use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Token;

class CheckClientCredentialsForAnyScope extends CheckCredentials
{
    /**
     * Validate token credentials.
     *
     * @throws PassportException
     */
    protected function validateCredentials(Token $token): void
    {
        if (! $token) {
            throw new PassportException('This action is unauthorized.');
        }
    }

    /**
     * Validate token credentials.
     *
     * @throws PassportException
     */
    protected function validateScopes(Token $token, array $scopes): void
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
