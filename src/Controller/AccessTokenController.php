<?php

namespace Richard\HyperfPassport\Controller;

use Richard\HyperfPassport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenController {

    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The token repository instance.
     *
     * @var \Richard\HyperfPassport\TokenRepository
     */
    protected $tokens;


    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Richard\HyperfPassport\TokenRepository  $tokens
     * @return void
     */
    public function __construct(AuthorizationServer $server,
            TokenRepository $tokens) {
        $this->server = $server;
        $this->tokens = $tokens;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function issueToken(ServerRequestInterface $request) {
        return $this->withErrorHandling(function () use ($request) {
                    return $this->convertResponse(
                                    $this->server->respondToAccessTokenRequest($request, new Psr7Response)
                    );
                });
    }

}
