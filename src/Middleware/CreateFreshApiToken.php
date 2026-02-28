<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Middleware;

use Closure;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\ApiTokenCookieFactory;
use Richard\HyperfPassport\Passport;

class CreateFreshApiToken
{
    protected SessionInterface $session;

    /**
     * The API token cookie factory instance.
     */
    protected ApiTokenCookieFactory $cookieFactory;

    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, SessionInterface $session, AuthManager $auth)
    {
        $this->cookieFactory = $cookieFactory;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        $this->guard = $guard;

        $response = $next($request);

        $user = $this->auth->guard('passport')->user();
        if ($this->shouldReceiveFreshToken($request, $response)) {
            $response->withCookie($this->cookieFactory->make(
                $user->getKey(),
                $this->session->token()
            ));
        }

        return $response;
    }

    /**
     * Determine if the given request should receive a fresh token.
     */
    protected function shouldReceiveFreshToken(Request $request, Response $response): bool
    {
        return $this->requestShouldReceiveFreshToken($request)
            && $this->responseShouldReceiveFreshToken($response);
    }

    /**
     * Determine if the request should receive a fresh token.
     */
    protected function requestShouldReceiveFreshToken(Request $request): bool
    {
        $user = $this->auth->guard('passport')->user();
        return $request->isMethod('GET') && $user;
    }

    /**
     * Determine if the response should receive a fresh token.
     */
    protected function responseShouldReceiveFreshToken(Response $response): bool
    {
        return ($response instanceof Response)
            && ! $this->alreadyContainsToken($response);
    }

    /**
     * Determine if the given response already contains an API token.
     *
     * This avoids us overwriting a just "refreshed" token.
     */
    protected function alreadyContainsToken(Response $response): bool
    {
        $passport = \Hyperf\Support\make(Passport::class);
        foreach ($response->getCookies() as $cookie) {
            if ($cookie->getName() === $passport->cookie()) {
                return true;
            }
        }

        return false;
    }
}
