<?php

namespace Richard\HyperfPassport\Middleware;

use Closure;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Richard\HyperfPassport\ApiTokenCookieFactory;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;

class CreateFreshApiToken
{

    protected SessionInterface $session;

    /**
     * The API token cookie factory instance.
     *
     * @var ApiTokenCookieFactory
     */
    protected ApiTokenCookieFactory $cookieFactory;

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     *
     * @param ApiTokenCookieFactory $cookieFactory
     * @return void
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, SessionInterface $session, AuthManager $auth)
    {
        $this->cookieFactory = $cookieFactory;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $this->guard = $guard;

        $response = $next($request);

        $user = $this->auth->guard('passport')->user();
        if ($this->shouldReceiveFreshToken($request, $response)) {
            $response->withCookie($this->cookieFactory->make(
                $user->getKey(), $this->session->token()
            ));
        }

        return $response;
    }

    /**
     * Determine if the given request should receive a fresh token.
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    protected function shouldReceiveFreshToken($request, $response)
    {
        return $this->requestShouldReceiveFreshToken($request) &&
            $this->responseShouldReceiveFreshToken($response);
    }

    /**
     * Determine if the request should receive a fresh token.
     *
     * @param Request $request
     * @return bool
     */
    protected function requestShouldReceiveFreshToken($request)
    {
        $user = $this->auth->guard('passport')->user();
        return $request->isMethod('GET') && $user;
    }

    /**
     * Determine if the response should receive a fresh token.
     *
     * @param Response $response
     * @return bool
     */
    protected function responseShouldReceiveFreshToken($response)
    {
        return ($response instanceof Response) &&
            !$this->alreadyContainsToken($response);
    }

    /**
     * Determine if the given response already contains an API token.
     *
     * This avoids us overwriting a just "refreshed" token.
     *
     * @param Response $response
     * @return bool
     */
    protected function alreadyContainsToken($response)
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        foreach ($response->getCookies() as $cookie) {
            if ($cookie->getName() === $passport->cookie()) {
                return true;
            }
        }

        return false;
    }

}
