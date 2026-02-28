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

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Model\Model;

class TokenRepository
{

    /**
     * Creates a new Access Token.
     *
     * @param array $attributes
     * @return Token
     */
    public function create(array $attributes): Token
    {
        $passport = make(Passport::class);
        return $passport->token()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param string $id
     * @return Token
     */
    public function find(string $id): Token
    {
        $passport = make(Passport::class);
        return $passport->token()->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param string $id
     * @param int $userId
     * @return Token|null
     */
    public function findForUser(string $id, int $userId): ?Token
    {
        $passport = make(Passport::class);
        return $passport->token()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param mixed $userId
     * @return Collection
     */
    public function forUser(mixed $userId): Collection
    {
        $passport = make(Passport::class);
        return $passport->token()->where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param Model $user
     * @param Client $client
     * @return Token|null
     */
    public function getValidToken(Model $user, Client $client): ?Token
    {
        return $client->tokens()
            ->whereUserId($user->getKey())
            ->where('revoked', 0)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Store the given token instance.
     *
     * @param Token $token
     * @return void
     */
    public function save(Token $token): void
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param string $id
     * @return mixed
     */
    public function revokeAccessToken(string $id): mixed
    {
        $passport = make(Passport::class);
        return $passport->token()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Revoke access tokens by conditions.
     *
     * @param array $conditions
     * @return mixed
     */
    public function revokeAccessTokenByConditions(array $conditions): mixed
    {
        $passport = make(Passport::class);
        return $passport->token()->where($conditions)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $id
     * @return bool
     */
    public function isAccessTokenRevoked(string $id): bool
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @param Model $user
     * @param Client $client
     * @return Token|null
     */
    public function findValidToken(Model $user, Client $client): ?Token
    {
        return $client->tokens()
            ->whereUserId($user->getKey())
            ->where('revoked', 0)
            ->where('expires_at', '>', Carbon::now())
            ->latest('expires_at')
            ->first();
    }

}
