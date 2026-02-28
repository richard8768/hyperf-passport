<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait;

    /**
     * The client's provider.
     */
    public ?string $provider;

    /**
     * The client identifier.
     */
    protected string $identifier;

    /**
     * Create a new client instance.
     */
    public function __construct(string $identifier, string $name, string $redirectUri, bool $isConfidential = false, ?string $provider = null)
    {
        $this->setIdentifier((string) $identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = explode(',', $redirectUri);
        $this->provider = $provider;
    }

    /**
     * Get the client's identifier.
     */
    public function getIdentifier(): string
    {
        return (string) $this->identifier;
    }

    /**
     * Set the client's identifier.
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }
}
