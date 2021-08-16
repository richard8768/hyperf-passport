<?php

namespace Richard\HyperfPassport;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;

class Passport {

    /**
     * Indicates if the implicit grant type is enabled.
     *
     * @var bool|null
     */
    public $implicitGrantEnabled = false;

    /**
     * The default scope.
     *
     * @var string
     */
    public $defaultScope;

    /**
     * All of the scopes defined for the application.
     *
     * @var array
     */
    public $scopes = [
            //
    ];

    /**
     * The date when access tokens expire.
     *
     * @var \DateTimeInterface|null
     */
    public $tokensExpireAt;

    /**
     * The date when refresh tokens expire.
     *
     * @var \DateTimeInterface|null
     */
    public $refreshTokensExpireAt;

    /**
     * The date when personal access tokens expire.
     *
     * @var \DateTimeInterface|null
     */
    public $personalAccessTokensExpireAt;

    /**
     * The name for API token cookies.
     *
     * @var string
     */
    public $cookie = 'hyperf_token';

    /**
     * Indicates if Passport should ignore incoming CSRF tokens.
     *
     * @var bool
     */
    public $ignoreCsrfToken = false;

    /**
     * The storage location of the encryption keys.
     *
     * @var string
     */
    public $keyPath;

    /**
     * The auth code model class name.
     *
     * @var string
     */
    public $authCodeModel = 'Richard\HyperfPassport\AuthCode';

    /**
     * The client model class name.
     *
     * @var string
     */
    public $clientModel = 'Richard\HyperfPassport\Client';

    /**
     * Indicates if client's are identified by UUIDs.
     *
     * @var bool
     */
    public $clientUuids = false;

    /**
     * The personal access client model class name.
     *
     * @var string
     */
    public $personalAccessClientModel = 'Richard\HyperfPassport\PersonalAccessClient';

    /**
     * The token model class name.
     *
     * @var string
     */
    public $tokenModel = 'Richard\HyperfPassport\Token';

    /**
     * The refresh token model class name.
     *
     * @var string
     */
    public $refreshTokenModel = 'Richard\HyperfPassport\RefreshToken';

    /**
     * Indicates if Passport migrations will be run.
     *
     * @var bool
     */
    public $runsMigrations = true;

    /**
     * Indicates if Passport should unserializes cookies.
     *
     * @var bool
     */
    public $unserializesCookies = false;

    /**
     * @var bool
     */
    public $hashesClientSecrets = false;

    /**
     * Indicates the scope should inherit its parent scope.
     *
     * @var bool
     */
    public $withInheritedScopes = false;

    /**
     * Enable the implicit grant type.
     *
     * @return static
     */
    public function enableImplicitGrant() {
        $this->implicitGrantEnabled = true;
        return $this;
    }

    /**
     * Set the default scope(s). Multiple scopes may be an array or specified delimited by spaces.
     *
     * @param  array|string  $scope
     * @return void
     */
    public function setDefaultScope($scope) {
        $this->defaultScope = is_array($scope) ? implode(' ', $scope) : $scope;
    }

    /**
     * Get all of the defined scope IDs.
     *
     * @return array
     */
    public function scopeIds() {
        return $this->scopes()->pluck('id')->values()->all();
    }

    /**
     * Determine if the given scope has been defined.
     *
     * @param  string  $id
     * @return bool
     */
    public function hasScope($id) {
        return $id === '*' || array_key_exists($id, $this->scopes);
    }

    /**
     * Get all of the scopes defined for the application.
     *
     * @return \Hyperf\Utils\Collection
     */
    public function scopes() {
        return collect($this->scopes)->map(function ($description, $id) {
                    return new Scope($id, $description);
                })->values();
    }

    /**
     * Get all of the scopes matching the given IDs.
     *
     * @param  array  $ids
     * @return array
     */
    public function scopesFor(array $ids) {
        return collect($ids)->map(function ($id) {
                    if (isset($this->scopes[$id])) {
                        return new Scope($id, $this->scopes[$id]);
                    }
                })->filter()->values()->all();
    }

    /**
     * Define the scopes for the application.
     *
     * @param  array  $scopes
     * @return void
     */
    public function tokensCan(array $scopes) {
        $this->scopes = $scopes;
    }

    /**
     * Get or set when access tokens expire.
     *
     * @param  \DateTimeInterface|null  $date
     * @return \DateInterval
     */
    public function tokensExpireIn(DateTimeInterface $date = null) {
        if (is_null($date)) {
            return $this->tokensExpireAt ? Carbon::now()->diff($this->tokensExpireAt) : new DateInterval('P1Y');
        }
        $this->tokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * @param  \DateTimeInterface|null  $date
     * @return \DateInterval
     */
    public function refreshTokensExpireIn(DateTimeInterface $date = null) {
        if (is_null($date)) {
            return $this->refreshTokensExpireAt ? Carbon::now()->diff($this->refreshTokensExpireAt) :
                    new DateInterval('P1Y');
        }

        $this->refreshTokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set when personal access tokens expire.
     *
     * @param  \DateTimeInterface|null  $date
     * @return \DateInterval
     */
    public function personalAccessTokensExpireIn(DateTimeInterface $date = null) {
        if (is_null($date)) {
            return $this->personalAccessTokensExpireAt ? Carbon::now()->diff($this->personalAccessTokensExpireAt) :
                    new DateInterval('P1Y');
        }

        $this->personalAccessTokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set the name for API token cookies.
     *
     * @param  string|null  $cookie
     * @return string
     */
    public function cookie($cookie = null) {
        if (is_null($cookie)) {
            return $this->cookie;
        }

        $this->cookie = $cookie;

        return $this;
    }

    /**
     * Indicate that Passport should ignore incoming CSRF tokens.
     *
     * @param  bool  $ignoreCsrfToken
     * @return static
     */
    public function ignoreCsrfToken($ignoreCsrfToken = true) {
        $this->ignoreCsrfToken = $ignoreCsrfToken;

        return $this;
    }

    /**
     * Set the storage location of the encryption keys.
     *
     * @param  string  $path
     * @return void
     */
    public function loadKeysFrom($path) {
        $this->keyPath = $path;
    }

    /**
     * The location of the encryption keys.
     *
     * @param  string  $file
     * @return string
     */
    public function keyPath($file) {
        $file = ltrim($file, '/\\');
        $path = BASE_PATH . DIRECTORY_SEPARATOR . (config('passport.key_store_path') ?? 'storage');

        return $this->keyPath ? (rtrim($this->keyPath, '/\\') . DIRECTORY_SEPARATOR . $file) : ($path . DIRECTORY_SEPARATOR . $file);
    }

    /**
     * Set the auth code model class name.
     *
     * @param  string  $authCodeModel
     * @return void
     */
    public function useAuthCodeModel($authCodeModel) {
        $this->authCodeModel = $authCodeModel;
    }

    /**
     * Get the auth code model class name.
     *
     * @return string
     */
    public function authCodeModel() {
        return $this->authCodeModel;
    }

    /**
     * Get a new auth code model instance.
     *
     * @return \Richard\HyperfPassport\AuthCode
     */
    public function authCode() {
        return new $this->authCodeModel;
    }

    /**
     * Set the client model class name.
     *
     * @param  string  $clientModel
     * @return void
     */
    public function useClientModel($clientModel) {
        $this->clientModel = $clientModel;
    }

    /**
     * Get the client model class name.
     *
     * @return string
     */
    public function clientModel() {
        return $this->clientModel;
    }

    /**
     * Get a new client model instance.
     *
     * @return \Richard\HyperfPassport\Client
     */
    public function client() {
        return new $this->clientModel;
    }

    /**
     * Determine if clients are identified using UUIDs.
     *
     * @return bool
     */
    public function clientUuids() {
        return $this->clientUuids;
    }

    /**
     * Specify if clients are identified using UUIDs.
     *
     * @param  bool  $value
     * @return void
     */
    public function setClientUuids($value) {
        $this->clientUuids = $value;
    }

    /**
     * Set the personal access client model class name.
     *
     * @param  string  $clientModel
     * @return void
     */
    public function usePersonalAccessClientModel($clientModel) {
        $this->personalAccessClientModel = $clientModel;
    }

    /**
     * Get the personal access client model class name.
     *
     * @return string
     */
    public function personalAccessClientModel() {
        return $this->personalAccessClientModel;
    }

    /**
     * Get a new personal access client model instance.
     *
     * @return \Richard\HyperfPassport\PersonalAccessClient
     */
    public function personalAccessClient() {
        return new $this->personalAccessClientModel;
    }

    /**
     * Set the token model class name.
     *
     * @param  string  $tokenModel
     * @return void
     */
    public function useTokenModel($tokenModel) {
        $this->tokenModel = $tokenModel;
    }

    /**
     * Get the token model class name.
     *
     * @return string
     */
    public function tokenModel() {
        return $this->tokenModel;
    }

    /**
     * Get a new personal access client model instance.
     *
     * @return \Richard\HyperfPassport\Token
     */
    public function token() {
        return new $this->tokenModel;
    }

    /**
     * Set the refresh token model class name.
     *
     * @param  string  $refreshTokenModel
     * @return void
     */
    public function useRefreshTokenModel($refreshTokenModel) {
        $this->refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     *
     * @return string
     */
    public function refreshTokenModel() {
        return $this->refreshTokenModel;
    }

    /**
     * Get a new refresh token model instance.
     *
     * @return \Richard\HyperfPassport\RefreshToken
     */
    public function refreshToken() {
        return new $this->refreshTokenModel;
    }

    /**
     * Configure Passport to hash client credential secrets.
     *
     * @return static
     */
    public function hashClientSecrets() {
        $this->hashesClientSecrets = true;
        return $this;
    }

    /**
     * Configure Passport to not register its migrations.
     *
     * @return static
     */
    public function ignoreMigrations() {
        $this->runsMigrations = false;
        return $this;
    }

    /**
     * Instruct Passport to enable cookie serialization.
     *
     * @return static
     */
    public function withCookieSerialization() {
        $this->unserializesCookies = true;
        return $this;
    }

    /**
     * Instruct Passport to disable cookie serialization.
     *
     * @return static
     */
    public function withoutCookieSerialization() {
        $this->unserializesCookies = false;
        return $this;
    }

}
