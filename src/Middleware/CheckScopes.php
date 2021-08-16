<?php

namespace Richard\HyperfPassport\Middleware;

use Qbhy\HyperfAuth\AuthManager;

class CheckScopes {

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
     * @param  \Hyperf\HttpServer\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Hyperf\HttpMessage\Server\Response
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    public function handle($request, $next, ...$scopes) {
        $user = $this->auth->guard('passport')->user();
        if (!$user || !$user->token()) {
            $exception = new \Richard\HyperfPassport\Exception\PassportException('This action is unauthorized.');
            throw $exception;
        }

        foreach ($scopes as $scope) {
            if (!$user->tokenCan($scope)) {
                $exception = new \Richard\HyperfPassport\Exception\PassportException('Invalid scope(s) provided.');
                $exception->setScopes($scopes);
                throw $exception;
            }
        }

        return $next($request);
    }

}
