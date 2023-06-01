<?php

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    /**
     * Create a new scope instance.
     *
     * @param string $name
     * @return void
     */
    public function __construct($name)
    {
        $this->setIdentifier($name);
    }

    /**
     * Get the data that should be serialized to JSON.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->getIdentifier();
    }
}
