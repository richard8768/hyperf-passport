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

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Richard\HyperfPassport\Event\RefreshTokenCreated;
use Richard\HyperfPassport\RefreshTokenRepository as PassportRefreshTokenRepository;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * The refresh token repository instance.
     */
    protected PassportRefreshTokenRepository $refreshTokenRepository;

    /**
     * The event dispatcher instance.
     */
    protected EventDispatcherInterface $events;

    /**
     * Create a new repository instance.
     */
    public function __construct(PassportRefreshTokenRepository $refreshTokenRepository, EventDispatcherInterface $events)
    {
        $this->events = $events;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function getNewRefreshToken(): null|RefreshToken|RefreshTokenEntityInterface
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $isRevoke = config('passport.is_revoke_user_others_token');
        if ($isRevoke) {
            $this->refreshTokenRepository->revokeRefreshTokensByConditons([
                'user_id' => $refreshTokenEntity->getAccessToken()->getUserIdentifier(),
                'client_id' => $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier(),
            ]);
        }
        $this->refreshTokenRepository->create([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);
        $this->events->dispatch(new RefreshTokenCreated($id, $accessTokenId));
    }

    public function revokeRefreshToken($tokenId): void
    {
        $this->refreshTokenRepository->revokeRefreshToken($tokenId);
    }

    public function isRefreshTokenRevoked($tokenId): bool
    {
        return $this->refreshTokenRepository->isRefreshTokenRevoked($tokenId);
    }
}
