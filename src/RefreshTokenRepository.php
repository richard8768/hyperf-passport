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

class RefreshTokenRepository
{

    /**
     * Creates a new refresh token.
     *
     * @param array $attributes
     * @return RefreshToken
     */
    public function create(array $attributes): RefreshToken
    {
        $passport = make(Passport::class);
        return $passport->refreshToken()->create($attributes);
    }

    /**
     * Gets a refresh token by the given ID.
     *
     * @param string $id
     * @return RefreshToken
     */
    public function find(string $id): RefreshToken
    {
        $passport = make(Passport::class);
        return $passport->refreshToken()->where('id', $id)->first();
    }

    /**
     * Stores the given token instance.
     *
     * @param RefreshToken $token
     * @return void
     */
    public function save(RefreshToken $token): void
    {
        $token->save();
    }

    /**
     * Revokes the refresh token.
     *
     * @param string $id
     * @return mixed
     */
    public function revokeRefreshToken(string $id): mixed
    {
        $passport = make(Passport::class);
        return $passport->refreshToken()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Revokes refresh tokens by access token id.
     *
     * @param string $tokenId
     * @return void
     */
    public function revokeRefreshTokensByAccessTokenId(string $tokenId): void
    {
        $passport = make(Passport::class);
        $passport->refreshToken()->where('access_token_id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Revoke refresh tokens by conditions.
     *
     * @param array $conditions
     * @return mixed
     */
    public function revokeRefreshTokensByConditons(array $conditions): mixed
    {
        $passport = make(Passport::class);
        $accessTokenIdList = $passport->token()->where($conditions)->get(['id'])->toArray();
        return $passport->refreshToken()->whereIn('access_token_id', $accessTokenIdList)->update(['revoked' => true]);
    }

    /**
     * Checks if the refresh token has been revoked.
     *
     * @param string $id
     * @return bool
     */
    public function isRefreshTokenRevoked(string $id): bool
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

}
