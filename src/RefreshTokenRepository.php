<?php

namespace Richard\HyperfPassport;

class RefreshTokenRepository {

    /**
     * Creates a new refresh token.
     *
     * @param  array  $attributes
     * @return \Richard\HyperfPassport\RefreshToken
     */
    public function create($attributes) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->refreshToken()->create($attributes);
    }

    /**
     * Gets a refresh token by the given ID.
     *
     * @param  string  $id
     * @return \Richard\HyperfPassport\RefreshToken
     */
    public function find($id) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->refreshToken()->where('id', $id)->first();
    }

    /**
     * Stores the given token instance.
     *
     * @param  \Richard\HyperfPassport\RefreshToken  $token
     * @return void
     */
    public function save(RefreshToken $token) {
        $token->save();
    }

    /**
     * Revokes the refresh token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeRefreshToken($id) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->refreshToken()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Revokes refresh tokens by access token id.
     *
     * @param  string  $tokenId
     * @return void
     */
    public function revokeRefreshTokensByAccessTokenId($tokenId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $passport->refreshToken()->where('access_token_id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Checks if the refresh token has been revoked.
     *
     * @param  string  $id
     * @return bool
     */
    public function isRefreshTokenRevoked($id) {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

}
