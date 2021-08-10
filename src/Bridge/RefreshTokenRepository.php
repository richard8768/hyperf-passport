<?php

namespace Richard\HyperfPassport\Bridge;

use Psr\EventDispatcher\EventDispatcherInterface;
use Richard\HyperfPassport\Event\RefreshTokenCreated;
use Richard\HyperfPassport\RefreshTokenRepository as PassportRefreshTokenRepository;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

    /**
     * The refresh token repository instance.
     *
     * @var \Hyperf\Database\Connection
     */
    protected $refreshTokenRepository;

    /**
     * The event dispatcher instance.
     *
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $events;

    /**
     * Create a new repository instance.
     *
     * @param  \Richard\HyperfPassport\RefreshTokenRepository  $refreshTokenRepository
     * @param  \Psr\EventDispatcher\EventDispatcherInterface  $events
     * @return void
     */
    public function __construct(PassportRefreshTokenRepository $refreshTokenRepository, EventDispatcherInterface $events) {
        $this->events = $events;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken() {
        return new RefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity) {
        $this->refreshTokenRepository->create([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);
        $this->events->dispatch(new RefreshTokenCreated($id, $accessTokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken($tokenId) {
        $this->refreshTokenRepository->revokeRefreshToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId) {
        return $this->refreshTokenRepository->isRefreshTokenRevoked($tokenId);
    }

}
