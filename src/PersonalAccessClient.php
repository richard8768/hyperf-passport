<?php

namespace Richard\HyperfPassport;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

class PersonalAccessClient extends Model
{

    /**
     * The database table used by the model.
     *
     * @var null|string
     */
    protected ?string $table = 'oauth_personal_access_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected array $guarded = [];

    /**
     * Get all the authentication codes for the client.
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        $passport = make(Passport::class);
        return $this->belongsTo($passport->clientModel());
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return config('passport.database_connection') ?? $this->connection;
    }

}
