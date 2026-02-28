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

use Hyperf\Database\Model\Collection;
use Hyperf\Stringable\Str;
use Richard\HyperfPassport\Exception\PassportException;

class ClientRepository
{
    /**
     * The personal access client ID.
     */
    protected null|int|string $personalAccessClientId;

    /**
     * The personal access client secret.
     */
    protected ?string $personalAccessClientSecret;

    /**
     * Create a new client repository.
     */
    public function __construct(null|int|string $personalAccessClientId = null, ?string $personalAccessClientSecret = null)
    {
        $this->personalAccessClientId = $personalAccessClientId;
        $this->personalAccessClientSecret = $personalAccessClientSecret;
    }

    /**
     * Get a client by the given ID.
     */
    public function find(int $id): ?Client
    {
        $passport = make(Passport::class);
        $client = $passport->client();

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     */
    public function findActive(int $id): ?Client
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     */
    public function findForUser(int $clientId, mixed $userId): ?Client
    {
        $passport = make(Passport::class);
        $client = $passport->client();

        return $client
            ->where($client->getKeyName(), $clientId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get the client instances for the given user ID.
     */
    public function forUser(mixed $userId): Collection
    {
        $passport = make(Passport::class);
        return $passport->client()
            ->where('user_id', $userId)
            ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param mixed $userId
     * @return \Hyperf\Collection\Collection
     */
    public function activeForUser($userId)
    {
        return $this->forUser($userId)->reject(function ($client) {
            return $client->revoked;
        })->values();
    }

    public function findForProvider($id, $provider = 'user')
    {
        $passport = make(Passport::class);
        $client = $passport->client();

        if (empty($provider)) {
            return $client->where($client->getKeyName(), $id)->first();
        }

        return $client->where($client->getKeyName(), $id)->where('provider', $provider)->first();
    }

    /**
     * Get the personal access token client for the application.
     */
    public function personalAccessClient(string $provider = 'users'): Client
    {
        if ($this->personalAccessClientId) {
            $resClient = $this->findForProvider($this->personalAccessClientId, $provider);
            if (empty($resClient)) {
                throw new PassportException('Personal access client not found. Please create one..');
            }
            return $resClient;
        }
        $passport = make(Passport::class);
        $client = $passport->client();
        $client = $client->where('provider', $provider)->orderBy($client->getKeyName(), 'desc')->first();

        if (! $client->exists()) {
            throw new PassportException('Personal access client not found. Please create one.');
        }

        $personalClient = $passport->personalAccessClient();
        $clientRes = $personalClient->orderBy($personalClient->getKeyName(), 'desc')->first();
        if (empty($clientRes)) {
            throw new PassportException('Personal access client not found. Please create one..');
        }

        return $clientRes->client;
    }

    /**
     * Store a new client.
     *
     * @param null $provider
     */
    public function create(?int $userId, string $name, string $redirect, $provider = null, bool $personalAccess = false, bool $password = false, bool $confidential = true): Client
    {
        $passport = make(Passport::class);
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
     * @param null $provider
     */
    public function createPersonalAccessClient(?int $userId, string $name, string $redirect, $provider = null): Client
    {
        $passport = make(Passport::class);
        return tap($this->create($userId, $name, $redirect, $provider, true), function ($client) use ($passport) {
            $accessClient = $passport->personalAccessClient();
            $accessClient->client_id = $client->id;
            $accessClient->save();
        });
    }

    /**
     * Store a new password grant client.
     *
     * @param null $provider
     */
    public function createPasswordGrantClient(?int $userId, string $name, string $redirect, $provider = null): Client
    {
        return $this->create($userId, $name, $redirect, $provider, false, true);
    }

    /**
     * Update the given client.
     */
    public function update(Client $client, string $name, string $redirect): Client
    {
        $client->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     */
    public function regenerateSecret(Client $client): Client
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
     */
    public function revoked($id): bool
    {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     */
    public function delete(Client $client): void
    {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }

    /**
     * Get the personal access client id.
     */
    public function getPersonalAccessClientId(): null|int|string
    {
        return $this->personalAccessClientId;
    }

    /**
     * Get the personal access client secret.
     */
    public function getPersonalAccessClientSecret(): ?string
    {
        return $this->personalAccessClientSecret;
    }
}
