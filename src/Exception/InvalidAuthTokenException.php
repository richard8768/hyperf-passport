<?php

namespace Richard\HyperfPassport\Exception;

use Qbhy\HyperfAuth\Exception\AuthException;

class InvalidAuthTokenException extends AuthException {

    /**
     * Create a new InvalidAuthTokenException for different auth tokens.
     *
     * @return static
     */
    public static function different() {
        return new static('The provided auth token for the request is different from the session auth token.');
    }

}
