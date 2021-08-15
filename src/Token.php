<?php

namespace Richard\HyperfPassport;

use Hyperf\DbConnection\Model\Model;

class Token extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_tokens';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_at',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the client that the token belongs to.
     *
     * @return \Hyperf\Database\Model\Relations\BelongsTo
     */
    public function client() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->belongsTo($passport->clientModel());
    }

    /**
     * Get the user that the token belongs to.
     *
     * @return \Hyperf\Database\Model\Relations\BelongsTo
     */
    public function user() {
        $provider = config('auth.guards.passport.provider');

        $model = config('auth.providers.' . $provider . '.model');

        return $this->belongsTo($model, 'user_id', (new $model)->getKeyName());
    }

    /**
     * Determine if the token has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function can($scope) {
        if (in_array('*', $this->scopes)) {
            return true;
        }
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $scopes = $passport->withInheritedScopes ? $this->resolveInheritedScopes($scope) : [$scope];

        foreach ($scopes as $scope) {
            if (array_key_exists($scope, array_flip($this->scopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve all possible scopes.
     *
     * @param  string  $scope
     * @return array
     */
    protected function resolveInheritedScopes($scope) {
        $parts = explode(':', $scope);

        $partsCount = count($parts);

        $scopes = [];

        for ($i = 1; $i <= $partsCount; $i++) {
            $scopes[] = implode(':', array_slice($parts, 0, $i));
        }

        return $scopes;
    }

    /**
     * Determine if the token is missing a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function cant($scope) {
        return !$this->can($scope);
    }

    /**
     * Revoke the token instance.
     *
     * @return bool
     */
    public function revoke() {
        return $this->forceFill(['revoked' => true])->save();
    }

    /**
     * Determine if the token is a transient JWT token.
     *
     * @return bool
     */
    public function transient() {
        return false;
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
