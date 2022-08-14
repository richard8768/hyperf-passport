<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenController {

    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     */
    protected $server;

    /**
     * The token repository instance.
     *
     */
    protected $tokens;


    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @param  TokenRepository  $tokens
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
     * @param  ServerRequestInterface  $request
     * @return Response
     */
    public function issueToken(ServerRequestInterface $request) {
        return $this->withErrorHandling(function () use ($request) {
                    return $this->convertResponse(
                                    $this->server->respondToAccessTokenRequest($request, new Psr7Response)
                    );
                });
    }

}
