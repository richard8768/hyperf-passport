<?php

namespace Richard\HyperfPassport\Contracts;

use Qbhy\HyperfAuth\AuthGuard;

interface ExtendAuthGuard extends AuthGuard {

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return null|int|string
     */
    public function id();

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool;
}
