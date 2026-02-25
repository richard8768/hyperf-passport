<?php

namespace Richard\HyperfPassport\Controller;

use Exception;
use Hyperf\HttpServer\Response;
use Hyperf\HttpServer\Request;
use Hyperf\Collection\Arr;
use Hyperf\Contract\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\AuthManager;

class DenyAuthorizationController
{

    use RetrievesAuthRequestFromSession;

    /**
     * The response factory implementation.
     *
     * @var Response
     */
    protected Response $response;
    protected SessionInterface $session;

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     *
     * @param Response $response
     * @param SessionInterface $session
     * @param AuthManager $auth
     */
    public function __construct(Response $response, SessionInterface $session, AuthManager $auth)
    {
        $this->response = $response;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Deny the authorization request.
     *
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function deny(Request $request): ResponseInterface
    {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);

        $clientUris = Arr::wrap($authRequest->getClient()->getRedirectUri());

        if (!in_array($uri = $authRequest->getRedirectUri(), $clientUris)) {
            $uri = Arr::first($clientUris);
        }

        $separator = ($authRequest->getGrantTypeId() === 'implicit') ? '#' : (str_contains($uri, '?') ? '&' : '?');

        return $this->response->redirect(
            $uri . $separator . 'error=access_denied&state=' . $request->input('state')
        );
    }

}
