<?php

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface as HttpRequest;
use Hyperf\Contract\SessionInterface;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\SessionAuthenticationException;

class SessionAuthMiddleware implements MiddlewareInterface
{
    protected $guards = ['session']; // 支持多个 guard

    /**
     * @Inject
     * @var AuthManager
     */
    protected $auth;

    /**
     * @Inject
     * @var HttpRequest
     */
    protected HttpRequest $httpRequest;


    /**
     * @Inject
     * @var SessionInterface
     */
    private SessionInterface $session;


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Psr7ResponseInterface
    {
        $this->authenticate($request);
        return $handler->handle($request);
    }

    protected function authenticate($request)
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

    protected function unauthenticated($request)
    {
        $intended = $this->httpRequest->getMethod() === 'GET' &&
        (!(('XMLHttpRequest' == $this->httpRequest->getHeaderLine('X-Requested-With')) &&
            (!($this->httpRequest->getHeaderLine('X-PJAX') == true))))
            ? $this->httpRequest->fullUrl()
            : $this->httpRequest->getHeaderLine('referer') ?? ($this->session->previousUrl() ?? '/');
        if ($intended) {
            $this->session->set('url.intended', $intended);
        }
        throw new SessionAuthenticationException(
            'Unauthenticated user .', $this->guards, $this->redirectTo($request)
        );
    }

    protected function redirectTo($request): string
    {
        return config('passport.session_user_login_uri')??'/';
    }

}