<?php

namespace Richard\HyperfPassport\Middleware;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\PassportException;

class CheckScopes
{

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     * @return void
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param mixed ...$scopes
     * @return Response
     *
     * @throws PassportException
     */
    public function handle($request, $next, ...$scopes)
    {
        $user = $this->auth->guard('passport')->user();
        if (!$user || !$user->token()) {
            $exception = new PassportException('This action is unauthorized.');
            throw $exception;
        }

        foreach ($scopes as $scope) {
            if (!$user->tokenCan($scope)) {
                $exception = new PassportException('Invalid scope(s) provided.');
                $exception->setScopes($scopes);
                throw $exception;
            }
        }

        return $next($request);
    }

}
