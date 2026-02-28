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

use Closure;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;
use Richard\HyperfPassport\Utils\Str;

class Client extends Model
{
    /**
     * The database table used by the model.
     */
    protected ?string $table = 'oauth_clients';

    /**
     * The guarded attributes on the model.
     */
    protected array $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected array $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'grant_types' => 'array',
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
    ];

    /**
     * The temporary plain-text client secret.
     */
    protected ?string $plainSecret;

    /**
     * Bootstrap the model and its traits.
     */
    public function boot(): void
    {
        parent::boot();
    }

    public function creating(Creating $event): Closure
    {
        return function ($model) {
            if (config('passport.client_uuids')) {
                $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string) Str::orderedUuid();
            }
        };
    }

    /**
     * Get the user that the client belongs to.
     */
    public function user(): BelongsTo
    {
        $provider = $this->provider ?: config('auth.guards.passport.provider');

        return $this->belongsTo(
            config('auth.providers.' . $provider . '.model')
        );
    }

    /**
     * Get all  the authentication codes for the client.
     */
    public function authCodes(): HasMany
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->authCodeModel(), 'client_id');
    }

    /**
     * Get all  the tokens that belong to the client.
     */
    public function tokens(): HasMany
    {
        $passport = make(Passport::class);
        return $this->hasMany($passport->tokenModel(), 'client_id');
    }

    /**
     * The temporary non-hashed client secret.
     *
     * This is only available once during the request that created the client.
     */
    public function getPlainSecretAttribute(): ?string
    {
        return $this->plainSecret;
    }

    /**
     * Set the value of the secret attribute.
     *
     * @param null|string $value
     */
    public function setSecretAttribute($value): void
    {
        $this->plainSecret = $value;
        $passport = make(Passport::class);
        if (is_null($value) || ! $passport->hashesClientSecrets) {
            $this->attributes['secret'] = $value;

            return;
        }

        $this->attributes['secret'] = password_hash($value, PASSWORD_BCRYPT);
    }

    /**
     * Determine if the client is a "first party" client.
     */
    public function firstParty(): bool
    {
        return $this->personal_access_client || $this->password_client;
    }

    /**
     * Determine if the client should skip the authorization prompt.
     */
    public function skipsAuthorization(): bool
    {
        return false;
    }

    /**
     * Determine if the client is a confidential client.
     */
    public function confidential(): bool
    {
        return ! empty($this->secret);
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        $passport = make(Passport::class);
        return $passport->clientUuids() ? 'string' : $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        $passport = make(Passport::class);
        return $passport->clientUuids() ? false : $this->incrementing;
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('passport.database_connection') ?? $this->connection;
    }
}
