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

use Hyperf\Contract\SessionInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\ApiTokenCookieFactory;

class TransientTokenController
{
    protected SessionInterface $session;

    /**
     * The cookie factory instance.
     */
    protected ApiTokenCookieFactory $cookieFactory;

    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, SessionInterface $session, AuthManager $auth)
    {
        $this->cookieFactory = $cookieFactory;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Get a fresh transient token cookie for the authenticated user.
     */
    public function refresh(Request $request): Response
    {
        $response = new Response();
        $user = $this->auth->guard('passport')->user();
        return $response->withBody(new SwooleStream('Refreshed.'))
            ->withCookie($this->cookieFactory->make(
                $user->getKey(),
                $this->session->token()
            ));
    }
}
