<?php

namespace Richard\HyperfPassport\Contracts;

use Qbhy\HyperfAuth\UserProvider;
use Qbhy\HyperfAuth\Authenticatable;

interface ExtendUserProvider extends UserProvider {

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Qbhy\HyperfAuth\Authenticatable|null
     */
    public function retrieveById($identifier);

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Qbhy\HyperfAuth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token);
}
