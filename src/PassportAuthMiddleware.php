<?php

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Exception\PassportException;

class PassportAuthMiddleware implements MiddlewareInterface
{

    protected array $guards = ['passport'];
    // 支持多个 guard

    #[Inject]
    protected AuthManager $auth;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->guards as $name) {
            $guard = $this->auth->guard($name);

            if (!$guard->user() instanceof Authenticatable) {
                throw new PassportException("Without authorization from {$guard->getName()} guard", $guard);
            }
        }

        return $handler->handle($request);
    }

}
