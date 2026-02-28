<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Contracts;

use Qbhy\HyperfAuth\AuthGuard;

interface ExtendAuthGuard extends AuthGuard
{
    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): null|int|string;

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool;
}
