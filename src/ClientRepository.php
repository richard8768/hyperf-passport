<?php

namespace Richard\HyperfPassport;

use Hyperf\Database\Model\Collection;
use Hyperf\Stringable\Str;
use RuntimeException;

class ClientRepository
{

    /**
     * The personal access client ID.
     *
     * @var int|string|null
     */
    protected string|int|null $personalAccessClientId;

    /**
     * The personal access client secret.
     *
     * @var string|null
     */
    protected ?string $personalAccessClientSecret;

    /**
     * Create a new client repository.
     *
     * @param int|string|null $personalAccessClientId
     * @param string|null $personalAccessClientSecret
     * @return void
     */
    public function __construct($personalAccessClientId = null, $personalAccessClientSecret = null)
    {
        $this->personalAccessClientId = $personalAccessClientId;
        $this->personalAccessClientSecret = $personalAccessClientSecret;
    }

    /**
     * Get a client by the given ID.
     *
     * @param int $id
     * @return Client|null
     */
    public function find($id)
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client();

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     *
     * @param int $id
     * @return Client|null
     */
    public function findActive($id)
    {
        $client = $this->find($id);

        return $client && !$client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     *
     * @param int $clientId
     * @param mixed $userId
     * @return Client|null
     */
    public function findForUser($clientId, $userId)
    {
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
     * @param mixed $userId
     * @return Collection
     */
    public function forUser($userId)
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->client()
            ->where('user_id', $userId)
            ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param mixed $userId
     * @return Collection
     */
    public function activeForUser($userId)
    {
        return $this->forUser($userId)->reject(function ($client) {
            return $client->revoked;
        })->values();
    }

    public function findForProvider($id, $provider = 'user')
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client();

        if (empty($provider)) {
            return $client->where($client->getKeyName(), $id)->first();
        } else {
            return $client->where($client->getKeyName(), $id)->where('provider', $provider)->first();
        }
    }

    /**
     * Get the personal access token client for the application.
     *
     * @return Client
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient($provider = 'users')
    {
        if ($this->personalAccessClientId) {
            $resClient = $this->findForProvider($this->personalAccessClientId, $provider);
            if (empty($resClient)) {
                throw new \Richard\HyperfPassport\Exception\PassportException('Personal access client not found. Please create one..');
            }
            return $resClient;
        }
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $client = $passport->client();
        $client = $client->where('provider', $provider)->orderBy($client->getKeyName(), 'desc')->first();

        if (!$client->exists()) {
            throw new \Richard\HyperfPassport\Exception\PassportException('Personal access client not found. Please create one.');
        }

        $personalClient = $passport->personalAccessClient();
        $clientRes = $personalClient->orderBy($personalClient->getKeyName(), 'desc')->first();
        if (empty($clientRes)) {
            throw new \Richard\HyperfPassport\Exception\PassportException('Personal access client not found. Please create one..');
        }

        return $clientRes->client;
    }

    /**
     * Store a new client.
     *
     * @param int $userId
     * @param string $name
     * @param string $redirect
     * @param string|null $provider
     * @param bool $personalAccess
     * @param bool $password
     * @param bool $confidential
     * @return Client
     */
    public function create($userId, $name, $redirect, $provider = null, $personalAccess = false, $password = false, $confidential = true)
    {
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
     * @param int $userId
     * @param string $name
     * @param string $redirect
     * @return Client
     */
    public function createPersonalAccessClient($userId, $name, $redirect, $provider = null)
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return tap($this->create($userId, $name, $redirect, $provider, true), function ($client) use ($passport) {
            $accessClient = $passport->personalAccessClient();
            $accessClient->client_id = $client->id;
            $accessClient->save();
        });
    }

    /**
     * Store a new password grant client.
     *
     * @param int $userId
     * @param string $name
     * @param string $redirect
     * @param string|null $provider
     * @return Client
     */
    public function createPasswordGrantClient($userId, $name, $redirect, $provider = null)
    {
        return $this->create($userId, $name, $redirect, $provider, false, true);
    }

    /**
     * Update the given client.
     *
     * @param Client $client
     * @param string $name
     * @param string $redirect
     * @return Client
     */
    public function update(Client $client, $name, $redirect)
    {
        $client->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     *
     * @param Client $client
     * @return Client
     */
    public function regenerateSecret(Client $client)
    {
        $client->forceFill([
            'secret' => Str::random(40),
        ])->save();

        return $client;
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param int $id
     * @return bool
     */
    public function revoked($id)
    {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     *
     * @param Client $client
     * @return void
     */
    public function delete(Client $client)
    {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }

    /**
     * Get the personal access client id.
     *
     * @return int|string|null
     */
    public function getPersonalAccessClientId()
    {
        return $this->personalAccessClientId;
    }

    /**
     * Get the personal access client secret.
     *
     * @return string|null
     */
    public function getPersonalAccessClientSecret()
    {
        return $this->personalAccessClientSecret;
    }

}
