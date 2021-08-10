<?php

namespace Richard\HyperfPassport;

use Hyperf\DbConnection\Model\Model;

class PersonalAccessClient extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_personal_access_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Hyperf\Database\Model\Relations\BelongsTo
     */
    public function client() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $this->belongsTo($passport->clientModel());
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
