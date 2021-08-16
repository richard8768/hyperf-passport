<?php

namespace Richard\HyperfPassport\Middleware;

use Closure;
use Richard\HyperfPassport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;

abstract class CheckCredentials {

    /**
     * The Resource Server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * Token Repository.
     *
     * @var \Richard\HyperfPassport\TokenRepository
     */
    protected $repository;

    /**
     * Create a new middleware instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @param  \Richard\HyperfPassport\TokenRepository  $repository
     * @return void
     */
    public function __construct(ResourceServer $server, TokenRepository $repository) {
        $this->server = $server;
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return mixed
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    public function handle($request, Closure $next, ...$scopes) {
        $psr = $request;

        try {
            $psr = $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            $exception = new \Richard\HyperfPassport\Exception\PassportException($e->getMessage());
            $exception->setStatusCode($e->getCode());
            throw $exception;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }

    /**
     * Validate the scopes and token on the incoming request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $psr
     * @param  array  $scopes
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    protected function validate($psr, $scopes) {
        $token = $this->repository->find($psr->getAttribute('oauth_access_token_id'));

        $this->validateCredentials($token);

        $this->validateScopes($token, $scopes);
    }

    /**
     * Validate token credentials.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    abstract protected function validateCredentials($token);

    /**
     * Validate token scopes.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\PassportException
     */
    abstract protected function validateScopes($token, $scopes);
}
