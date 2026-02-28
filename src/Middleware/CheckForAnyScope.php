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

use Closure;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\PassportException;

class CheckForAnyScope
{
    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the incoming request.
     *
     * @param mixed ...$scopes
     *
     * @throws PassportException
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        $user = $this->auth->guard('passport')->user();
        if (! $user || ! $user->token()) {
            $exception = new PassportException('This action is unauthorized.');
            throw $exception;
        }

        foreach ($scopes as $scope) {
            if ($user->tokenCan($scope)) {
                return $next($request);
            }
        }
        $exception = new PassportException('Invalid scope(s) provided.');
        $exception->setScopes($scopes);
        throw $exception;
    }
}
