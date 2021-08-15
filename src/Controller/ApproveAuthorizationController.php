<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpMessage\Server\Response;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;
use Hyperf\HttpServer\Request;

class ApproveAuthorizationController {

    use ConvertsPsrResponses,
        RetrievesAuthRequestFromSession;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;
    protected $session;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server, SessionInterface $session, AuthManager $auth) {
        $this->server = $server;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Approve the authorization request.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function approve(Request $request) {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);

        return $this->convertResponse(
                        $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
        );
    }

}
