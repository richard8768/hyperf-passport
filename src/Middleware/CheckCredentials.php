<?php

namespace Richard\HyperfPassport\Middleware;

use Closure;
use Hyperf\HttpServer\Request;
use Psr\Http\Message\ServerRequestInterface;
use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Token;
use Richard\HyperfPassport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;

abstract class CheckCredentials
{

    /**
     * The Resource Server instance.
     *
     * @var ResourceServer
     */
    protected ResourceServer $server;

    /**
     * Token Repository.
     *
     * @var TokenRepository
     */
    protected TokenRepository $repository;

    /**
     * Create a new middleware instance.
     *
     * @param ResourceServer $server
     * @param TokenRepository $repository
     * @return void
     */
    public function __construct(ResourceServer $server, TokenRepository $repository)
    {
        $this->server = $server;
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param mixed ...$scopes
     * @return mixed
     *
     * @throws PassportException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = $request;

        try {
            $psr = $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            $exception = new PassportException($e->getMessage());
            $exception->setStatusCode($e->getCode());
            throw $exception;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }

    /**
     * Validate the scopes and token on the incoming request.
     *
     * @param ServerRequestInterface $psr
     * @param array $scopes
     * @return void
     *
     * @throws PassportException
     */
    protected function validate($psr, $scopes)
    {
        $token = $this->repository->find($psr->getAttribute('oauth_access_token_id'));

        $this->validateCredentials($token);

        $this->validateScopes($token, $scopes);
    }

    /**
     * Validate token credentials.
     *
     * @param Token $token
     * @return void
     *
     * @throws PassportException
     */
    abstract protected function validateCredentials($token);

    /**
     * Validate token scopes.
     *
     * @param Token $token
     * @param array $scopes
     * @return void
     *
     * @throws PassportException
     */
    abstract protected function validateScopes($token, $scopes);
}
