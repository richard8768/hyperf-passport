<?php

namespace Richard\HyperfPassport\Middleware;

use Richard\HyperfPassport\Auth\AuthorizationException;
use Richard\HyperfPassport\Exception\MissingScopeException;
use Qbhy\HyperfAuth\AuthManager;

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
     * @param  \Hyperf\HttpServer\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Hyperf\HttpMessage\Server\Response
     *
     * @throws \Richard\HyperfPassport\Auth\AuthorizationException|\Richard\HyperfPassport\Exception\MissingScopeException
     */
    public function handle($request, $next, ...$scopes) {
        $user = $this->auth->guard('passport')->user();
        if (!$user || !$user->token()) {
            throw new AuthorizationException;
        }

        foreach ($scopes as $scope) {
            if ($user->tokenCan($scope)) {
                return $next($request);
            }
        }

        throw new MissingScopeException($scopes);
    }

}
