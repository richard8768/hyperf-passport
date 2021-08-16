<?php

namespace Richard\HyperfPassport;

use Hyperf\Di\Container;

trait HasApiTokens {

    /**
     * The current access token for the authentication user.
     *
     * @var \Richard\HyperfPassport\Token
     */
    protected $accessToken;

    /**
     * Get all of the user's registered OAuth clients.
     *
     * @return \Hyperf\Database\Model\Relations\HasMany
     */
    public function clients() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->hasMany($passport->clientModel(), 'user_id');
    }

    /**
     * Get all of the access tokens for the user.
     *
     * @return \Hyperf\Database\Model\Relations\HasMany
     */
    public function tokens() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->hasMany($passport->tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return \Richard\HyperfPassport\Token|null
     */
    public function token() {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function tokenCan($scope) {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     * @return \Richard\HyperfPassport\PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = [], $provider = 'users') {
        return Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
                        $this->getKey(), $name, $scopes, $provider
        );
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \Richard\HyperfPassport\Token  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken) {
        $this->accessToken = $accessToken;

        return $this;
    }

}
