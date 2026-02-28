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

use DateTime;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Richard\HyperfPassport\AuthManager;
use Richard\HyperfPassport\Event\AccessTokenCreated;
use Richard\HyperfPassport\TokenRepository;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * The token repository instance.
     */
    protected TokenRepository $tokenRepository;

    /**
     * The event dispatcher instance.
     */
    protected EventDispatcherInterface $events;

    /**
     * Create a new repository instance.
     */
    public function __construct(TokenRepository $tokenRepository, EventDispatcherInterface $events, AuthManager $auth)
    {
        $this->events = $events;
        $this->tokenRepository = $tokenRepository;
        $this->auth = $auth;
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessToken|AccessTokenEntityInterface
    {
        return new AccessToken($userIdentifier, $scopes, $clientEntity);
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $isRevoke = config('passport.is_revoke_user_others_token');
        if ($isRevoke) {
            $this->tokenRepository->revokeAccessTokenByConditions([
                'user_id' => $accessTokenEntity->getUserIdentifier(),
                'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            ]);
        }
        $this->tokenRepository->create([
            'id' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $this->scopesToArray($accessTokenEntity->getScopes()),
            'revoked' => false,
            'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ]);
        $this->events->dispatch(new AccessTokenCreated(
            $accessTokenEntity->getIdentifier(),
            $accessTokenEntity->getUserIdentifier(),
            $accessTokenEntity->getClient()->getIdentifier()
        ));
    }

    public function revokeAccessToken($tokenId): void
    {
        $this->tokenRepository->revokeAccessToken($tokenId);
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        return $this->tokenRepository->isAccessTokenRevoked($tokenId);
    }
}
