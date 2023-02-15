<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\ApiTokenCookieFactory;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;

class TransientTokenController
{

    protected SessionInterface $session;

    /**
     * The cookie factory instance.
     *
     * @var ApiTokenCookieFactory
     */
    protected ApiTokenCookieFactory $cookieFactory;

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     *
     * @param ApiTokenCookieFactory $cookieFactory
     * @param SessionInterface $session
     * @param AuthManager $auth
     * @return void
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, SessionInterface $session, AuthManager $auth)
    {
        $this->cookieFactory = $cookieFactory;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Get a fresh transient token cookie for the authenticated user.
     *
     * @param Request $request
     * @return Response
     */
    public function refresh(Request $request)
    {
        $response = new Response();
        $user = $this->auth->guard('passport')->user();
        return $response->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream('Refreshed.'))
            ->withCookie($this->cookieFactory->make(
                $user->getKey(), $this->session->token()
            ));
    }

}
