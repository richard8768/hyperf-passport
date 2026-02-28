<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Exception;

use Hyperf\Collection\Arr;
use League\OAuth2\Server\Exception\OAuthServerException;
use Qbhy\HyperfAuth\AuthGuard;
use Qbhy\HyperfAuth\Exception\AuthException;

class PassportException extends AuthException
{
    protected ?AuthGuard $guard;

    protected int $statusCode = 401;

    protected array $scopes = [];

    public function __construct(string $message, ?AuthGuard $guard = null, ?OAuthServerException $previous = null)
    {
        parent::__construct($message, 401, $previous);
        $this->guard = $guard;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): PassportException
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getGuard(): string
    {
        return (string) $this->guard;
    }

    public function setScopes(array $scopes = []): PassportException
    {
        $this->scopes = Arr::wrap($scopes);
        return $this;
    }

    public function scopes(): array
    {
        return $this->scopes;
    }
}
