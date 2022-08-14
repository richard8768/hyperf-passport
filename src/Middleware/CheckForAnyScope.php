<?php

namespace Richard\HyperfPassport\Middleware;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\PassportException;

class CheckForAnyScope {

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     * @return void
     */
    public function __construct(AuthManager $auth) {
        $this->auth = $auth;
    }

    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return Response
     *
     * @throws PassportException
     */
    public function handle($request, $next, ...$scopes) {
        $user = $this->auth->guard('passport')->user();
        if (!$user || !$user->token()) {
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
