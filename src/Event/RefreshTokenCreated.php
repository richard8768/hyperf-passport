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
    public function __construct(string $refreshTokenId, string $accessTokenId)
    {
        $this->accessTokenId = $accessTokenId;
        $this->refreshTokenId = $refreshTokenId;
    }

}
