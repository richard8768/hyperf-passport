<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\View\Render;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\Str;
use Richard\HyperfPassport\Bridge\User;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;
use Hyperf\Contract\SessionInterface;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Qbhy\HyperfAuth\AuthManager;
use Hyperf\Contract\ConfigInterface;

class AuthorizationController {

    use HandlesOAuthErrors;

    /**
     * @Inject
     * @var \Hyperf\Contract\ConfigInterface
     */
    protected $config;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * @var Render
     */
    protected $render;
    protected $session;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Hyperf\View\RenderInterface  $render
     * @param  \Hyperf\Contract\SessionInterface  $session
     * @return void
     */
    public function __construct(AuthorizationServer $server, Render $render, SessionInterface $session, AuthManager $auth) {
        $this->server = $server;
        $this->render = $render;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @param  \Hyperf\HttpServer\Request  $request
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @param  \Richard\HyperfPassport\TokenRepository  $tokens
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function authorize(ServerRequestInterface $psrRequest,
            Request $request,
            ClientRepository $clients,
            TokenRepository $tokens) {
        $authRequest = $this->withErrorHandling(function () use ($psrRequest) {
            return $this->server->validateAuthorizationRequest($psrRequest);
        });

        $scopes = $this->parseScopes($authRequest);

        $token = $tokens->findValidToken(
                $user = $this->auth->guard('passport')->user(),
                $client = $clients->find($authRequest->getClient()->getIdentifier())
        );

        if (($token && $token->scopes === collect($scopes)->pluck('id')->all()) ||
                $client->skipsAuthorization()) {
            return $this->approveRequest($authRequest, $user);
        }

        $authToken = Str::random();
        $this->session->set('authToken', $authToken);
        $this->session->set('authRequest', $authRequest);
        $appname = $this->config->get('app_name');

        return $this->render->render('passport.authorize', [
                    'client' => $client,
                    'user' => $user,
                    'scopes' => $scopes,
                    'request' => $request,
                    'authToken' => $authToken,
                    'appname' => $appname,
        ]);
    }

    /**
     * Transform the authorization requests's scopes into Scope instances.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @return array
     */
    protected function parseScopes($authRequest) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->scopesFor(
                        collect($authRequest->getScopes())->map(function ($scope) {
                            return $scope->getIdentifier();
                        })->unique()->all()
        );
    }

    /**
     * Approve the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  \Hyperf\Database\Model\Model  $user
     * @return  \Hyperf\HttpMessage\Server\Response
     */
    protected function approveRequest($authRequest, $user) {
        $authRequest->setUser(new User($user->getKey()));

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(function () use ($authRequest) {
                    return $this->convertResponse(
                                    $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
                    );
                });
    }

}
