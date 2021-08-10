<?php

namespace Richard\HyperfPassport\Bridge;

use Richard\HyperfPassport\Passport;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface {

    use FormatsScopesForStorage;

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode() {
        return new AuthCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity) {
        $attributes = [
            'id' => $authCodeEntity->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => $this->formatScopesForStorage($authCodeEntity->getScopes()),
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ];
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $passport->authCode()->forceFill($attributes)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $passport->authCode()->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->authCode()->where('id', $codeId)->where('revoked', 1)->exists();
    }

}
