<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\Database\Model\Relations\HasMany;

trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     */
    protected Token $accessToken;

    /**
     * Get all  the user's registered OAuth clients.
     */
    public function clients(): HasMany
    {
        $passport = \Hyperf\Support\make(Passport::class);
        return $this->hasMany($passport->clientModel(), 'user_id');
    }

    /**
     * Get all  the access tokens for the user.
     */
    public function tokens(): HasMany
    {
        $passport = \Hyperf\Support\make(Passport::class);
        return $this->hasMany($passport->tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     */
    public function token(): ?Token
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool
    {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
    }

    /**
     * Create a new personal access token for the user.
     */
    public function createToken(string $name, array $scopes = [], string $provider = 'users'): PersonalAccessTokenResult
    {
        return make(PersonalAccessTokenFactory::class)->make(
            $this->getKey(),
            $name,
            $scopes,
            $provider
        );
    }

    /**
     * Set the current access token for the user.
     *
     * @return $this
     */
    public function withAccessToken(Token $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
