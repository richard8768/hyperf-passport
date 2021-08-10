<?php

namespace Richard\HyperfPassport\Bridge;

use Richard\HyperfPassport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface {

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($identifier) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if ($passport->hasScope($identifier)) {
            return new Scope($identifier);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
            array $scopes, $grantType,
            ClientEntityInterface $clientEntity, $userIdentifier = null) {
        if (!in_array($grantType, ['password', 'personal_access', 'client_credentials'])) {
            $scopes = collect($scopes)->reject(function ($scope) {
                        return trim($scope->getIdentifier()) === '*';
                    })->values()->all();
        }
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return collect($scopes)->filter(function ($scope) use ($passport) {
                    return $passport->hasScope($scope->getIdentifier());
                })->values()->all();
    }

}
