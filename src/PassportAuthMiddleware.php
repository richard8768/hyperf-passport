<?php

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\Exception\UnauthorizedException;
use Qbhy\HyperfAuth\Authenticatable;

class PassportAuthMiddleware implements MiddlewareInterface
{

    protected array $guards = ['passport'];
    // 支持多个 guard

    #[Inject]
    protected \Richard\HyperfPassport\AuthManager $auth;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->guards as $name) {
            $guard = $this->auth->guard($name);

            if (!$guard->user() instanceof Authenticatable) {
                throw new \Richard\HyperfPassport\Exception\PassportException("Without authorization from {$guard->getName()} guard", $guard);
            }
        }

        return $handler->handle($request);
    }

}
