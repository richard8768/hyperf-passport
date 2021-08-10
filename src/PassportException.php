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

namespace Richard\HyperfPassport;

use Qbhy\HyperfAuth\AuthGuard;
use Throwable;
use Qbhy\HyperfAuth\Exception\AuthException;
use League\OAuth2\Server\Exception\OAuthServerException;

class PassportException extends AuthException {

    protected $guard;
    protected $statusCode = 401;

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

}
