<?php

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Richard\HyperfPassport\Passport;

class ScopeRepository implements ScopeRepositoryInterface
{

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($identifier): Scope|ScopeEntityInterface|null
    {
        $passport = make(Passport::class);
        if ($passport->hasScope($identifier)) {
            return new Scope($identifier);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     * @param array $scopes
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @param string|null $userIdentifier
     * @param string|null $authCodeId
     */
    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, string|null $userIdentifier = null, ?string $authCodeId = null):array
    {
        if (!in_array($grantType, ['password', 'personal_access', 'client_credentials'])) {
            $scopes = collect($scopes)->reject(function ($scope) {
                return trim($scope->getIdentifier()) === '*';
            })->values()->all();
        }
        $passport = make(Passport::class);
        return collect($scopes)->filter(function ($scope) use ($passport) {
            return $passport->hasScope($scope->getIdentifier());
        })->values()->all();
    }

}
