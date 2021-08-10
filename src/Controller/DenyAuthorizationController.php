<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpServer\Response;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\Arr;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;

class DenyAuthorizationController {

    use RetrievesAuthRequestFromSession;

    /**
     * The response factory implementation.
     *
     * @var \Hyperf\HttpServer\Response
     */
    protected $response;
    protected $session;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param  \Hyperf\HttpServer\Response  $response
     * @return void
     */
    public function __construct(Response $response, SessionInterface $session, AuthManager $auth) {
        $this->response = $response;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Deny the authorization request.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Hyperf\HttpMessage\Server\RedirectResponse
     */
    public function deny(Request $request) {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);

        $clientUris = Arr::wrap($authRequest->getClient()->getRedirectUri());

        if (!in_array($uri = $authRequest->getRedirectUri(), $clientUris)) {
            $uri = Arr::first($clientUris);
        }

        $separator = $authRequest->getGrantTypeId() === 'implicit' ? '#' : (strstr($uri, '?') ? '&' : '?');

        return $this->response->redirect(
                        $uri . $separator . 'error=access_denied&state=' . $request->input('state')
        );
    }

}
