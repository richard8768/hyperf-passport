<?php

namespace Richard\HyperfPassport;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Utils\Str;
use Hyperf\Database\Model\Events\Creating;

class Client extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'grant_types' => 'array',
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
    ];

    /**
     * The temporary plain-text client secret.
     *
     * @var string|null
     */
    protected $plainSecret;

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    public function boot(): void {
        parent::boot();
    }

    public function creating(Creating $event) {
        return function ($model) {
            if (config('passport.client_uuids')) {
                $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string) Str::orderedUuid();
            }
        };
    }

    /**
     * Get the user that the client belongs to.
     *
     * @return \Hyperf\Database\Model\Relations\BelongsTo
     */
    public function user() {
        $provider = $this->provider ?: config('auth.guards.passport.provider');

        return $this->belongsTo(
                        config("auth.providers.{$provider}.model")
        );
    }

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Hyperf\Database\Model\Relations\HasMany
     */
    public function authCodes() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->hasMany($passport->authCodeModel(), 'client_id');
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return \Hyperf\Database\Model\Relations\HasMany
     */
    public function tokens() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->hasMany($passport->tokenModel(), 'client_id');
    }

    /**
     * The temporary non-hashed client secret.
     *
     * This is only available once during the request that created the client.
     *
     * @return string|null
     */
    public function getPlainSecretAttribute() {
        return $this->plainSecret;
    }

    /**
     * Set the value of the secret attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setSecretAttribute($value) {
        $this->plainSecret = $value;
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if (is_null($value) || !$passport->hashesClientSecrets) {
            $this->attributes['secret'] = $value;

            return;
        }

        $this->attributes['secret'] = password_hash($value, PASSWORD_BCRYPT);
    }

    /**
     * Determine if the client is a "first party" client.
     *
     * @return bool
     */
    public function firstParty() {
        return $this->personal_access_client || $this->password_client;
    }

    /**
     * Determine if the client should skip the authorization prompt.
     *
     * @return bool
     */
    public function skipsAuthorization() {
        return false;
    }

    /**
     * Determine if the client is a confidential client.
     *
     * @return bool
     */
    public function confidential() {
        return !empty($this->secret);
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->clientUuids() ? 'string' : $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->clientUuids() ? false : $this->incrementing;
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName() {
        return config('passport.storage.database.connection') ?? $this->connection;
    }

}
