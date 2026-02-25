<?php

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class User implements UserEntityInterface
{
    use EntityTrait;

    /**
     * Create a new user instance.
     *
     * @param int|string $identifier
     * @return void
     */
    public function __construct(int|string $identifier)
    {
        $this->setIdentifier($identifier);
    }
}
