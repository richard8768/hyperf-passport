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

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

class Token extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public bool $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     */
    public bool $timestamps = false;

    /**
     * The database table used by the model.
     */
    protected ?string $table = 'oauth_access_tokens';

    /**
     * The "type" of the primary key ID.
     */
    protected string $keyType = 'string';

    /**
     * The guarded attributes on the model.
     */
    protected array $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'scopes' => 'array',
        'revoked' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected array $dates = [
        'expires_at',
    ];

    /**
     * Get the client that the token belongs to.
     */
    public function client(): BelongsTo
    {
        $passport = make(Passport::class);
        return $this->belongsTo($passport->clientModel());
    }

    /**
     * Get the user that the token belongs to.
     */
    public function user(): BelongsTo
    {
        $provider = config('auth.guards.passport.provider');

        $model = config('auth.providers.' . $provider . '.model');

        return $this->belongsTo($model, 'user_id', (new $model())->getKeyName());
    }

    /**
     * Determine if the token has a given scope.
     */
    public function can(string $scope): bool
    {
        if (in_array('*', $this->scopes)) {
            return true;
        }
        $passport = make(Passport::class);
        $scopes = $passport->withInheritedScopes ? $this->resolveInheritedScopes($scope) : [$scope];

        foreach ($scopes as $loopScope) {
            if (array_key_exists($loopScope, array_flip($this->scopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the token is missing a given scope.
     */
    public function cant(string $scope): bool
    {
        return ! $this->can($scope);
    }

    /**
     * Revoke the token instance.
     */
    public function revoke(): bool
    {
        return $this->forceFill(['revoked' => true])->save();
    }

    /**
     * Determine if the token is a transient JWT token.
     */
    public function transient(): bool
    {
        return false;
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('passport.database_connection') ?? $this->connection;
    }

    /**
     * Resolve all possible scopes.
     */
    protected function resolveInheritedScopes(string $scope): array
    {
        $parts = explode(':', $scope);

        $partsCount = count($parts);

        $scopes = [];

        for ($i = 1; $i <= $partsCount; ++$i) {
            $scopes[] = implode(':', array_slice($parts, 0, $i));
        }

        return $scopes;
    }
}
