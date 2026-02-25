<?php

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;

class Client implements ClientEntityInterface
{

    use ClientTrait;

    /**
     * The client identifier.
     *
     * @var string
     */
    protected string $identifier;

    /**
     * The client's provider.
     *
     * @var null|string
     */
    public ?string $provider;

    /**
     * Create a new client instance.
     *
     * @param string $identifier
     * @param string $name
     * @param string $redirectUri
     * @param bool $isConfidential
     * @param string|null $provider
     * @return void
     */
    public function __construct(string $identifier, string $name, string $redirectUri, bool $isConfidential = false, string $provider = null)
    {
        $this->setIdentifier((string)$identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = explode(',', $redirectUri);
        $this->provider = $provider;
    }

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return (string)$this->identifier;
    }

    /**
     * Set the client's identifier.
     *
     * @param string $identifier
     * @return void
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

}
