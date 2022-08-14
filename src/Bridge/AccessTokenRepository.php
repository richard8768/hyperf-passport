<?php

namespace Richard\HyperfPassport\Bridge;

use DateTime;
use Psr\EventDispatcher\EventDispatcherInterface;
use Richard\HyperfPassport\TokenRepository;
use Richard\HyperfPassport\Event\AccessTokenCreated;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Richard\HyperfPassport\AuthManager;

class AccessTokenRepository implements AccessTokenRepositoryInterface {

    use FormatsScopesForStorage;

    /**
     * The token repository instance.
     *
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * The event dispatcher instance.
     *
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * Create a new repository instance.
     *
     * @param TokenRepository $tokenRepository
     * @param EventDispatcherInterface $events
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, EventDispatcherInterface $events, AuthManager $auth) {
        $this->events = $events;
        $this->tokenRepository = $tokenRepository;
        $this->auth = $auth;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null) {
        return new AccessToken($userIdentifier, $scopes, $clientEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity) {
        $isRevoke = config('passport.is_revoke_user_others_token');
        if($isRevoke) {
            $this->tokenRepository->revokeAccessTokenByConditons([
                'user_id' => $accessTokenEntity->getUserIdentifier(),
                'client_id' => $accessTokenEntity->getClient()->getIdentifier()
            ]);
        }
        $this->tokenRepository->create([
            'id' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $this->scopesToArray($accessTokenEntity->getScopes()),
            'revoked' => false,
            'created_at' => (new DateTime)->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTime)->format('Y-m-d H:i:s'),
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ]);
        $this->events->dispatch(new AccessTokenCreated(
                        $accessTokenEntity->getIdentifier(),
                        $accessTokenEntity->getUserIdentifier(),
                        $accessTokenEntity->getClient()->getIdentifier()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId) {
        $this->tokenRepository->revokeAccessToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId) {
        return $this->tokenRepository->isAccessTokenRevoked($tokenId);
    }

}
