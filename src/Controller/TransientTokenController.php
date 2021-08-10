<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\ApiTokenCookieFactory;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;

class TransientTokenController {

    protected $session;

    /**
     * The cookie factory instance.
     *
     * @var \Richard\HyperfPassport\ApiTokenCookieFactory
     */
    protected $cookieFactory;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param  \Richard\HyperfPassport\ApiTokenCookieFactory  $cookieFactory
     * @return void
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, SessionInterface $session, AuthManager $auth) {
        $this->cookieFactory = $cookieFactory;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Get a fresh transient token cookie for the authenticated user.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function refresh(Request $request) {
        $response = new Response();
        $user = $this->auth->guard('passport')->user();
        return $response->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream('Refreshed.'))
                        ->withCookie($this->cookieFactory->make(
                                        $user->getKey(), $this->session->token()
        ));
    }

}
