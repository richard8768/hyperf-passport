<?php

namespace Richard\HyperfPassport;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

class AuthCode extends Model
{

    /**
     * The database table used by the model.
     *
     * @var null|string
     */
    protected ?string $table = 'oauth_auth_codes';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public bool $incrementing = false;

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected array $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = [
        'revoked' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected array $dates = [
        'expires_at',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public bool $timestamps = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected string $keyType = 'string';

    /**
     * Get the client that owns the authentication code.
     *
     * @return BelongsTo
     */
    public function client()
    {
        $passport = make(Passport::class);
        return $this->belongsTo($passport->clientModel());
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return config('passport.database_connection') ?? $this->connection;
    }

}
