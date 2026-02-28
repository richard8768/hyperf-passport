<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\Contract\SessionInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\SessionAuthenticationException;

class SessionAuthMiddleware implements MiddlewareInterface
{
    protected array $guards = ['session'];
    // 支持多个 guard

    #[Inject]
    protected AuthManager $auth;

    #[Inject]
    protected HttpRequest $httpRequest;

    #[Inject]
    private SessionInterface $session;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Psr7ResponseInterface
    {
        $this->authenticate($request);
        return $handler->handle($request);
    }

    protected function authenticate($request): void
    {
        if (empty($this->guards)) {
            $this->guards = [null];
        }

        foreach ($this->guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return;
            }
        }
        $this->unauthenticated($request);
    }

    protected function unauthenticated($request): void
    {
        $intended = $this->httpRequest->getMethod() === 'GET' && ! ($this->httpRequest->getHeaderLine('X-Requested-With') == 'XMLHttpRequest' && ! ($this->httpRequest->getHeaderLine('X-PJAX') == true)) ? $this->httpRequest->fullUrl() : $this->httpRequest->getHeaderLine('referer') ?? $this->session->previousUrl() ?? '/';
        if ($intended) {
            $this->session->set('url.intended', $intended);
        }
        throw new SessionAuthenticationException('Unauthenticated user .', $this->guards, $this->redirectTo($request));
    }

    protected function redirectTo($request): string
    {
        return config('passport.session_user_login_uri') ?? '/';
    }
}
