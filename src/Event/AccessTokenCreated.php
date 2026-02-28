<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Event;

class AccessTokenCreated
{
    /**
     * The newly created token ID.
     */
    public string $tokenId;

    /**
     * The ID of the user associated with the token.
     */
    public string $userId;

    /**
     * The ID of the client associated with the token.
     */
    public string $clientId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $tokenId, null|int|string $userId, string $clientId)
    {
        $this->userId = $userId;
        $this->tokenId = $tokenId;
        $this->clientId = $clientId;
    }
}
