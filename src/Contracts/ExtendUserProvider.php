<?php

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
