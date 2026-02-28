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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\Database\Model\Model;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Hyperf\Stringable\Str;
use Hyperf\View\Render;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Bridge\User;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;

class AuthorizationController
{
    use HandlesOAuthErrors;

    #[Inject]
    protected ConfigInterface $config;

    /**
     * The authorization server.
     */
    protected AuthorizationServer $server;

    protected Render $render;

    protected SessionInterface $session;

    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     */
    public function __construct(AuthorizationServer $server, Render $render, SessionInterface $session, AuthManager $auth)
    {
        $this->server = $server;
        $this->render = $render;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Authorize a client to access the user's account.
     */
    public function authorize(ServerRequestInterface $psrRequest, Request $request, ClientRepository $clients, TokenRepository $tokens): Response|ResponseInterface
    {
        $authRequest = $this->withErrorHandling(function () use ($psrRequest) {
            return $this->server->validateAuthorizationRequest($psrRequest);
        });

        $scopes = $this->parseScopes($authRequest);
        $token = $tokens->findValidToken($user = $this->auth->guard('session')->user(), $client = $clients->find($authRequest->getClient()->getIdentifier()));
        if (($token && ($token->scopes === collect($scopes)->pluck('id')->all())) || $client->skipsAuthorization()) {
            return $this->approveRequest($authRequest, $user);
        }

        $authToken = Str::random();
        $this->session->set('authToken', $authToken);
        $this->session->set('authRequest', $authRequest);
        $appname = $this->config->get('app_name');
        return $this->render->render('passport.authorize', ['client' => $client, 'user' => $user, 'scopes' => $scopes, 'request' => $request, 'authToken' => $authToken, 'appname' => $appname]);
    }

    /**
     * Transform the authorization request's scopes into Scope instances.
     */
    protected function parseScopes(AuthorizationRequest $authRequest): array
    {
        return make(Passport::class)->scopesFor(collect($authRequest->getScopes())->map(function ($scope) {
            return $scope->getIdentifier();
        })->unique()->all());
    }

    /**
     * Approve the authorization request.
     */
    protected function approveRequest(AuthorizationRequest $authRequest, Model $user): Response
    {
        $authRequest->setUser(new User($user->getKey()));

        $authRequest->setAuthorizationApproved(true);
        return $this->withErrorHandling(function () use ($authRequest) {
            return $this->convertResponse($this->server->completeAuthorizationRequest($authRequest, new Psr7Response()));
        });
    }
}
