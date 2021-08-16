<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Exception;

use Qbhy\HyperfAuth\AuthGuard;
use Throwable;
use Qbhy\HyperfAuth\Exception\AuthException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Hyperf\Utils\Arr;

class PassportException extends AuthException {

    protected $guard;
    protected $statusCode = 401;
    protected $scopes = [];

    public function __construct(string $message, AuthGuard $guard = null, OAuthServerException $previous = null) {
        parent::__construct($message, 401, $previous);
        $this->guard = $guard;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getGuard(): string {
        return $this->guard;
    }

    public function setScopes(array $scopes = []) {
        $this->scopes = Arr::wrap($scopes);
        return $this;
    }

    public function scopes(): array {
        return $this->scopes;
    }

}
