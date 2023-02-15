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
    public function clients()
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->clientModel(), 'user_id');
    }

    /**
     * Get all  the access tokens for the user.
     *
     * @return HasMany
     */
    public function tokens()
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return Token|null
     */
    public function token()
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function tokenCan($scope)
    {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param string $name
     * @param array $scopes
     * @return PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = [], $provider = 'users')
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
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

}
