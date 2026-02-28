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

class AuthCode extends Model
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
    protected ?string $table = 'oauth_auth_codes';

    /**
     * The guarded attributes on the model.
     */
    protected array $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'revoked' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected array $dates = [
        'expires_at',
    ];

    /**
     * The "type" of the primary key ID.
     */
    protected string $keyType = 'string';

    /**
     * Get the client that owns the authentication code.
     */
    public function client(): BelongsTo
    {
        $passport = \Hyperf\Support\make(Passport::class);
        return $this->belongsTo($passport->clientModel());
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('passport.database_connection') ?? $this->connection;
    }
}
