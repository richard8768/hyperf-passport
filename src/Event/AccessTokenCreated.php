<?php

namespace Richard\HyperfPassport\Event;

class AccessTokenCreated
{

    /**
     * The newly created token ID.
     *
     * @var string
     */
    public string $tokenId;

    /**
     * The ID of the user associated with the token.
     *
     * @var string
     */
    public string $userId;

    /**
     * The ID of the client associated with the token.
     *
     * @var string
     */
    public string $clientId;

    /**
     * Create a new event instance.
     *
     * @param string $tokenId
     * @param int|string|null $userId
     * @param string $clientId
     * @return void
     */
    public function __construct(string $tokenId, int|string|null $userId, string $clientId)
    {
        $this->userId = $userId;
        $this->tokenId = $tokenId;
        $this->clientId = $clientId;
    }

}
