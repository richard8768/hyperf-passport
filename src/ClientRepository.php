<?php

namespace Richard\HyperfPassport;

use Hyperf\Utils\Str;
use RuntimeException;

class ClientRepository {

    /**
     * The personal access client ID.
     *
     * @var int|string|null
     */
    protected $personalAccessClientId;

    /**
     * The personal access client secret.
     *
     * @var string|null
     */
    protected $personalAccessClientSecret;

    /**
     * Create a new client repository.
     *
     * @param  int|string|null  $personalAccessClientId
     * @param  string|null  $personalAccessClientSecret
     * @return void
     */
    public function __construct($personalAccessClientId = null, $personalAccessClientSecret = null) {
        $this->personalAccessClientId = $personalAccessClientId;
        $this->personalAccessClientSecret = $personalAccessClientSecret;
    }

    /**
     * Get a client by the given ID.
     *
     * @param  int  $id
     * @return \Richard\HyperfPassport\Client|null
     */
    public function find($id) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client();

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int  $id
     * @return \Richard\HyperfPassport\Client|null
     */
    public function findActive($id) {
        $client = $this->find($id);

        return $client && !$client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     *
     * @param  int  $clientId
     * @param  mixed  $userId
     * @return \Richard\HyperfPassport\Client|null
     */
    public function findForUser($clientId, $userId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client();

        return $client
                        ->where($client->getKeyName(), $clientId)
                        ->where('user_id', $userId)
                        ->first();
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Hyperf\Database\Model\Collection
     */
    public function forUser($userId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->client()
                        ->where('user_id', $userId)
                        ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Hyperf\Database\Model\Collection
     */
    public function activeForUser($userId) {
        return $this->forUser($userId)->reject(function ($client) {
                    return $client->revoked;
                })->values();
    }

    /**
     * Get the personal access token client for the application.
     *
     * @return \Richard\HyperfPassport\Client
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient() {
        if ($this->personalAccessClientId) {
            return $this->find($this->personalAccessClientId);
        }
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->personalAccessClient();

        if (!$client->exists()) {
            throw new RuntimeException('Personal access client not found. Please create one.');
        }

        return $client->orderBy($client->getKeyName(), 'desc')->first()->client;
    }

    /**
     * Store a new client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @param  string|null  $provider
     * @param  bool  $personalAccess
     * @param  bool  $password
     * @param  bool  $confidential
     * @return \Richard\HyperfPassport\Client
     */
    public function create($userId, $name, $redirect, $provider = null, $personalAccess = false, $password = false, $confidential = true) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client()->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'secret' => ($confidential || $personalAccess) ? Str::random(40) : null,
            'provider' => $provider,
            'redirect' => $redirect,
            'personal_access_client' => $personalAccess,
            'password_client' => $password,
            'revoked' => false,
        ]);

        $client->save();

        return $client;
    }

    /**
     * Store a new personal access token client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @return \Richard\HyperfPassport\Client
     */
    public function createPersonalAccessClient($userId, $name, $redirect) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return tap($this->create($userId, $name, $redirect, null, true), function ($client) use ($passport) {
            $accessClient = $passport->personalAccessClient();
            $accessClient->client_id = $client->id;
            $accessClient->save();
        });
    }

    /**
     * Store a new password grant client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @param  string|null  $provider
     * @return \Richard\HyperfPassport\Client
     */
    public function createPasswordGrantClient($userId, $name, $redirect, $provider = null) {
        return $this->create($userId, $name, $redirect, $provider, false, true);
    }

    /**
     * Update the given client.
     *
     * @param  \Richard\HyperfPassport\Client  $client
     * @param  string  $name
     * @param  string  $redirect
     * @return \Richard\HyperfPassport\Client
     */
    public function update(Client $client, $name, $redirect) {
        $client->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     *
     * @param  \Richard\HyperfPassport\Client  $client
     * @return \Richard\HyperfPassport\Client
     */
    public function regenerateSecret(Client $client) {
        $client->forceFill([
            'secret' => Str::random(40),
        ])->save();

        return $client;
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id) {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     *
     * @param  \Richard\HyperfPassport\Client  $client
     * @return void
     */
    public function delete(Client $client) {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }

    /**
     * Get the personal access client id.
     *
     * @return int|string|null
     */
    public function getPersonalAccessClientId() {
        return $this->personalAccessClientId;
    }

    /**
     * Get the personal access client secret.
     *
     * @return string|null
     */
    public function getPersonalAccessClientSecret() {
        return $this->personalAccessClientSecret;
    }

}