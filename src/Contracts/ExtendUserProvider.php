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

use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\UserProvider;

interface ExtendUserProvider extends UserProvider
{

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier): ?Authenticatable;

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  $identifier
     * @param string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, string $token): ?Authenticatable;
}
