<?php

namespace Richard\HyperfPassport;


use Hyperf\Database\Model\Relations\HasMany;

trait HasApiTokens
{

    /**
     * The current access token for the authentication user.
     *
     * @var Token
     */
    protected Token $accessToken;

    /**
     * Get all  the user's registered OAuth clients.
     *
     * @return HasMany
     */
    public function clients(): HasMany
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->clientModel(), 'user_id');
    }

    /**
     * Get all  the access tokens for the user.
     *
     * @return HasMany
     */
    public function tokens(): HasMany
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return Token|null
     */
    public function token(): ?Token
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function tokenCan(string $scope): bool
    {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param string $name
     * @param array $scopes
     * @param string $provider
     * @return PersonalAccessTokenResult
     */
    public function createToken(string $name, array $scopes = [], string $provider = 'users'): PersonalAccessTokenResult
    {
        return make(PersonalAccessTokenFactory::class)->make(
            $this->getKey(), $name, $scopes, $provider
        );
    }

    /**
     * Set the current access token for the user.
     *
     * @param Token $accessToken
     * @return $this
     */
    public function withAccessToken(Token $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

}
