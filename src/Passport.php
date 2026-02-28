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

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Hyperf\Collection\Collection;
use Hyperf\Collection\Enumerable;

class Passport
{
    /**
     * Indicates if the implicit grant type is enabled.
     */
    public ?bool $implicitGrantEnabled = false;

    /**
     * The default scope.
     */
    public string $defaultScope = '';

    /**
     * All the scopes defined for the application.
     */
    public array $scopes = [
    ];

    /**
     * The date when access tokens expire.
     */
    public ?DateTimeInterface $tokensExpireAt = null;

    /**
     * The date when refresh tokens expire.
     */
    public ?DateTimeInterface $refreshTokensExpireAt = null;

    /**
     * The date when personal access tokens expire.
     */
    public ?DateTimeInterface $personalAccessTokensExpireAt = null;

    /**
     * The name for API token cookies.
     */
    public string $cookie = 'hyperf_token';

    /**
     * Indicates if Passport should ignore incoming CSRF tokens.
     */
    public bool $ignoreCsrfToken = false;

    /**
     * The storage location of the encryption keys.
     */
    public string $keyPath = '';

    /**
     * The auth code model class name.
     */
    public string $authCodeModel = AuthCode::class;

    /**
     * The client model class name.
     */
    public string $clientModel = Client::class;

    /**
     * Indicates if clients are identified by UUIDs.
     */
    public bool $clientUuids = false;

    /**
     * The personal access client model class name.
     */
    public string $personalAccessClientModel = PersonalAccessClient::class;

    /**
     * The token model class name.
     */
    public string $tokenModel = Token::class;

    /**
     * The refresh token model class name.
     */
    public string $refreshTokenModel = RefreshToken::class;

    /**
     * Indicates if Passport migrations will be run.
     */
    public bool $runsMigrations = true;

    /**
     * Indicates if Passport should unserializes cookies.
     */
    public bool $unserializesCookies = false;

    public bool $hashesClientSecrets = false;

    /**
     * Indicates the scope should inherit its parent scope.
     */
    public bool $withInheritedScopes = false;

    /**
     * Enable the implicit grant type.
     */
    public function enableImplicitGrant(): static
    {
        $this->implicitGrantEnabled = true;
        return $this;
    }

    /**
     * Set the default scope(s). Multiple scopes may be an array or specified delimited by spaces.
     */
    public function setDefaultScope(array|string $scope): void
    {
        $this->defaultScope = is_array($scope) ? implode(' ', $scope) : $scope;
    }

    /**
     * Get all  the defined scope IDs.
     */
    public function scopeIds(): array
    {
        return $this->scopes()->pluck('id')->values()->all();
    }

    /**
     * Determine if the given scope has been defined.
     */
    public function hasScope(string $id): bool
    {
        return $id === '*' || array_key_exists($id, $this->scopes);
    }

    /**
     * Get all  the scopes defined for the application.
     */
    public function scopes(): Collection|Enumerable
    {
        return \Hyperf\Collection\collect($this->scopes)->map(function ($description, $id) {
            return new Scope($id, $description);
        })->values();
    }

    /**
     * Get all  the scopes matching the given IDs.
     */
    public function scopesFor(array $ids): array
    {
        return \Hyperf\Collection\collect($ids)->map(function ($id) {
            if (isset($this->scopes[$id])) {
                return new Scope($id, $this->scopes[$id]);
            }
            return [];
        })->filter()->values()->all();
    }

    /**
     * Define the scopes for the application.
     */
    public function tokensCan(array $scopes): void
    {
        $this->scopes = $scopes;
    }

    /**
     * Get or set when access tokens expire.
     *
     * @return DateInterval|Passport
     */
    public function tokensExpireIn(?DateTimeInterface $date = null): DateInterval|Passport|static
    {
        if (is_null($date)) {
            return $this->tokensExpireAt ? Carbon::now()->diff($this->tokensExpireAt) : new DateInterval('P1Y');
        }
        $this->tokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * @return DateInterval|Passport
     */
    public function refreshTokensExpireIn(?DateTimeInterface $date = null): DateInterval|Passport|static
    {
        if (is_null($date)) {
            return $this->refreshTokensExpireAt ? Carbon::now()->diff($this->refreshTokensExpireAt)
                : new DateInterval('P1Y');
        }

        $this->refreshTokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set when personal access tokens expire.
     *
     * @return DateInterval|Passport
     */
    public function personalAccessTokensExpireIn(?DateTimeInterface $date = null): DateInterval|Passport|static
    {
        if (is_null($date)) {
            return $this->personalAccessTokensExpireAt ? Carbon::now()->diff($this->personalAccessTokensExpireAt)
                : new DateInterval('P1Y');
        }

        $this->personalAccessTokensExpireAt = $date;

        return $this;
    }

    /**
     * Get or set the name for API token cookies.
     *
     * @return Passport|string
     */
    public function cookie(?string $cookie = null): Passport|static|string
    {
        if (is_null($cookie)) {
            return $this->cookie;
        }

        $this->cookie = $cookie;

        return $this;
    }

    /**
     * Indicate that Passport should ignore incoming CSRF tokens.
     */
    public function ignoreCsrfToken(bool $ignoreCsrfToken = true): static
    {
        $this->ignoreCsrfToken = $ignoreCsrfToken;

        return $this;
    }

    /**
     * Set the storage location of the encryption keys.
     */
    public function loadKeysFrom(string $path): void
    {
        $this->keyPath = $path;
    }

    /**
     * The location of the encryption keys.
     */
    public function keyPath(string $file): string
    {
        $file = ltrim($file, '/\\');
        $path = BASE_PATH . DIRECTORY_SEPARATOR . (config('passport.key_store_path') ?? 'storage');

        return $this->keyPath ? (rtrim($this->keyPath, '/\\') . DIRECTORY_SEPARATOR . $file) : ($path . DIRECTORY_SEPARATOR . $file);
    }

    /**
     * Set the auth code model class name.
     */
    public function useAuthCodeModel(string $authCodeModel): void
    {
        $this->authCodeModel = $authCodeModel;
    }

    /**
     * Get the auth code model class name.
     */
    public function authCodeModel(): string
    {
        return $this->authCodeModel;
    }

    /**
     * Get a new auth code model instance.
     */
    public function authCode(): AuthCode
    {
        return new $this->authCodeModel();
    }

    /**
     * Set the client model class name.
     */
    public function useClientModel(string $clientModel): void
    {
        $this->clientModel = $clientModel;
    }

    /**
     * Get the client model class name.
     */
    public function clientModel(): string
    {
        return $this->clientModel;
    }

    /**
     * Get a new client model instance.
     */
    public function client(): Client
    {
        return new $this->clientModel();
    }

    /**
     * Determine if clients are identified using UUIDs.
     */
    public function clientUuids(): bool
    {
        return $this->clientUuids;
    }

    /**
     * Specify if clients are identified using UUIDs.
     */
    public function setClientUuids(bool $value): void
    {
        $this->clientUuids = $value;
    }

    /**
     * Set the personal access client model class name.
     */
    public function usePersonalAccessClientModel(string $clientModel): void
    {
        $this->personalAccessClientModel = $clientModel;
    }

    /**
     * Get the personal access client model class name.
     */
    public function personalAccessClientModel(): string
    {
        return $this->personalAccessClientModel;
    }

    /**
     * Get a new personal access client model instance.
     */
    public function personalAccessClient(): PersonalAccessClient
    {
        return new $this->personalAccessClientModel();
    }

    /**
     * Set the token model class name.
     */
    public function useTokenModel(string $tokenModel): void
    {
        $this->tokenModel = $tokenModel;
    }

    /**
     * Get the token model class name.
     */
    public function tokenModel(): string
    {
        return $this->tokenModel;
    }

    /**
     * Get a new personal access client model instance.
     */
    public function token(): Token
    {
        return new $this->tokenModel();
    }

    /**
     * Set the refresh token model class name.
     */
    public function useRefreshTokenModel(string $refreshTokenModel): void
    {
        $this->refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     */
    public function refreshTokenModel(): string
    {
        return $this->refreshTokenModel;
    }

    /**
     * Get a new refresh token model instance.
     */
    public function refreshToken(): RefreshToken
    {
        return new $this->refreshTokenModel();
    }

    /**
     * Configure Passport to hash client credential secrets.
     */
    public function hashClientSecrets(): static
    {
        $this->hashesClientSecrets = true;
        return $this;
    }

    /**
     * Configure Passport to not register its migrations.
     */
    public function ignoreMigrations(): static
    {
        $this->runsMigrations = false;
        return $this;
    }

    /**
     * Instruct Passport to enable cookie serialization.
     */
    public function withCookieSerialization(): static
    {
        $this->unserializesCookies = true;
        return $this;
    }

    /**
     * Instruct Passport to disable cookie serialization.
     */
    public function withoutCookieSerialization(): static
    {
        $this->unserializesCookies = false;
        return $this;
    }
}
