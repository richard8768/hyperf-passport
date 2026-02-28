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
