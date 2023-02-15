<?php

namespace Richard\HyperfPassport\Event;

class RefreshTokenCreated
{

    /**
     * The newly created refresh token ID.
     *
     * @var string
     */
    public string $refreshTokenId;

    /**
     * The access token ID.
     *
     * @var string
     */
    public string $accessTokenId;

    /**
     * Create a new event instance.
     *
     * @param string $refreshTokenId
     * @param string $accessTokenId
     * @return void
     */
    public function __construct($refreshTokenId, $accessTokenId)
    {
        $this->accessTokenId = $accessTokenId;
        $this->refreshTokenId = $refreshTokenId;
    }

}
